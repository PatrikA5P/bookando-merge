<!-- AppToggle.vue -->
<template>
  <button
    ref="toggleButton"
    :class="['liquid-toggle', sizeClass]"
    type="button"
    :aria-label="ariaLabel"
    :aria-pressed="isPressed.toString()"
    :data-active="isInteracting ? 'true' : null"
    data-bounce="true"
    :style="{ '--complete': isPressed ? 100 : 0 }"
    @click="onClick"
    @keydown.space.prevent
    @keyup.space="onClick"
    @keydown.enter.prevent="onClick"
  >
    <!-- Knockout-Layer (stanzt die schwarze Maske aus) -->
    <div class="knockout">
      <div class="indicator indicator--masked">
        <div class="mask" />
      </div>
    </div>

    <!-- Flüssiger Knopf + Glas -->
    <div class="indicator__liquid">
      <div class="shadow" />
      <div class="wrapper">
        <div class="liquids">
          <div class="liquid__shadow" />
          <div class="liquid__track" />
        </div>
      </div>
      <div class="cover" />
    </div>

    <!-- SVG-Filter exakt wie in der Vorlage -->
    <svg
      class="sr-only"
      aria-hidden="true"
      focusable="false"
    >
      <defs>
        <!-- verschmilzt die Flüssigkeitsformen -->
        <filter id="goo">
          <feGaussianBlur
            in="SourceGraphic"
            stdDeviation="13"
            result="blur"
          />
          <feColorMatrix
            in="blur"
            type="matrix"
            result="goo"
            values="
              1 0 0 0 0
              0 1 0 0 0
              0 0 1 0 0
              0 0 0 13 -10
            "
          />
          <feComposite
            in="SourceGraphic"
            in2="goo"
            operator="atop"
          />
        </filter>

        <!-- entfernt SCHWARZ (Maske) robust, ohne Flackern -->
        <filter
          id="remove-black"
          color-interpolation-filters="sRGB"
        >
          <feColorMatrix
            type="matrix"
            result="black-pixels"
            values="
              1 0 0 0 0
              0 1 0 0 0
              0 0 1 0 0
              -255 -255 -255 0 1
            "
          />
          <feMorphology
            in="black-pixels"
            operator="dilate"
            radius="0.5"
            result="smoothed"
          />
          <feComposite
            in="SourceGraphic"
            in2="smoothed"
            operator="out"
          />
        </filter>
      </defs>
    </svg>
  </button>
</template>

<script lang="ts">
import { defineComponent, ref, watch, onMounted, onBeforeUnmount, computed } from 'vue'

export default defineComponent({
  name: 'AppToggle',
  props: {
    modelValue: { type: Boolean, default: false },
    ariaLabel:   { type: String,  default: 'Toggle' },
    /** Größe: xs | sm | md | lg (default: sm) */
    size: { type: String, default: 'sm', validator: (value: string) => ['xs','sm','md','lg'].includes(value) },
  },
  emits: ['update:modelValue'],
  setup(props, { emit }) {
    const toggleButton = ref<HTMLElement>()
    const isPressed = ref(props.modelValue)
    const isInteracting = ref(false)
    const sizeClass = computed(() => `liquid-toggle--${props.size}`)

    watch(() => props.modelValue, v => { isPressed.value = v })

    let gsap: any | null = null
    let Draggable: any | null = null
    let drags: any[] = []

    const commit = (value: boolean) => emit('update:modelValue', value)

    const animateTo = (toPercent: number) => {
      if (!toggleButton.value || !gsap) return
      isInteracting.value = true
      gsap.timeline({
        onComplete: () => gsap!.delayedCall(0.05, () => (isInteracting.value = false)),
      }).to(toggleButton.value, {
        '--complete': toPercent,
        duration: 0.15,
        delay: 0.2,    // lässt die Bounce-Phase sichtbar
      })
    }

    const onClick = () => {
      const next = !isPressed.value
      animateTo(next ? 100 : 0)
      isPressed.value = next
      commit(next)
    }

    onMounted(async () => {
      if (!toggleButton.value) return
      // GSAP/Draggable als ESM (kein Babel nötig)
      const gsapMod = await import('gsap')
      const draggableMod = await import('gsap/Draggable')

      const unwrapGsap = (mod: any) => {
        if (!mod) return null
        if (mod.default?.registerPlugin) return mod.default
        if (mod.gsap?.registerPlugin) return mod.gsap
        if (mod.registerPlugin) return mod
        return null
      }
      const unwrapDraggable = (mod: any) => {
        if (!mod) return null
        if (mod.default) return mod.default
        if (mod.Draggable) return mod.Draggable
        return mod
      }

      gsap = unwrapGsap(gsapMod)
      Draggable = unwrapDraggable(draggableMod)

      if (!gsap || !Draggable) return

      if (typeof gsap.registerPlugin === 'function') {
        gsap.registerPlugin(Draggable)
      }

      gsap.set(toggleButton.value, { '--complete': isPressed.value ? 100 : 0 })

      const proxy = document.createElement('div')
      drags = Draggable.create(proxy, {
        allowContextMenu: true,
        trigger: toggleButton.value,
        onPress: function () {
          this.__pressTime = Date.now()
          isInteracting.value = true
        },
        onDrag: function () {
          const el = toggleButton.value!
          const delta = this.x - this.startX
          const { width } = el.getBoundingClientRect()
          const startPct = isPressed.value ? 100 : 0
          const complete = gsap!.utils.clamp(0, 100, startPct + (delta / width) * 100)
          gsap!.set(el, { '--complete': complete })
          this.complete = complete
        },
        onDragEnd: function () {
          const el = toggleButton.value!
          const target = (this.complete ?? 0) >= 50 ? 100 : 0
          gsap!.to(el, {
            '--complete': target,
            duration: 0.15,
            onComplete: () => {
              isInteracting.value = false
              const newState = target === 100
              if (newState !== isPressed.value) {
                isPressed.value = newState
                commit(newState)
              }
            },
          })
        },
        onRelease: function () {
          const pressDuration = Date.now() - (this.__pressTime || 0)
          if (!this.isDragging && pressDuration > 150) {
            isInteracting.value = false
          }
        },
      })
    })

    onBeforeUnmount(() => {
      drags.forEach(d => { try { d.kill() } catch {} })
    })

    return { toggleButton, isPressed, isInteracting, onClick, sizeClass }
  },
})
</script>

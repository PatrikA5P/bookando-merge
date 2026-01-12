import { createApp, defineComponent, h, nextTick, ref } from 'vue'
import { afterEach, beforeEach, describe, expect, it, vi, type Mock } from 'vitest'

vi.mock('@core/Design/components/AppShell.vue', () => ({
  default: defineComponent({
    name: 'AppShellStub',
    setup(_, { slots }) {
      return () => h('div', { class: 'app-shell-stub' }, slots.default?.())
    },
  }),
}))

vi.mock('@core/Design/components/AppPageHeader.vue', () => ({
  default: defineComponent({
    name: 'AppPageHeaderStub',
    setup(_, { slots }) {
      return () => h('header', {}, slots.actions ? [slots.default?.(), slots.actions?.()] : slots.default?.())
    },
  }),
}))

vi.mock('@core/Design/components/AppButton.vue', () => ({
  default: defineComponent({
    name: 'AppButtonStub',
    props: {
      icon: { type: String, default: '' },
    },
    emits: ['click'],
    setup(props, { slots, emit }) {
      return () => h('button', { 'data-icon': props.icon, onClick: () => emit('click') }, slots.default?.())
    },
  }),
}))

vi.mock('@core/Design/components/AppTabs.vue', () => ({
  default: defineComponent({
    name: 'AppTabsStub',
    props: {
      modelValue: { type: String, default: '' },
      tabs: { type: Array, default: () => [] },
    },
    emits: ['update:modelValue'],
    setup(props) {
      return () => h('div', { class: 'app-tabs-stub' }, (props.tabs as any[]).map(tab => tab.label).join(' | '))
    },
  }),
}))

vi.mock('@core/Design/components/BookandoField.vue', () => ({
  default: defineComponent({
    name: 'BookandoFieldStub',
    props: {
      modelValue: { type: null, default: '' },
    },
    emits: ['update:modelValue'],
    setup(props, { emit }) {
      return () => h('input', {
        value: props.modelValue ?? '',
        onInput: (event: Event) => emit('update:modelValue', (event.target as HTMLInputElement).value),
      })
    },
  }),
}))

vi.mock('../store/resourcesStore', () => ({
  useResourcesStore: vi.fn(),
}))

vi.mock('@core/Composables/useNotifier', () => ({
  notify: vi.fn(),
}))

vi.mock('vue-i18n', () => ({
  useI18n: () => ({ t: (key: string) => key }),
}))

import { useResourcesStore } from '../store/resourcesStore'
import { notify } from '@core/Composables/useNotifier'
import ResourcesView from '../views/ResourcesView.vue'

describe('ResourcesView', () => {
  let storeMock: {
    resources: ReturnType<typeof ref>,
    loading: ReturnType<typeof ref>,
    error: ReturnType<typeof ref>,
    deletingId: ReturnType<typeof ref>,
    saving: ReturnType<typeof ref>,
    loadResources: Mock,
    persistResource: Mock,
    removeResource: Mock,
  }
  const mounts: Array<{ unmount: () => void }> = []

  beforeEach(() => {
    vi.clearAllMocks()
    storeMock = {
      resources: ref({
        locations: [],
        rooms: [],
        materials: [],
      }),
      loading: ref(false),
      error: ref(null),
      deletingId: ref(null),
      saving: ref(false),
      loadResources: vi.fn(),
      persistResource: vi.fn(),
      removeResource: vi.fn(),
    }

    ;(useResourcesStore as unknown as Mock).mockReturnValue(storeMock)
  })

  afterEach(() => {
    mounts.splice(0).forEach(mount => mount.unmount())
  })

  function mountComponent() {
    const container = document.createElement('div')
    document.body.appendChild(container)
    const app = createApp(ResourcesView)
    const vm = app.mount(container) as any
    const unmount = () => {
      app.unmount()
      container.remove()
    }
    mounts.push({ unmount })
    return { vm, container }
  }

  it('loads resources on mount', async () => {
    mountComponent()
    await nextTick()
    expect(storeMock.loadResources).toHaveBeenCalledTimes(1)
  })

  it('submits resource form via the store', async () => {
    ;(storeMock.persistResource as Mock).mockResolvedValue({ id: '1' })

    const { vm } = mountComponent()
    await nextTick()

    const state = vm.$.setupState as any

    state.showModal = true
    state.resourceForm.type = 'rooms'
    state.resourceForm.name = 'Besprechungsraum'
    state.tagsInput = 'alpha, beta'

    await state.submitResource()

    expect(storeMock.persistResource).toHaveBeenCalledWith('rooms', expect.objectContaining({
      name: 'Besprechungsraum',
      tags: ['alpha', 'beta'],
      type: 'rooms',
    }))
    expect(notify).toHaveBeenCalledWith(expect.objectContaining({ type: 'success' }))
    expect(state.showModal).toBe(false)
  })

  it('removes a resource through the store', async () => {
    storeMock.resources.value.locations = [{
      id: 'loc-1',
      name: 'Location 1',
      description: '',
      capacity: null,
      tags: [],
      availability: [],
      type: 'locations',
    }]

    ;(storeMock.removeResource as Mock).mockResolvedValue(true)

    const { container } = mountComponent()
    await nextTick()

    const deleteButton = container.querySelector('[data-icon="trash"]') as HTMLButtonElement
    expect(deleteButton).toBeTruthy()

    deleteButton.click()
    await nextTick()

    expect(storeMock.removeResource).toHaveBeenCalledWith('locations', 'loc-1')
    expect(notify).toHaveBeenCalledWith(expect.objectContaining({ type: 'success' }))
  })
})


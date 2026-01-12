<!-- AppPagination.vue -->
<template>
  <div class="bookando-container bookando-py-xs">
    <!-- DESKTOP / >= md -->
    <div
      v-if="!belowMd"
      class="bookando-grid align-top"
      style="--bookando-grid-cols: 1fr auto 1fr;"
    >
      <!-- LINKS (oben links) -->
      <div class="bookando-flex bookando-flex-col bookando-items-start">
        <div class="bookando-label bookando-mb-xxs">
          {{ pageSizeLabelResolved }}
        </div>
        <BookandoField
          v-model="internalPageSize"
          type="dropdown"
          :options="pageSizeOptions"
          :appearance="'default'"
          :grouped="false"
          :teleport="true"
          :match-trigger-width="true"
          :dropup="true"
          :z-index="10020"
          :label="''"
          width="content"
          class="bookando-dropdown-wrapper--auto"
          :style="{ width: dropdownWidthPx + 'px' }"
        />
      </div>

      <!-- MITTE (zentriert) -->
      <div class="bookando-flex bookando-justify-center bookando-items-center">
        <div class="bookando-pagination">
          <AppButton
            icon="chevron-left"
            variant="standard"
            size="square"
            btn-type="icononly"
            :disabled="currentPage === 1 || totalPages < 1"
            :tooltip="t('ui.pagination.prev')"
            :aria-label="t('ui.pagination.prev')"
            @click="goToPage(currentPage - 1)"
          />
          <template
            v-for="(item, idx) in paginationItems"
            :key="`p-${idx}-${item}`"
          >
            <AppButton
              v-if="item === ELLIPSIS"
              variant="standard"
              size="square"
              btn-type="full"
              :disabled="true"
              aria-hidden="true"
            >
              …
            </AppButton>
            <AppButton
              v-else
              :variant="currentPage === item ? 'primary' : 'standard'"
              size="square"
              btn-type="full"
              :disabled="currentPage === item"
              :aria-current="currentPage === item ? 'page' : undefined"
              :tooltip="t('ui.pagination.go_to_page', { page: item })"
              :aria-label="t('ui.pagination.go_to_page', { page: item })"
              @click="goToPage(item as number)"
            >
              {{ item }}
            </AppButton>
          </template>
          <AppButton
            icon="chevron-right"
            variant="standard"
            size="square"
            btn-type="icononly"
            :disabled="currentPage === totalPages || totalPages < 1"
            :tooltip="t('ui.pagination.next')"
            :aria-label="t('ui.pagination.next')"
            @click="goToPage(currentPage + 1)"
          />
        </div>
      </div>

      <!-- RECHTS (oben rechts) -->
      <div class="bookando-flex bookando-justify-end bookando-items-start bookando-text-right">
        <span class="bookando-pagination-range">
          {{ t('ui.pagination.range', { start: startItem, end: endItem, total: totalItems, label: entityLabelPlural }) }}
        </span>
      </div>
    </div>

    <!-- MOBILE / < md -->
    <div v-else>
      <!-- Zeile 1: links Dropdown, rechts Range (eine Zeile, no-wrap) -->
      <div class="bookando-flex bookando-items-start bookando-justify-between bookando-gap-sm">
        <div class="bookando-flex bookando-flex-col bookando-items-start">
          <div class="bookando-label bookando-mb-xxs">
            {{ pageSizeLabelResolved }}
          </div>
          <BookandoField
            v-model="internalPageSize"
            type="dropdown"
            :options="pageSizeOptions"
            :appearance="'default'"
            :grouped="false"
            :teleport="true"
            :match-trigger-width="true"
            :dropup="true"
            :z-index="10020"
            :label="''"
            width="content"
            class="bookando-dropdown-wrapper--auto"
            :style="{ width: dropdownWidthPx + 'px' }"
          />
        </div>

        <div
          class="bookando-text-right"
          style="white-space:nowrap;"
        >
          <span class="bookando-pagination-range">
            {{ t('ui.pagination.range', { start: startItem, end: endItem, total: totalItems, label: entityLabelPlural }) }}
          </span>
        </div>
      </div>

      <!-- Zeile 2: Buttons mittig -->
      <div class="bookando-flex bookando-justify-center bookando-mt-xs">
        <div class="bookando-pagination">
          <AppButton
            icon="chevron-left"
            variant="standard"
            size="square"
            btn-type="icononly"
            :disabled="currentPage === 1 || totalPages < 1"
            :tooltip="t('ui.pagination.prev')"
            :aria-label="t('ui.pagination.prev')"
            @click="goToPage(currentPage - 1)"
          />
          <template
            v-for="(item, idx) in paginationItems"
            :key="`m-${idx}-${item}`"
          >
            <AppButton
              v-if="item === ELLIPSIS"
              variant="standard"
              size="square"
              btn-type="full"
              :disabled="true"
              aria-hidden="true"
            >
              …
            </AppButton>
            <AppButton
              v-else
              :variant="currentPage === item ? 'primary' : 'standard'"
              size="square"
              btn-type="full"
              :disabled="currentPage === item"
              :aria-current="currentPage === item ? 'page' : undefined"
              :tooltip="t('ui.pagination.go_to_page', { page: item })"
              :aria-label="t('ui.pagination.go_to_page', { page: item })"
              @click="goToPage(item as number)"
            >
              {{ item }}
            </AppButton>
          </template>
          <AppButton
            icon="chevron-right"
            variant="standard"
            size="square"
            btn-type="icononly"
            :disabled="currentPage === totalPages || totalPages < 1"
            :tooltip="t('ui.pagination.next')"
            :aria-label="t('ui.pagination.next')"
            @click="goToPage(currentPage + 1)"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, watch, ref, onMounted, nextTick } from 'vue'
import { useI18n } from 'vue-i18n'
import { useResponsive } from '@core/Composables/useResponsive'
import BookandoField from '@core/Design/components/BookandoField.vue'
import AppButton from '@core/Design/components/AppButton.vue'

const { t, te } = useI18n()
const { isBelow } = useResponsive()
const belowMd = isBelow('md')

const ELLIPSIS = '…'

const props = defineProps({
  currentPage: { type: Number, required: true },
  totalPages:  { type: Number, required: true },
  pageSize:    { type: Number, required: true },
  totalItems:  { type: Number, required: true },
  pageSizeOptions: {
    type: Array as () => Array<{ label: string; value: number }>,
    default: () => ([
      { label: '10', value: 10 },
      { label: '20', value: 20 },
      { label: '30', value: 30 },
      { label: '50', value: 50 },
      { label: '100', value: 100 },
      { label: '500', value: 500 },
    ])
  },
  entityLabelSingular: { type: String, default: 'Eintrag' },
  entityLabelPlural:   { type: String, default: 'Einträge' },
  pageSizeLabel: { type: String, default: 'ui.pagination.per_page' },
  siblingCount: { type: Number, default: 2 }
})

const emit = defineEmits<{
  (event: 'page-change', page: number): void
  (event: 'page-size-change', size: number): void
}>()

const pageSizeLabelResolved = computed(() =>
  te(props.pageSizeLabel) ? t(props.pageSizeLabel) : props.pageSizeLabel
)

/* Interner Sync fürs Dropdown */
const internalPageSize = ref(props.pageSize)
watch(() => props.pageSize, val => { internalPageSize.value = val })
watch(internalPageSize, val => emit('page-size-change', val))

/* Navigation */
function goToPage(page: number) {
  const p = Math.max(1, Math.min(page, props.totalPages))
  if (p !== props.currentPage) emit('page-change', p)
}

/* 9-Slots-Pagination: Carets außen + 7 Items (mit Ellipsen) */
function range(a: number, b: number) { const r:number[]=[]; for (let i=a;i<=b;i++) r.push(i); return r }
const paginationItems = computed<(number|string)[]>(() => {
  const total = props.totalPages
  const current = Math.min(Math.max(props.currentPage, 1), Math.max(total, 1))
  if (total <= 7) return range(1, Math.max(total, 1))
  const last = total
  if (current <= 4)       return [1, 2, 3, 4, 5, ELLIPSIS, last]
  if (current >= last-3)  return [1, ELLIPSIS, last-4, last-3, last-2, last-1, last]
  return [1, ELLIPSIS, current-1, current, current+1, ELLIPSIS, last]
})

/* Range-Info */
const startItem = computed(() => props.totalItems === 0 ? 0 : (props.currentPage - 1) * props.pageSize + 1)
const endItem   = computed(() => Math.min(props.currentPage * props.pageSize, props.totalItems))

/* Dropdown-Breitenmessung: breitester Options-Label */
const dropdownWidthPx = ref(96)
function measureDropdownWidth() {
  try {
    const labels = (props.pageSizeOptions || []).map(o => String((o as any).label ?? (o as any).value ?? ''))
    const probe = document.createElement('div')
    probe.className = 'bookando-combobox-btn'
    probe.style.position = 'absolute'
    probe.style.visibility = 'hidden'
    probe.style.whiteSpace = 'nowrap'
    probe.style.display = 'inline-flex'
    probe.style.width = 'auto'
    document.body.appendChild(probe)
    let max = 0
    for (const label of labels) {
      // XSS-safe: Create DOM elements programmatically instead of innerHTML
      probe.textContent = '' // Clear previous content
      const wrapper = document.createElement('div')
      wrapper.className = 'content-wrapper'
      const labelSpan = document.createElement('span')
      labelSpan.className = 'option-label'
      labelSpan.textContent = label // Safe: textContent auto-escapes
      wrapper.appendChild(labelSpan)
      const iconSpan = document.createElement('span')
      iconSpan.className = 'bookando-icon--select'
      iconSpan.setAttribute('aria-hidden', 'true')
      probe.appendChild(wrapper)
      probe.appendChild(iconSpan)
      max = Math.max(max, probe.offsetWidth)
    }
    document.body.removeChild(probe)
    dropdownWidthPx.value = Math.min(Math.max(Math.ceil(max + 8), 88), 160)
  } catch {
    dropdownWidthPx.value = 120
  }
}
onMounted(async () => { await nextTick(); measureDropdownWidth() })
watch(() => props.pageSizeOptions, () => measureDropdownWidth(), { deep: true })
</script>

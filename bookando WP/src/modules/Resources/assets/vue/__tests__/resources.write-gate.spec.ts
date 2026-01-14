import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { defineComponent, h, nextTick } from 'vue'

const apiMock = vi.hoisted(() => ({
  fetchState: vi.fn(),
  saveResource: vi.fn(),
  deleteResource: vi.fn(),
}))

const notifySpy = vi.hoisted(() => vi.fn())

vi.mock('../api/ResourcesApi', () => apiMock)

vi.mock('@core/Design/components/AppShell.vue', () => ({
  default: defineComponent({
    name: 'AppShellStub',
    setup(_props, { slots }) {
      return () => h('div', { 'data-app-shell': '' }, slots.default ? slots.default() : undefined)
    },
  }),
}))

vi.mock('@core/Design/components/AppLicenseOverlay.vue', () => ({
  default: defineComponent({
    name: 'AppLicenseOverlayStub',
    setup(_props, { slots }) {
      return () => h('div', { 'data-license-overlay': '' }, slots.default ? slots.default() : undefined)
    },
  }),
}))

vi.mock('@core/Design/components/AppPageHeader.vue', () => ({
  default: defineComponent({
    name: 'AppPageHeaderStub',
    setup(_props, { slots }) {
      return () => h('header', { 'data-app-page-header': '' }, [
        slots.actions ? h('div', { 'data-slot-actions': '' }, slots.actions()) : null,
        slots.default ? slots.default() : null,
      ])
    },
  }),
}))

vi.mock('@core/Design/components/AppTabs.vue', () => ({
  default: defineComponent({
    name: 'AppTabsStub',
    props: {
      modelValue: {
        type: String,
        default: 'locations',
      },
      tabs: {
        type: Array,
        default: () => [],
      },
    },
    emits: ['update:modelValue'],
    setup(props) {
      return () => h('div', { 'data-app-tabs': props.modelValue })
    },
  }),
}))

vi.mock('@core/Design/components/AppButton.vue', () => ({
  default: defineComponent({
    name: 'AppButtonStub',
    props: {
      icon: {
        type: String,
        default: '',
      },
      variant: {
        type: String,
        default: '',
      },
    },
    emits: ['click'],
    setup(props, { slots, emit }) {
      return () => h(
        'button',
        {
          'data-app-button': props.icon || 'text',
          'data-variant': props.variant || '',
          onClick: (event: MouseEvent) => emit('click', event),
        },
        slots.default ? slots.default() : undefined,
      )
    },
  }),
}))

vi.mock('@core/Design/components/BookandoField.vue', () => ({
  default: defineComponent({
    name: 'BookandoFieldStub',
    props: ['modelValue'],
    emits: ['update:modelValue'],
    setup(_props, { slots }) {
      return () => h('div', { 'data-bookando-field': '' }, slots.default ? slots.default() : undefined)
    },
  }),
}))

vi.mock('@core/Composables/useNotifier', () => ({
  notify: notifySpy,
}))

describe('ResourcesView feature gating', () => {
  beforeEach(() => {
    vi.resetModules()
    vi.clearAllMocks()
    notifySpy.mockReset()
    document.body.innerHTML = '<div id="bookando-resources-root"></div>'

    apiMock.fetchState.mockResolvedValue({
      locations: [
        {
          id: 'loc-1',
          name: 'Konferenzraum',
          description: 'Raum',
          capacity: 10,
          tags: [],
          availability: [],
          type: 'locations',
        },
      ],
      rooms: [],
      materials: [],
    })
    apiMock.saveResource.mockResolvedValue({
      id: 'loc-1',
      name: 'Konferenzraum',
      description: 'Raum',
      capacity: 10,
      tags: [],
      availability: [],
      type: 'locations',
    })
    apiMock.deleteResource.mockResolvedValue(true)
  })

  afterEach(() => {
    document.body.innerHTML = ''
    delete (window as any).BOOKANDO_VARS
  })

  async function mountWithFeatures(features: string[]) {
    ;(window as any).BOOKANDO_VARS = {
      slug: 'resources',
      lang: 'de',
      module_allowed: true,
      license_features: features,
    }

    await import('../main')
    await flushRender()
  }

  async function flushRender() {
    await Promise.resolve()
    await nextTick()
    await Promise.resolve()
    await nextTick()
  }

  it('renders write controls when rest_api_write feature is enabled', async () => {
    await mountWithFeatures(['rest_api_write'])

    expect(document.querySelector('[data-app-button="edit-3"]')).not.toBeNull()
    expect(document.querySelector('[data-app-button="trash"]')).not.toBeNull()
  })

  it('hides write controls when rest_api_write feature is missing', async () => {
    await mountWithFeatures([])

    expect(document.querySelector('[data-app-button="edit-3"]')).toBeNull()
    expect(document.querySelector('[data-app-button="trash"]')).toBeNull()

    const hasPrimaryPlus = Array.from(document.querySelectorAll('button[data-variant="primary"]'))
      .some((btn) => (btn as HTMLButtonElement).dataset.appButton === 'plus')
    expect(hasPrimaryPlus).toBe(false)
  })
})

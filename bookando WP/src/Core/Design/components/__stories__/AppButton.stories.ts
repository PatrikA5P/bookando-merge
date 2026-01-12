import type { Meta, StoryObj } from '@storybook/vue3'
import AppButton from '../AppButton.vue'

const meta: Meta<typeof AppButton> = {
  title: 'Design System/AppButton',
  component: AppButton,
  tags: ['autodocs'],
  argTypes: {
    variant: {
      control: 'select',
      options: ['primary', 'secondary', 'success', 'warning', 'danger', 'ghost', 'link']
    },
    size: {
      control: 'select',
      options: ['sm', 'md', 'lg', 'square', 'dynamic']
    },
    disabled: { control: 'boolean' },
    loading: { control: 'boolean' }
  }
}

export default meta
type Story = StoryObj<typeof AppButton>

export const Primary: Story = {
  args: {
    variant: 'primary'
  },
  render: (args) => ({
    components: { AppButton },
    setup() {
      return { args }
    },
    template: '<AppButton v-bind="args">Primary Button</AppButton>'
  })
}

export const Variants: Story = {
  render: () => ({
    components: { AppButton },
    template: `
      <div style="display: flex; gap: 8px; flex-wrap: wrap;">
        <AppButton variant="primary">Primary</AppButton>
        <AppButton variant="secondary">Secondary</AppButton>
        <AppButton variant="success">Success</AppButton>
        <AppButton variant="warning">Warning</AppButton>
        <AppButton variant="danger">Danger</AppButton>
        <AppButton variant="ghost">Ghost</AppButton>
        <AppButton variant="link">Link</AppButton>
      </div>
    `
  })
}

export const Sizes: Story = {
  render: () => ({
    components: { AppButton },
    template: `
      <div style="display: flex; gap: 8px; align-items: center;">
        <AppButton size="sm" variant="primary">Small</AppButton>
        <AppButton size="md" variant="primary">Medium</AppButton>
        <AppButton size="lg" variant="primary">Large</AppButton>
      </div>
    `
  })
}

export const Loading: Story = {
  args: {
    loading: true,
    variant: 'primary'
  },
  render: (args) => ({
    components: { AppButton },
    setup() {
      return { args }
    },
    template: '<AppButton v-bind="args">Loading</AppButton>'
  })
}

export const Disabled: Story = {
  args: {
    disabled: true,
    variant: 'primary'
  },
  render: (args) => ({
    components: { AppButton },
    setup() {
      return { args }
    },
    template: '<AppButton v-bind="args">Disabled</AppButton>'
  })
}

import type { Meta, StoryObj } from '@storybook/vue3'
import AppBadge from '../AppBadge.vue'

const meta: Meta<typeof AppBadge> = {
  title: 'Design System/AppBadge',
  component: AppBadge,
  tags: ['autodocs'],
  argTypes: {
    variant: {
      control: 'select',
      options: ['default', 'primary', 'secondary', 'success', 'warning', 'danger', 'info']
    },
    size: {
      control: 'select',
      options: ['sm', 'md', 'lg']
    },
    label: { control: 'text' },
    outlined: { control: 'boolean' },
    pill: { control: 'boolean' },
    removable: { control: 'boolean' },
    interactive: { control: 'boolean' }
  }
}

export default meta
type Story = StoryObj<typeof AppBadge>

export const Default: Story = {
  args: {
    label: 'Badge',
    variant: 'default'
  }
}

export const Variants: Story = {
  render: () => ({
    components: { AppBadge },
    template: `
      <div style="display: flex; gap: 8px; flex-wrap: wrap;">
        <AppBadge variant="default" label="Default" />
        <AppBadge variant="primary" label="Primary" />
        <AppBadge variant="secondary" label="Secondary" />
        <AppBadge variant="success" label="Success" />
        <AppBadge variant="warning" label="Warning" />
        <AppBadge variant="danger" label="Danger" />
        <AppBadge variant="info" label="Info" />
      </div>
    `
  })
}

export const Sizes: Story = {
  render: () => ({
    components: { AppBadge },
    template: `
      <div style="display: flex; gap: 8px; align-items: center;">
        <AppBadge size="sm" variant="primary" label="Small" />
        <AppBadge size="md" variant="primary" label="Medium" />
        <AppBadge size="lg" variant="primary" label="Large" />
      </div>
    `
  })
}

export const Pill: Story = {
  args: {
    label: 'Pill Badge',
    variant: 'primary',
    pill: true
  }
}

export const Removable: Story = {
  args: {
    label: 'Removable',
    variant: 'primary',
    removable: true
  }
}

export const Interactive: Story = {
  args: {
    label: 'Interactive',
    variant: 'primary',
    interactive: true
  }
}

import type { Meta, StoryObj } from '@storybook/vue3'
import AppAlert from '../AppAlert.vue'

const meta: Meta<typeof AppAlert> = {
  title: 'Design System/AppAlert',
  component: AppAlert,
  tags: ['autodocs'],
  argTypes: {
    variant: {
      control: 'select',
      options: ['info', 'success', 'warning', 'danger']
    },
    size: {
      control: 'select',
      options: ['sm', 'md', 'lg']
    },
    title: { control: 'text' },
    message: { control: 'text' },
    closable: { control: 'boolean' },
    showIcon: { control: 'boolean' }
  }
}

export default meta
type Story = StoryObj<typeof AppAlert>

export const Default: Story = {
  args: {
    variant: 'info',
    message: 'This is an informational message'
  }
}

export const WithTitle: Story = {
  args: {
    variant: 'success',
    title: 'Success!',
    message: 'Your changes have been saved successfully.'
  }
}

export const Closable: Story = {
  args: {
    variant: 'warning',
    title: 'Warning',
    message: 'Please review your input before continuing.',
    closable: true
  }
}

export const Variants: Story = {
  render: () => ({
    components: { AppAlert },
    template: `
      <div style="display: flex; flex-direction: column; gap: 16px;">
        <AppAlert variant="info" message="This is an info alert" />
        <AppAlert variant="success" message="This is a success alert" />
        <AppAlert variant="warning" message="This is a warning alert" />
        <AppAlert variant="danger" message="This is a danger alert" />
      </div>
    `
  })
}

export const Sizes: Story = {
  render: () => ({
    components: { AppAlert },
    template: `
      <div style="display: flex; flex-direction: column; gap: 16px;">
        <AppAlert size="sm" variant="info" message="Small alert" />
        <AppAlert size="md" variant="info" message="Medium alert" />
        <AppAlert size="lg" variant="info" message="Large alert" />
      </div>
    `
  })
}

import { describe, it, expect, beforeEach } from 'vitest'
import { renderWithI18n, fireClickEvent, createSpy } from '../../__tests__/utils/test-utils'
import AppButton from '../AppButton.vue'

describe('AppButton', () => {
  describe('Rendering', () => {
    it('renders button with default props', () => {
      const { getByRole } = renderWithI18n(AppButton, {
        slots: {
          default: 'Click me'
        }
      })

      const button = getByRole('button')
      expect(button).toBeInTheDocument()
      expect(button).toHaveTextContent('Click me')
    })

    it('renders with variant classes', () => {
      const { getByRole } = renderWithI18n(AppButton, {
        props: {
          variant: 'primary'
        },
        slots: { default: 'Primary' }
      })

      const button = getByRole('button')
      expect(button).toHaveClass('bookando-btn--primary')
    })

    it('renders with different sizes', () => {
      const { getByRole } = renderWithI18n(AppButton, {
        props: {
          size: 'lg'
        },
        slots: { default: 'Large' }
      })

      const button = getByRole('button')
      expect(button).toHaveClass('bookando-btn--lg')
    })

    it('renders as disabled when disabled prop is true', () => {
      const { getByRole } = renderWithI18n(AppButton, {
        props: {
          disabled: true
        },
        slots: { default: 'Disabled' }
      })

      const button = getByRole('button')
      expect(button).toBeDisabled()
    })

    it('renders as loading when loading prop is true', () => {
      const { getByRole, container } = renderWithI18n(AppButton, {
        props: {
          loading: true
        },
        slots: { default: 'Loading' }
      })

      const button = getByRole('button')
      expect(button).toHaveClass('bookando-btn--loading')
      expect(button).toBeDisabled()
      // Check for spinner
      expect(container.querySelector('.bookando-btn__spinner')).toBeInTheDocument()
    })
  })

  describe('Events', () => {
    it('emits click event when clicked', async () => {
      const onClick = createSpy()

      const { getByRole } = renderWithI18n(AppButton, {
        props: {
          onClick
        },
        slots: { default: 'Click me' }
      })

      const button = getByRole('button')
      await fireClickEvent(button)

      expect(onClick).toHaveBeenCalledOnce()
    })

    it('does not emit click when disabled', async () => {
      const onClick = createSpy()

      const { getByRole } = renderWithI18n(AppButton, {
        props: {
          disabled: true,
          onClick
        },
        slots: { default: 'Disabled' }
      })

      const button = getByRole('button')
      await fireClickEvent(button)

      expect(onClick).not.toHaveBeenCalled()
    })

    it('does not emit click when loading', async () => {
      const onClick = createSpy()

      const { getByRole } = renderWithI18n(AppButton, {
        props: {
          loading: true,
          onClick
        },
        slots: { default: 'Loading' }
      })

      const button = getByRole('button')
      await fireClickEvent(button)

      expect(onClick).not.toHaveBeenCalled()
    })
  })

  describe('Accessibility', () => {
    it('has correct button role', () => {
      const { getByRole } = renderWithI18n(AppButton, {
        slots: { default: 'Button' }
      })

      expect(getByRole('button')).toBeInTheDocument()
    })

    it('sets aria-disabled when disabled', () => {
      const { getByRole } = renderWithI18n(AppButton, {
        props: {
          disabled: true
        },
        slots: { default: 'Disabled' }
      })

      const button = getByRole('button')
      expect(button).toHaveAttribute('aria-disabled', 'true')
    })

    it('sets aria-busy when loading', () => {
      const { getByRole } = renderWithI18n(AppButton, {
        props: {
          loading: true
        },
        slots: { default: 'Loading' }
      })

      const button = getByRole('button')
      expect(button).toHaveAttribute('aria-busy', 'true')
    })

    it('has correct type attribute', () => {
      const { getByRole } = renderWithI18n(AppButton, {
        props: {
          nativeType: 'submit'
        },
        slots: { default: 'Submit' }
      })

      const button = getByRole('button')
      expect(button).toHaveAttribute('type', 'submit')
    })
  })

  describe('Icon Support', () => {
    it('renders with icon', () => {
      const { container } = renderWithI18n(AppButton, {
        props: {
          icon: 'check'
        },
        slots: { default: 'With Icon' }
      })

      // AppIcon component should be present
      expect(container.querySelector('.bookando-icon')).toBeInTheDocument()
    })

    it('renders icon-only button', () => {
      const { getByRole } = renderWithI18n(AppButton, {
        props: {
          icon: 'check',
          btnType: 'icononly'
        }
      })

      const button = getByRole('button')
      expect(button).toHaveClass('bookando-btn--icononly')
    })
  })

  describe('Button Types', () => {
    it('renders full-width button', () => {
      const { getByRole } = renderWithI18n(AppButton, {
        props: {
          btnType: 'full'
        },
        slots: { default: 'Full Width' }
      })

      const button = getByRole('button')
      expect(button).toHaveClass('bookando-btn--full')
    })

    it('renders square button', () => {
      const { getByRole } = renderWithI18n(AppButton, {
        props: {
          size: 'square'
        },
        slots: { default: 'Square' }
      })

      const button = getByRole('button')
      expect(button).toHaveClass('bookando-btn--square')
    })
  })
})

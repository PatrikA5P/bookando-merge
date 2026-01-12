import { describe, it, expect } from 'vitest'
import { render } from '@testing-library/vue'
import { fireEvent } from '@testing-library/vue'
import AppInputText from '../AppInputText.vue'

describe('AppInputText', () => {
  it('renders input with value', () => {
    const { getByRole } = render(AppInputText, {
      props: {
        modelValue: 'test value',
        id: 'test-input'
      }
    })

    const input = getByRole('textbox') as HTMLInputElement
    expect(input.value).toBe('test value')
  })

  it('emits update:modelValue on input', async () => {
    const { getByRole, emitted } = render(AppInputText, {
      props: {
        modelValue: '',
        id: 'test-input'
      }
    })

    const input = getByRole('textbox')
    await fireEvent.update(input, 'new value')

    expect(emitted()['update:modelValue']).toBeTruthy()
    expect(emitted()['update:modelValue'][0]).toEqual(['new value'])
  })

  it('applies error class when error prop is set', () => {
    const { getByRole } = render(AppInputText, {
      props: {
        error: 'Error message',
        id: 'test-input'
      }
    })

    const input = getByRole('textbox')
    expect(input).toHaveClass('bookando-control--danger')
  })

  it('sets aria-invalid when error exists', () => {
    const { getByRole } = render(AppInputText, {
      props: {
        error: 'Error message',
        id: 'test-input'
      }
    })

    const input = getByRole('textbox')
    expect(input).toHaveAttribute('aria-invalid', 'true')
  })

  it('sets aria-describedby for error and hint', () => {
    const { getByRole } = render(AppInputText, {
      props: {
        id: 'test-input',
        error: 'Error message',
        hint: 'Hint message'
      }
    })

    const input = getByRole('textbox')
    expect(input).toHaveAttribute('aria-describedby', 'test-input-error test-input-hint')
  })

  it('disables input when disabled prop is true', () => {
    const { getByRole } = render(AppInputText, {
      props: {
        disabled: true,
        id: 'test-input'
      }
    })

    const input = getByRole('textbox')
    expect(input).toBeDisabled()
  })
})

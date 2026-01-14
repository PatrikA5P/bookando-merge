import { ref } from 'vue'

export interface ConfirmOptions {
  title?: string
  message: string
  confirmText?: string
  cancelText?: string
  type?: 'default' | 'info' | 'success' | 'warning' | 'danger'
}

export interface ConfirmState extends ConfirmOptions {
  show: boolean
  resolve?: (value: boolean) => void
}

export function useConfirm() {
  const confirmState = ref<ConfirmState>({
    show: false,
    title: '',
    message: '',
    confirmText: 'Bestätigen',
    cancelText: 'Abbrechen',
    type: 'default'
  })

  const confirm = (options: ConfirmOptions): Promise<boolean> => {
    return new Promise((resolve) => {
      confirmState.value = {
        show: true,
        title: options.title || 'Bestätigung',
        message: options.message,
        confirmText: options.confirmText || 'Bestätigen',
        cancelText: options.cancelText || 'Abbrechen',
        type: options.type || 'default',
        resolve
      }
    })
  }

  const handleConfirm = () => {
    confirmState.value.resolve?.(true)
    confirmState.value.show = false
  }

  const handleCancel = () => {
    confirmState.value.resolve?.(false)
    confirmState.value.show = false
  }

  return {
    confirmState,
    confirm,
    handleConfirm,
    handleCancel
  }
}

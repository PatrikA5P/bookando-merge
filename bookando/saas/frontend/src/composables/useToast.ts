/**
 * useToast — Toast/Snackbar Composable
 *
 * Zeigt kurze Benachrichtigungen am unteren Bildschirmrand.
 * Unterstützt Undo-Aktionen nach destruktiven Operationen.
 *
 * Verwendung:
 *   const toast = useToast();
 *   toast.success('Gespeichert!');
 *   toast.error('Fehler beim Laden');
 *   toast.info('3 Einträge exportiert', { action: { label: 'Rückgängig', onClick: undo } });
 */
import { ref, readonly } from 'vue';

export interface ToastItem {
  id: string;
  type: 'success' | 'error' | 'warning' | 'info';
  message: string;
  duration: number;
  action?: {
    label: string;
    onClick: () => void;
  };
}

const toasts = ref<ToastItem[]>([]);

function addToast(type: ToastItem['type'], message: string, options?: {
  duration?: number;
  action?: ToastItem['action'];
}) {
  const id = `toast-${Date.now()}-${Math.random().toString(36).slice(2)}`;
  const duration = options?.duration ?? 5000;

  const toast: ToastItem = {
    id,
    type,
    message,
    duration,
    action: options?.action,
  };

  toasts.value.push(toast);

  if (duration > 0) {
    setTimeout(() => {
      removeToast(id);
    }, duration);
  }
}

function removeToast(id: string) {
  const index = toasts.value.findIndex(t => t.id === id);
  if (index !== -1) {
    toasts.value.splice(index, 1);
  }
}

export function useToast() {
  return {
    toasts: readonly(toasts),
    success: (message: string, options?: { duration?: number; action?: ToastItem['action'] }) =>
      addToast('success', message, options),
    error: (message: string, options?: { duration?: number; action?: ToastItem['action'] }) =>
      addToast('error', message, options),
    warning: (message: string, options?: { duration?: number; action?: ToastItem['action'] }) =>
      addToast('warning', message, options),
    info: (message: string, options?: { duration?: number; action?: ToastItem['action'] }) =>
      addToast('info', message, options),
    remove: removeToast,
  };
}

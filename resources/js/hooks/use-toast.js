import toast from 'react-hot-toast';

export function useToast() {
  const toastMethods = {
    toast,
    success: (message, options) => toast.success(message, options),
    error: (message, options) => toast.error(message, options),
    info: (message, options) => toast(message, options),
    warning: (message, options) => toast(message, options),
    promise: (promise, msgs, options) => toast.promise(promise, msgs, options),
    dismiss: (toastId) => toast.dismiss(toastId),
    loading: (message, options) => toast.loading(message, options),
  };

  return toastMethods;
}
import Swal from 'sweetalert2';

// Base function for showing alerts
const showAlertFunction = (title, text, type = 'info') => {
  const config = {
    success: {
      icon: 'success',
      confirmButtonColor: '#10B981'
    },
    error: {
      icon: 'error',
      confirmButtonColor: '#EF4444'
    },
    warning: {
      icon: 'warning',
      confirmButtonColor: '#F59E0B'
    },
    info: {
      icon: 'info',
      confirmButtonColor: '#3B82F6'
    }
  };

  const selectedConfig = config[type] || config.info;

  return Swal.fire({
    icon: selectedConfig.icon,
    title,
    text,
    confirmButtonColor: selectedConfig.confirmButtonColor,
    background: '#1a1f28',
    color: '#ffffff',
    confirmButtonText: 'OK'
  });
};

export const showConfirm = (title, text, type = 'question', confirmText = 'Yes', cancelText = 'Cancel') => {
  const iconConfig = {
    question: 'question',
    warning: 'warning',
    error: 'error',
    info: 'info'
  };

  return Swal.fire({
    title,
    text,
    icon: iconConfig[type] || 'question',
    showCancelButton: true,
    confirmButtonColor: type === 'warning' || type === 'error' ? '#EF4444' : '#10B981',
    cancelButtonColor: '#6B7280',
    confirmButtonText: confirmText,
    cancelButtonText: cancelText,
    background: '#1a1f28',
    color: '#ffffff',
    reverseButtons: true
  }).then((result) => {
    return result.isConfirmed;
  });
};

export const showLoading = (title, text) => {
  return Swal.fire({
    title,
    text,
    allowOutsideClick: false,
    allowEscapeKey: false,
    showConfirmButton: false,
    background: '#1a1f28',
    color: '#ffffff',
    didOpen: () => {
      Swal.showLoading();
    }
  });
};

export const showInput = (title, text, inputType = 'text', placeholder = '') => {
  return Swal.fire({
    title,
    text,
    input: inputType,
    inputPlaceholder: placeholder,
    showCancelButton: true,
    confirmButtonColor: '#10B981',
    cancelButtonColor: '#6B7280',
    confirmButtonText: 'Submit',
    cancelButtonText: 'Cancel',
    background: '#1a1f28',
    color: '#ffffff',
    inputValidator: (value) => {
      if (!value || !value.trim()) {
        return 'This field is required';
      }
    }
  });
};

export const closeAlert = () => {
  Swal.close();
};

// Export showAlert as an object with methods (primary export)
export const showAlert = Object.assign(showAlertFunction, {
  success: (title, text) => showAlertFunction(title, text, 'success'),
  error: (title, text) => showAlertFunction(title, text, 'error'),
  warning: (title, text) => showAlertFunction(title, text, 'warning'),
  info: (title, text) => showAlertFunction(title, text, 'info'),
  confirm: showConfirm,
  loading: showLoading,
  close: closeAlert,
  input: showInput
});

// Legacy object export for backward compatibility
export const sweetAlert = {
  success: (title, text) => showAlertFunction(title, text, 'success'),
  error: (title, text) => showAlertFunction(title, text, 'error'),
  warning: (title, text) => showAlertFunction(title, text, 'warning'),
  info: (title, text) => showAlertFunction(title, text, 'info'),
  confirm: showConfirm,
  loading: showLoading,
  close: closeAlert,
  input: showInput
};
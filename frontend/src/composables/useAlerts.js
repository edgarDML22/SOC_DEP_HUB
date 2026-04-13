import Swal from 'sweetalert2'

// 1. Guardamos nuestras clases base en una variable aparte para que no marque "undefined"
const baseClasses = {
    popup: 'swal-border-radius',
    confirmButton: 'btn-primary',
    cancelButton: 'btn-cancel'
}

// 2. SweetAlert principal
const swalApp = Swal.mixin({
    background: 'var(--p-surface-50)',
    color: 'var(--p-surface-900)',
    buttonsStyling: false,
    customClass: baseClasses
})

export const useAlerts = () => {
    // Toasts
    const toastInfo = (title, text, type = 'success') => {
        const iconColor = type === 'success' ? 'var(--state-success)' :
            type === 'error' ? 'var(--state-error)' : 'var(--state-info)';
        Swal.fire({
            toast: true,
            position: 'bottom-end',
            icon: type,
            iconColor: iconColor,
            title: title,
            text: text,
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            background: 'white',
            customClass: { popup: 'swal-border-radius' }
        });
    }

    // Modal de Eliminar 
    const confirmDelete = async (title, text) => {
        return await swalApp.fire({
            title: title,
            text: text,
            showCancelButton: true,
            confirmButtonText: 'Sí, Eliminar',
            cancelButtonText: 'Cancelar',
            customClass: {
                ...baseClasses,
                confirmButton: 'btn-delete-confirm'
            }
        });
    }

    // Modal de Advertencia (Cancelar Pase)
    const confirmWarning = async (title, text, confirmText = 'Confirmar') => {
        return await swalApp.fire({
            title: title,
            text: text,
            showCancelButton: true,
            confirmButtonText: confirmText,
            cancelButtonText: 'Regresar',
        });
    }

    // Modal de Éxito
    const successModal = (title, text) => {
        swalApp.fire({
            title: title,
            text: text,
            icon: 'success',
            iconColor: 'var(--state-success)',
            confirmButtonText: 'OK'
        });
    }

    return { toastInfo, confirmDelete, confirmWarning, successModal }
}
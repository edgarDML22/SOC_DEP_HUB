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
        const iconColor = type === 'success' ? 'var(--color-state-success)' :
            type === 'error' ? 'var(--color-state-error)' : 'var(--color-state-info)';
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

    // Action Toast (estilo homogéneo para acciones exitosas/fallidas)
    const actionToast = (title, type = 'success') => {
        const bgColors = {
            success: '#f0fdf4', // green-50
            error: '#fef2f2',   // red-50
            info: '#eff6ff'     // blue-50
        }
        const textColors = {
            success: '#166534', // green-800
            error: '#991b1b',   // red-800
            info: '#1e40af'     // blue-800
        }
        
        Swal.fire({
            toast: true,
            position: 'bottom-end',
            icon: type,
            title: title,
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            background: bgColors[type] || 'white',
            color: textColors[type] || '#1f2937',
            customClass: { 
                popup: '!rounded-xl !shadow-lg border border-slate-200 !mt-4 !mr-4',
                title: '!text-sm !font-medium !mt-0'
            }
        });
    }

    // Modal de Eliminar 
    const confirmDelete = async (title, text, confirmText = 'Sí, Eliminar') => {
        return await swalApp.fire({
            title: title,
            text: text,
            showCancelButton: true,
            confirmButtonText: confirmText, // <-- Ahora acepta texto personalizado
            cancelButtonText: 'Cancelar',
            customClass: {
                ...baseClasses,
                confirmButton: 'btn-delete-confirm' // <-- Esto mantiene el color rojo
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
        return swalApp.fire({
            title: title,
            text: text,
            icon: 'success',
            iconColor: 'var(--color-state-success)',
            confirmButtonText: 'Aceptar'
        });
    }

    // Modal de Error
    const errorModal = (title, text) => {
        return swalApp.fire({
            title: title,
            text: text,
            icon: 'error',
            iconColor: 'var(--color-state-error)',
            confirmButtonText: 'Entendido'
        });
    }

    // Loading estandarizado (igual en todas las vistas)
    const showLoading = (title = 'Procesando...') => {
        Swal.fire({
            title: title,
            html: '<div class="my-6"><div class="animate-spin rounded-full h-14 w-14 border-t-2 border-b-2 border-primary-600 mx-auto"></div></div>',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            background: 'var(--color-surface-50)',
            color: 'var(--color-surface-900)',
            customClass: { popup: 'swal-border-radius' }
        });
    }

    const closeLoading = () => {
        Swal.close();
    }

    return { toastInfo, actionToast, confirmDelete, confirmWarning, successModal, errorModal, showLoading, closeLoading }
}
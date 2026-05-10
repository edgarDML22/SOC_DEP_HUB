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
    const confirmDelete = async (title, text, confirmText = 'Sí, Eliminar', preConfirmCallback = null) => {
        return await swalApp.fire({
            title: title,
            text: text,
            showCancelButton: true,
            confirmButtonText: confirmText,
            cancelButtonText: 'Cancelar',
            showLoaderOnConfirm: !!preConfirmCallback,
            preConfirm: preConfirmCallback ? async () => {
                try {
                    await preConfirmCallback();
                    return true;
                } catch (error) {
                    Swal.showValidationMessage(error.message || 'Error al procesar la solicitud');
                    return false;
                }
            } : undefined,
            allowOutsideClick: () => !Swal.isLoading(),
            customClass: {
                popup: '!rounded-3xl !shadow-2xl !p-6 border border-surface-200',
                title: '!text-xl !font-black !text-surface-900 !m-0 !pb-2',
                htmlContainer: '!text-sm !font-medium !text-surface-500 !m-0',
                actions: '!mt-6 !flex !gap-3 !w-full !justify-center',
                confirmButton: '!px-6 !py-3 !bg-red-500 hover:!bg-red-600 !text-white !font-bold !rounded-xl !shadow-sm !w-full sm:!w-auto transition-colors',
                cancelButton: '!px-6 !py-3 !bg-white !border !border-surface-200 !text-surface-700 hover:!bg-surface-50 !font-bold !rounded-xl !m-0 !w-full sm:!w-auto transition-colors'
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
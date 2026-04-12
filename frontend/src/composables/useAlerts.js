import Swal from 'sweetalert2'

// Configuración base: Apaga los estilos por defecto y usa tus clases globales
const swalApp = Swal.mixin({
    background: 'var(--p-surface-50)',
    color: 'var(--p-surface-900)',
    buttonsStyling: false, // Inner Join!
    customClass: {
        popup: 'swal-border-radius',
        confirmButton: 'btn-primary',
        cancelButton: 'btn-cancel',
        denyButton: 'btn-delete-confirm'
    }
})

export const useAlerts = () => {
    // 1. Toasts (Las notificaciones que salen abajo a la derecha)
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

    // 2. Modal de Confirmación Peligrosa (Eliminar)
    const confirmDelete = async (title, text) => {
        return await swalApp.fire({
            title: title,
            text: text,
            showCancelButton: true,
            confirmButtonText: 'Sí, Eliminar',
            cancelButtonText: 'Cancelar',
            customClass: { ...swalApp.options.customClass, confirmButton: 'btn-delete-confirm' }
        });
    }

    // 3. Modal de Confirmación Estándar (Cancelar Pase)
    const confirmWarning = async (title, text, confirmText = 'Confirmar') => {
        return await swalApp.fire({
            title: title,
            text: text,
            showCancelButton: true,
            confirmButtonText: confirmText,
            cancelButtonText: 'Regresar',
        });
    }

    // 4. Modal de Éxito al centro de la pantalla
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
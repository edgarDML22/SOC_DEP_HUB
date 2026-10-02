import Swal from 'sweetalert2'

// 1. Guardamos nuestras clases base en una variable aparte para que no marque "undefined"
const baseClasses = {
    popup: 'swal-border-radius',
    confirmButton: 'btn-primary',
    cancelButton: 'btn-cancel'
}

// 2. SweetAlert principal
// Usamos --color-surface-* (definidas en :root y .app-dark) y no --p-surface-*,
// que solo existen dentro del tema oscuro de PrimeVue.
const swalApp = Swal.mixin({
    background: 'var(--color-surface-50)',
    color: 'var(--color-surface-900)',
    buttonsStyling: false,
    customClass: baseClasses
})

// Toasts base
const toastInfo = (title, text, type = 'info') => {
    const iconColor = type === 'success' ? '#15803d' :
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
    const iconColors = {
        success: '#15803d', // dark green / emerald-700
        error: '#dc2626',   // red-600
        info: '#2563eb'     // blue-600
    }
    
    Swal.fire({
        toast: true,
        position: 'bottom-end',
        icon: type,
        iconColor: iconColors[type],
        title: title,
        showConfirmButton: false,
        timer: type === 'error' ? 4500 : 3000,
        timerProgressBar: true,
        background: bgColors[type] || 'white',
        color: textColors[type] || '#1f2937',
        customClass: { 
            popup: '!rounded-xl !shadow-lg border border-slate-200 !mt-4 !mr-4',
            title: '!text-sm !font-medium !mt-0'
        }
    });
}

const toastSuccess = (title) => {
    actionToast(title, 'success')
}

const toastError = (title) => {
    actionToast(title, 'error')
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
const errorModal = (title, html) => {
    return swalApp.fire({
        title: title,
        html: html,
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

/**
 * Extrae recursivamente listas de errores de arrays u objetos asociativos
 */
const extractErrorMessages = (item) => {
    if (!item) return [];
    if (typeof item === 'string') return [item];
    if (Array.isArray(item)) return item.flatMap(extractErrorMessages);
    if (typeof item === 'object') return Object.values(item).flatMap(extractErrorMessages);
    return [String(item)];
}

/**
 * Extrae un mensaje amigable y detallado de un error de API (Axios u otro)
 */
export const extractApiErrorMessage = (error) => {
    if (!error) return 'Ocurrió un error inesperado.';

    if (error.response) {
        const { status, data } = error.response;

        // HTTP 422: Error de validación
        if (status === 422) {
            if (data?.errors) {
                const errorList = extractErrorMessages(data.errors).filter(Boolean);
                if (errorList.length > 0) {
                    return errorList.join(' ');
                }
            }
            if (data?.message) return data.message;
            if (data?.error && typeof data.error === 'string') return data.error;
            return 'Error de validación: los datos proporcionados no son válidos.';
        }

        // HTTP 409: Conflicto / Colisiones de agenda
        if (status === 409) {
            // El código de error específico tiene prioridad sobre el mensaje genérico.
            if (data?.error === 'CONFLICTO_SEMANA') {
                return 'Conflicto de agenda: Ya existen sesiones o eventos programados en este horario.';
            }
            if (data?.message) {
                return data.message;
            }
            return 'Conflicto de agenda: El horario, espacio o recurso seleccionado colisiona con otro evento programado.';
        }

        // HTTP 500: Error interno del servidor
        if (status === 500) {
            return data?.message || 'Error interno del servidor. Por favor, intenta de nuevo más tarde.';
        }

        // Otros estados HTTP (404, 403, 401, etc.)
        if (status === 404) {
            return data?.message || data?.error || 'El recurso solicitado no fue encontrado.';
        }
        if (status === 403) {
            return data?.message || 'No tienes permisos suficientes para realizar esta acción.';
        }
        if (status === 401) {
            return data?.message || 'Sesión no válida o expirada. Por favor inicia sesión nuevamente.';
        }

        if (data?.message) return data.message;
        if (data?.error && typeof data.error === 'string') return data.error;
        return `Error del servidor (${status}).`;
    }

    if (error.request) {
        return 'No se pudo conectar con el servidor. Revisa tu conexión a internet.';
    }

    return error.message || 'Ocurrió un error inesperado al procesar la solicitud.';
}

/**
 * Captura y estandariza errores de API (409, 422, 500, etc.) disparando un Toast de error.
 * Retorna el mensaje formateado para su posible uso en estados locales.
 */
export const handleApiError = (error) => {
    const mensaje = extractApiErrorMessage(error);
    toastError(mensaje);
    return mensaje;
}

export const useAlerts = () => {
    return {
        toastInfo,
        actionToast,
        toastSuccess,
        toastError,
        handleApiError,
        extractApiErrorMessage,
        confirmDelete,
        confirmWarning,
        successModal,
        errorModal,
        showLoading,
        closeLoading
    }
}
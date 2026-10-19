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

// ─── Toasts (abajo a la derecha) ─────────────────────────────────────────
// Un solo estilo para todas las alertas de éxito / error / info / advertencia.
// El diseño vive en main.css (.app-toast) para que se vea igual en toda la app.
const TOAST_UI = {
    success: { icon: 'success', color: '#059669' },
    error: { icon: 'error', color: '#e11d48' },
    info: { icon: 'info', color: '#2563eb' },
    warning: { icon: 'warning', color: '#d97706' },
}

const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (c) => (
    { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]
))

const showToast = ({ title, text = '', type = 'success' }) => {
    const kind = TOAST_UI[type] ? type : 'info'
    // Título y texto van juntos en una sola fila para que queden centrados con el ícono
    // (se escapan porque pueden incluir nombres capturados por usuarios)
    const content = `<span class="app-toast__title-main">${escapeHtml(title)}</span>`
        + (text ? `<span class="app-toast__text">${escapeHtml(text)}</span>` : '')
    Swal.fire({
        toast: true,
        position: 'bottom-end',
        icon: TOAST_UI[kind].icon,
        iconColor: TOAST_UI[kind].color,
        title: content,
        showConfirmButton: false,
        timer: kind === 'error' ? 4500 : 3200,
        timerProgressBar: true,
        customClass: {
            popup: `app-toast app-toast--${kind}`,
            title: 'app-toast__title',
            timerProgressBar: 'app-toast__bar'
        },
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer)
            toast.addEventListener('mouseleave', Swal.resumeTimer)
        }
    });
}

// Compatibilidad: toastInfo(título, texto, tipo) y actionToast(título, tipo) usan el mismo diseño
const toastInfo = (title, text, type = 'info') => showToast({ title, text, type })

const actionToast = (title, type = 'success') => showToast({ title, type })

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
        showToast,
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
export const QR_REGEX = /^(OS|MF|OI)[A-Z0-9]{6}$/

/**
 * Valida que el código tenga el formato de QR de socio: prefijo OS/MF/OI + 6 alfanuméricos.
 * Normaliza a mayúsculas antes de validar para tolerar entrada mixta.
 */
export const validarFormatoQR = (codigo) => QR_REGEX.test(codigo?.toUpperCase() ?? '')

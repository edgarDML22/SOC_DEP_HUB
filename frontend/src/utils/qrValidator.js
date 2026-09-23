export const QR_REGEX = /^(QS|MF|QI)[A-Z0-9]{6}$/

/**
 * Valida que el código tenga el formato de QR válido + 6 alfanuméricos.
 *   QS → Socio titular
 *   MF → Miembro familiar
 *   QI → Invitado (con pase diario)
 * Normaliza a mayúsculas antes de validar para tolerar entrada mixta.
 */
export const validarFormatoQR = (codigo) => QR_REGEX.test(codigo?.toUpperCase() ?? '')

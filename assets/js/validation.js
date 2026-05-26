/**
 * assets/js/validation.js
 * Funciones de validación de formularios reutilizables (DWEC)
 *
 * Incluye expresiones regulares para todos los campos comunes
 * Se puede incluir en cualquier página que necesite validación
 */

// ============================================================
// EXPRESIONES REGULARES (DWEC: uso de RegExp)
// ============================================================

const REGEX = {
    /** Email: usuario@dominio.ext */
    email: /^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/,

    /** Teléfono español: 9 dígitos */
    telefono: /^[6789][0-9]{8}$/,

    /** DNI español: 8 números + letra */
    dni: /^[0-9]{8}[A-Za-z]$/,

    /** NIE: X/Y/Z + 7 números + letra */
    nie: /^[XYZxyz][0-9]{7}[A-Za-z]$/,

    /** Solo letras y espacios (nombres) */
    nombre: /^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s'\-]{3,100}$/,

    /** Número de habitación: letras y números */
    habitacion: /^[A-Za-z0-9]{1,10}$/,

    /** Fecha formato YYYY-MM-DD */
    fecha: /^\d{4}-\d{2}-\d{2}$/,

    /** Precio: número positivo con hasta 2 decimales */
    precio: /^\d+(\.\d{1,2})?$/,
};

// ============================================================
// FUNCIONES DE VALIDACIÓN
// ============================================================

/**
 * Valida un email con expresión regular
 * @param {string} email
 * @returns {boolean}
 */
function validarEmail(email) {
    return REGEX.email.test(email.trim());
}

/**
 * Valida un teléfono español (9 dígitos, empieza por 6, 7, 8 o 9)
 * @param {string} telefono
 * @returns {boolean}
 */
function validarTelefono(telefono) {
    return REGEX.telefono.test(telefono.trim().replace(/\s/g, ''));
}

/**
 * Valida un DNI español
 * @param {string} dni
 * @returns {boolean}
 */
function validarDNI(dni) {
    if (!REGEX.dni.test(dni)) return false;
    // Validar la letra del DNI matemáticamente
    const letras = 'TRWAGMYFPDXBNJZSQVHLCKE';
    const num    = parseInt(dni.substring(0, 8));
    const letra  = letras[num % 23];
    return letra === dni.charAt(8).toUpperCase();
}

/**
 * Calcula la edad a partir de una fecha de nacimiento (objeto Date)
 * @param {string} fechaNacimiento - formato YYYY-MM-DD
 * @returns {number} edad en años
 */
function calcularEdad(fechaNacimiento) {
    const hoy  = new Date();
    const nac  = new Date(fechaNacimiento);
    let edad   = hoy.getFullYear() - nac.getFullYear();
    const mes  = hoy.getMonth() - nac.getMonth();
    if (mes < 0 || (mes === 0 && hoy.getDate() < nac.getDate())) {
        edad--;
    }
    return edad;
}

/**
 * Calcula el número de noches entre dos fechas (objeto Date)
 * @param {string} checkin  - formato YYYY-MM-DD
 * @param {string} checkout - formato YYYY-MM-DD
 * @returns {number} número de noches (negativo si checkout <= checkin)
 */
function calcularNoches(checkin, checkout) {
    const d1 = new Date(checkin);
    const d2 = new Date(checkout);
    const diffMs = d2 - d1;
    return Math.round(diffMs / (1000 * 60 * 60 * 24));
}

/**
 * Formatea una fecha en español usando objeto Date
 * @param {string} fechaStr - formato YYYY-MM-DD o Date
 * @returns {string} - "14 de junio de 2025"
 */
function formatearFecha(fechaStr) {
    const fecha = new Date(fechaStr);
    return fecha.toLocaleDateString('es-ES', {
        day:   'numeric',
        month: 'long',
        year:  'numeric'
    });
}

/**
 * Muestra un mensaje de error en un campo con efecto fadeIn
 * @param {string} fieldId  - ID del campo
 * @param {string} mensaje  - Mensaje de error
 */
function mostrarError(fieldId, mensaje) {
    const campo = $('#' + fieldId);
    campo.addClass('is-invalid').removeClass('is-valid');

    // Crear o actualizar el mensaje de error
    let feedbackEl = campo.next('.invalid-feedback');
    if (!feedbackEl.length) {
        feedbackEl = $('<div class="invalid-feedback"></div>');
        campo.after(feedbackEl);
    }
    feedbackEl.text(mensaje).hide().fadeIn(300);
}

/**
 * Marca un campo como válido
 * @param {string} fieldId
 */
function marcarValido(fieldId) {
    $('#' + fieldId).removeClass('is-invalid').addClass('is-valid');
}

/**
 * Limpia todos los errores de un formulario
 * @param {string} formId
 */
function limpiarErrores(formId) {
    $('#' + formId + ' .is-invalid').removeClass('is-invalid');
    $('#' + formId + ' .is-valid').removeClass('is-valid');
    $('#' + formId + ' .invalid-feedback').remove();
}

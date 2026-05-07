/**
 * Formats a snake_case string into Title Case (e.g., "MENTE_CUERPO" -> "Mente Cuerpo")
 * @param {string} text - The string to format
 * @returns {string} - The formatted string
 */

export function useformat() {
    const formatText = (text) => {
        if (text === null || text === undefined) return "N/A";

        // Handle Booleans
        if (typeof text === 'boolean') {
            return text ? 'Activo' : 'Inactivo';
        }

        // Handle Numbers
        if (typeof text === 'number') {
            return text.toString();
        }

        // Handle Strings
        const str = String(text).trim();
        if (!str) return "N/A";

        // Handle special cases for booleans in string format
        if (str.toLowerCase() === 'true') return 'Activo';
        if (str.toLowerCase() === 'false') return 'Inactivo';

        return str
            .toLowerCase()
            .split(/[_\s-]+/) // Split by underscores, spaces, or hyphens
            .map(word => word.charAt(0).toUpperCase() + word.slice(1))
            .join(' ');
    };

    const formatDateTime = (dateString) => {
        if (!dateString) return "N/A";
        return new Date(dateString).toLocaleString();
    };

    const formatStatus = (status) => {
        return formatText(status);
    };

    const formatDate = (dateString) => {
        if (!dateString) return "N/A";
        return new Date(dateString).toLocaleDateString();
    };

    const formatTime = (dateString) => {
        if (!dateString) return "N/A";
        return new Date(dateString).toLocaleTimeString();
    };

    const formatCurrency = (amount) => {
        if (!amount) return "N/A";
        return amount.toLocaleString("en-US", {
            style: "currency",
            currency: "USD",
        });
    };

    const dateFormat = (fecha) => {
        if (!fecha) return "N/A";
        const fechaObj = new Date(fecha);
        let dia = fechaObj.getDate();
        let mes = fechaObj.getMonth() + 1;
        const anio = fechaObj.getFullYear();
        if (dia < 10) dia = '0' + String(dia);
        if (mes < 10) mes = '0' + String(mes);
        return `${dia}/${mes}/${anio}`;
    }

    const formatCategoryEnum = (val) => {
        if (!val) return '—';
        const map = {
            'MENTE_CUERPO': 'Mente y Cuerpo',
            'ACUATICO': 'Deportes Acuáticos',
            'DEPORTES_EQUIPO': 'Deportes de Equipo',
            'DEPORTES_RAQUETA': 'Deportes de Raqueta',
            'ARTES_MARCIALES': 'Artes Marciales',
            'GIMNASIA': 'Gimnasia',
            'ACONDICIONAMIENTO_FISICO': 'Acondicionamiento Físico',
        };
        return map[val] || val;
    };

    return { formatText, formatDateTime, formatStatus, formatDate, formatTime, formatCurrency, dateFormat, formatCategoryEnum };
}

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

    return { formatText, formatDateTime, formatStatus, formatDate, formatTime, formatCurrency };
}

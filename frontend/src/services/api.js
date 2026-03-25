import axios from 'axios';

const api = axios.create({
    // La URL del backend en Laravel expuesto por Docker
    baseURL: 'http://localhost:8000',

    // ESTA ES LA REGLA DE ORO PARA SANCTUM
    withCredentials: true,

    headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
    }
});

api.interceptors.request.use(
    (config) => {
        const token = localStorage.getItem('auth_token');
        if (token) {
            config.headers.Authorization = `Bearer ${token}`;
        }
        return config;
    },
    (error) => {
        return Promise.reject(error);
    }
);

api.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response && error.response.status === 401) {
            console.warn('Sesión expirada o token inválido');
            // Nota: Aquí en el futuro podrías agregar lógica para redirigir al router de Vue hacia el login
        }
        return Promise.reject(error);
    }
);

export default api;
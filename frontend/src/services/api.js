import axios from 'axios';

const api = axios.create({

    baseURL: 'http://localhost:8000',


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

// cuando el token expira (401).
api.interceptors.response.use(
    (response) => response,
    (error) => {
        // Si el servidor responde 401 (No autorizado), podría forzar el logout
        if (error.response && error.response.status === 401) {
            console.warn('Sesión expirada o token inválido');
            // localStorage.clear(); // Opcional: limpiar datos
            // window.location.href = '/login'; // Opcional: redirigir
        }
        return Promise.reject(error);
    }
);

export default api;
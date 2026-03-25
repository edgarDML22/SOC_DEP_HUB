import axios from 'axios';

const api = axios.create({
<<<<<<< HEAD
    // La URL de el backend en Laravel expuesto por Docker
    baseURL: 'http://localhost:8000',

    //ESTA ES LA REGLA DE ORO PARA SANCTUM
    withCredentials: true,

=======
    baseURL: 'http://localhost:8000',
    withCredentials: true,
>>>>>>> feature/SDH-12-pass-recover
    headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
    }
});

<<<<<<< HEAD
=======
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
        }
        return Promise.reject(error);
    }
);

>>>>>>> feature/SDH-12-pass-recover
export default api;
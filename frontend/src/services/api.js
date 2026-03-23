import axios from 'axios';

const api = axios.create({
    // La URL de el backend en Laravel expuesto por Docker
    baseURL: 'http://localhost:8000',

    //ESTA ES LA REGLA DE ORO PARA SANCTUM
    withCredentials: true,

    headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
    }
});

export default api;
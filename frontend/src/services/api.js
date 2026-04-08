import axios from "axios";

import router from "@/router";

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL, //URL DEL BACKEND
  withCredentials: true,

  headers: {
    Accept: "application/json",
    "Content-Type": "application/json",
  },
});

api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem("auth_token");
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  },
);

api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response && error.response.status === 401) {
      localStorage.removeItem("auth_token");
      localStorage.removeItem("user_data");

      router.push("/login");
    }
    return Promise.reject(error);
  },
);

export default api;

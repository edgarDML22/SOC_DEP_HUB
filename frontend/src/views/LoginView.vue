<script setup>
import { ref, reactive } from 'vue';
import { useRouter } from 'vue-router';
// IMPORTANTE: Importamos tu instancia personalizada, no la librería global
import api from '@/services/api'; 

const router = useRouter();
const form = reactive({ email: '', password: '' });
const errorMessage = ref('');
const isLoading = ref(false);

const handleLogin = async () => {
    errorMessage.value = '';
    if (!form.email || !form.password) {
        errorMessage.value = 'Por favor, complete todos los campos.';
        return;
    }
    isLoading.value = true;
    
    try {
        // 1. Opcional: Si usas Sanctum con cookies, primero pide el CSRF-TOKEN
        // await api.get('/sanctum/csrf-cookie');

        // 2. Usamos 'api' en lugar de 'axios'. 
        // Solo ponemos la ruta relativa porque el baseURL ya es http://localhost:8000
        const response = await api.post('/api/v1/auth/login', form);
        
        if (response.data.success) {
            const { token, user } = response.data.data;
            
            // Guardamos info para persistencia
            localStorage.setItem('auth_token', token);
            localStorage.setItem('user_data', JSON.stringify(user));
            
            // Redirección por roles
            const routes = {
                'gerente': '/admin/dashboard',
                'subgerente': '/admin/dashboard',
                'socio_titular': '/socio/home',
                'miembro_familiar': '/socio/home',
                'instructor': '/instructor/home'
            };
            router.push(routes[user.rol] || '/');
        }
    } catch (error) {
        console.error(error);
        errorMessage.value = error.response?.status === 401 
            ? 'Credenciales incorrectas' 
            : 'Error de conexión con el servidor';
    } finally { 
        isLoading.value = false; 
    }
};
</script>

<template>
    <div class="socdep-login-wrapper">
        <div class="socdep-login-card">
            
            <div class="socdep-side-panel">
                <div class="socdep-logo-container">
                    <img src="@/assets/LogoSocDep.jpg" alt="SOCDEP HUB" class="socdep-logo-img" />
                    <h1 class="socdep-title">SOC-DEP HUB</h1>
                    <div class="socdep-line"></div>
                    <p class="socdep-tagline">Gestión Deportiva y Social</p>
                </div>
            </div>

            <div class="socdep-form-panel">
                <div class="socdep-form-inner">
                    <div class="socdep-welcome">
                        <h2>¡Hola de nuevo!</h2>
                        <p>Ingresa tus datos para comenzar</p>
                    </div>

                    <div v-if="errorMessage" class="socdep-error">{{ errorMessage }}</div>

                    <form @submit.prevent="handleLogin" class="socdep-form">
                        <div class="socdep-input-group">
                            <label>Correo Electrónico</label>
                            <input v-model="form.email" type="email" placeholder="carlos@socdep.com" required />
                        </div>

                        <div class="socdep-input-group">
                            <label>Contraseña</label>
                            <input v-model="form.password" type="password" placeholder="••••••••" required />
                        </div>

                        <button type="submit" class="socdep-btn-main" :disabled="isLoading">
                            {{ isLoading ? 'Cargando...' : 'Iniciar Sesión' }}
                        </button>
                    </form>

                    <div class="socdep-divider"><span>o continuar con</span></div>

                    <div class="socdep-socials">
                        <button type="button" class="socdep-btn-social">Google</button>
                        <button type="button" class="socdep-btn-social">Apple</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.socdep-login-wrapper * {
    box-sizing: border-box;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
}

.socdep-login-wrapper {
    position: fixed;
    top: 0; left: 0;
    width: 100vw; height: 100vh;
    background: #f8f9fa;
    display: flex;
    align-items: center; justify-content: center;
    z-index: 9999;
    padding: 20px;
}

.socdep-login-card {
    display: flex;
    width: 100%;
    max-width: 900px;
    height: 550px;
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
}

.socdep-side-panel {
    flex: 1;
    background-color: #0d62ff; 
    display: flex;
    flex-direction: column;
    align-items: center; justify-content: center;
    color: white;
    padding: 40px;
}

.socdep-logo-container {
    text-align: center;
}

.socdep-logo-img {
    width: 160px;
    height: auto;
    margin-bottom: 20px;
}

.socdep-title {
    font-size: 24px;
    font-weight: 700;
    margin: 0 0 10px 0;
    color: #ffffff;
    letter-spacing: -0.5px;
}

.socdep-line {
    width: 30px;
    height: 3px;
    background: #3b82f6;
    margin-bottom: 15px;
    border-radius: 2px;
}

.socdep-tagline {
    opacity: 0.85;
    font-size: 14px;
    color: #e0e7ff;
    text-align: center;
    margin: 0;
}

.socdep-form-panel {
    flex: 1.2;
    padding: 50px;
    display: flex;
    align-items: center; justify-content: center;
}

.socdep-form-inner { width: 100%; max-width: 320px; }

.socdep-welcome h2 { font-size: 24px; font-weight: 700; color: #111827; margin: 0 0 4px 0; }
.socdep-welcome p { color: #6b7280; font-size: 14px; margin: 0 0 30px 0; }

.socdep-input-group { margin-bottom: 20px; }
.socdep-input-group label { 
    display: block; 
    font-size: 13px; 
    font-weight: 500; 
    color: #6b7280; 
    margin-bottom: 6px; 
}

input {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    background-color: #f9fafb;
    color: #111827;
    font-size: 15px;
    font-weight: 500;
    transition: 0.2s;
}

input:focus {
    background-color: #ffffff;
    border-color: #0d62ff;
    outline: none;
    box-shadow: 0 0 0 3px rgba(13, 98, 255, 0.1);
}

.socdep-btn-main {
    width: 100%;
    padding: 12px;
    background-color: #0d62ff;
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 15px;
    cursor: pointer;
    margin-top: 10px;
    transition: 0.2s;
}

.socdep-btn-main:hover { background-color: #004ecc; }

.socdep-divider {
    text-align: center;
    margin: 25px 0;
    position: relative;
}
.socdep-divider::before { content: ""; position: absolute; top: 50%; left: 0; width: 100%; height: 1px; background: #e5e7eb; }
.socdep-divider span { position: relative; background: white; padding: 0 10px; color: #6b7280; font-size: 13px; }

.socdep-socials { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.socdep-btn-social {
    padding: 10px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    background: #ffffff;
    color: #374151;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: 0.2s;
}

.socdep-btn-social:hover { background-color: #f9fafb; }

.socdep-error {
    background-color: #fef2f2;
    color: #991b1b;
    padding: 12px 16px;
    border: 1px solid #f87171;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 14px;
    text-align: center;
}

@media (max-width: 768px) {
    .socdep-side-panel { display: none; }
    .socdep-form-panel { padding: 40px 20px; }
    .socdep-login-card { border: none; box-shadow: none; background: transparent; }
}
</style>
<script setup>
import { ref, reactive } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';

const router = useRouter();

// Estado reactivo
const form = reactive({
    email: '',
    password: ''
});

const errorMessage = ref('');
const isLoading = ref(false);

// Login real
const handleLogin = async () => {
    errorMessage.value = '';

    // Validación frontend
    if (!form.email || !form.password) {
        errorMessage.value = 'Por favor, complete todos los campos.';
        return;
    }

    isLoading.value = true;

    try {
        const response = await axios.post(
            'http://localhost:8000/api/v1/auth/login',
            form
        );

        if (response.data.success) {
            const { token, user } = response.data.data;

            // Guardar sesión
            localStorage.setItem('auth_token', token);
            localStorage.setItem('user_data', JSON.stringify(user));

            // Redirección por rol
            switch (user.rol) {
                case 'gerente':
                case 'subgerente':
                    router.push('/admin/dashboard');
                    break;
                case 'socio_titular':
                case 'miembro_familiar':
                    router.push('/socio/home');
                    break;
                case 'instructor':
                    router.push('/instructor/home');
                    break;
                default:
                    errorMessage.value = 'Rol no reconocido.';
            }
        }
    } catch (error) {
        if (error.response && error.response.status === 401) {
            errorMessage.value = 'Credenciales incorrectas';
        } else {
            errorMessage.value = 'Error de conexión con el servidor';
        }
    } finally {
        isLoading.value = false;
    }
};
</script>

<template>
    <div class="auth-page">

        <main class="auth-container">

            <!-- Header -->
            <header class="auth-header">
                <h1>INICIAR SESIÓN</h1>
            </header>

            <section class="auth-card">

                <!-- Logo -->
                <article class="auth-logo-box">
                    <img src="@/assets/LogoSocDepHub.jpeg" alt="Logo" class="logo-img" />
                </article>

                <!-- Formulario -->
                <article class="auth-form-box">

                    <div class="auth-titles">
                        <h3>Bienvenido a Soc-Dep HUB</h3>
                        <p>Ingresa tus datos para comenzar</p>
                    </div>

                    <!-- Error -->
                    <div v-if="errorMessage" class="error-alert">
                        {{ errorMessage }}
                    </div>

                    <!-- FORM -->
                    <form @submit.prevent="handleLogin">

                        <input v-model="form.email" type="email" placeholder="Correo" />

                        <input v-model="form.password" type="password" placeholder="Contraseña" />

                        <button type="submit" :disabled="isLoading">
                            {{ isLoading ? 'Cargando...' : 'Iniciar Sesión' }}
                        </button>

                        <div class="divider">o</div>

                        <button type="button" class="btn-outline">
                            Iniciar sesión con Google
                        </button>

                        <button type="button" class="btn-outline">
                            Iniciar sesión con Apple
                        </button>

                    </form>

                </article>

            </section>
        </main>

    </div>
</template>

<style scoped>
/* RESET IMPORTANTE */
:global(html, body, #app) {
    height: 100%;
    margin: 0;
}

/* Layout general FULL SCREEN */
.auth-page {
    width: 100%;
    height: 100vh;
    display: flex;
    flex-direction: column;
    background: #f3f4f6;
}

/* Container ocupa todo */
.auth-container {
    flex: 1;
    display: flex;
    flex-direction: column;
    padding: 20px;
}

/* Header */
.auth-header {
    width: 100%;
    background: rgba(15, 23, 42, 1);
    padding: 10px;
    text-align: center;
    border-radius: 8px;
    margin-bottom: 20px;
}

.auth-header h1 {
    background: #d1d5db;
    padding: 10px;
    border-radius: 8px;
    margin: 0;
}

/* Card ocupa TODO el espacio restante */
.auth-card {
    flex: 1;
    width: 100%;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    background: white;
    border-radius: 12px;
    padding: 20px;
    gap: 20px;
}

/* Logo */
.auth-logo-box {
    text-align: center;
}

.logo-img {
    width: 500px;
    max-width: 300%;
    border-radius: 12px;
}

.logo-text {
    margin-top: 10px;
    font-size: 1.2rem;
}

/* Form */
.auth-form-box {
    width: 100%;
    max-width: 350px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.auth-titles {
    text-align: center;
}

/* Inputs */
input {
    width: 100%;
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 8px;
}

/* Botón */
button {
    width: 100%;
    padding: 10px;
    background: #2563eb;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
}

button:disabled {
    opacity: 0.6;
}

/* Divider */
.divider {
    text-align: center;
    margin: 10px 0;
}

/* Botones secundarios */
.btn-outline {
    background: white;
    border: 1px solid #ccc;
    color: black;
}

/* Error */
.error-alert {
    color: red;
    text-align: center;
}

/* ========================= */
/* 💻 DESKTOP RESPONSIVE */
/* ========================= */

@media (min-width: 768px) {
    .auth-card {
        flex-direction: row;
        justify-content: center;
        align-items: center;
        gap: 60px;
    }

    .auth-logo-box {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .auth-form-box {
        flex: 1;
        max-width: 400px;
    }
}
</style>
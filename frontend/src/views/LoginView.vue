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
                    <h2 class="logo-text">SOC-DEP HUB</h2>
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
/* Agrega aquí tus estilos base o de Tailwind para maquetar según los prototipos */
.error-alert {
  color: red;
  margin-bottom: 10px;
}
</style>
<script setup>
import { ref, reactive } from 'vue';

// Estado Reactivo (Composition API)
const form = reactive({
    email: '',
    password: ''
});

const errorMessage = ref('');
const isLoading = ref(false);

// Validación y Envío
const handleLogin = async () => {
    errorMessage.value = '';

    if (!form.email || !form.password) {
        errorMessage.value = 'Por favor, complete todos los campos.';
        return;
    }

    isLoading.value = true;

    try {
        // Simulación de autenticación
        console.log('Iniciando sesión con:', form);
        // Aquí iría tu lógica de axios.post('/api/login', form)
    } catch (error) {
        errorMessage.value = 'Credenciales incorrectas';
    } finally {
        isLoading.value = false;
    }
};
</script>

<template>

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>SOC-DEP HUB | Login</title>
        <!-- CSS Externo obligatorio -->
        <link rel="stylesheet" href="@/assets/stykes.css">
    </head>

    <body class="bg-gray flex-center auth-page">

        <main class="auth-container">
            <!-- Banner superior -->
            <header class="auth-header flex-center">
                <h1>INICIAR SESION</h1>
            </header>

            <section class="auth-card card flex-between">
                <!-- Columna Izquierda: Logo -->
                <article class="auth-logo-box flex-col flex-center">
                    <div class="logo-shield flex-center">
                        <img src="@/assets/LogoSocDepHub.jpeg" alt="Escudo de SOC-DEP HUB" width="120"
                            style="border-radius: 12px; object-fit: cover;">
                    </div>
                    <h2 class="logo-text">SOC-DEP HUB</h2>
                </article>

                <!-- Columna Derecha: Formulario -->
                <article class="auth-form-box flex-col">
                    <div class="auth-titles text-center">
                        <h3>Bienvenido a Soc-Dep HUB</h3>
                        <p class="text-muted">Ingresa tus datos para comenzar</p>
                    </div>

                    <!-- Formulario completo con validación básica HTML5 -->
                    <form action="#" method="POST" class="flex-col form-group">

                        <div class="input-wrapper">
                            <label for="correo" class="sr-only">Correo:</label>
                            <input type="email" id="correo" name="correo" placeholder="Correo: alexis@socdep.com"
                                required pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$">
                        </div>

                        <div class="input-wrapper">
                            <label for="password" class="sr-only">Contraseña:</label>
                            <input type="password" id="password" name="password"
                                placeholder="Contraseña: **************" required minlength="8">
                        </div>


                        <!-- Simulación de envío -->
                        <button type="submit" class="btn btn-primary">Iniciar Sesión</button>

                        <div class="divider flex-center">
                            <span>o</span>
                        </div>

                        <button type="button" class="btn btn-outline flex-center">
                            <img src="https://www.svgrepo.com/show/475656/google-color.svg" alt="Google Logo" width="18"
                                height="18" class="btn-icon">
                            Iniciar Sesión con Google
                        </button>

                        <button type="button" class="btn btn-outline flex-center mt-sm">
                            <img src="https://www.svgrepo.com/show/448234/apple.svg" alt="Apple Logo" width="18"
                                height="18" class="btn-icon">
                            Iniciar Sesión con Apple
                        </button>
                    </form>
                </article>
            </section>
        </main>

    </body>
</template>

<template>
  <RouterView />
</template>

<script setup>
import { RouterView } from 'vue-router'
</script>



<style>
/* Estilos globales (Tailwind ya incluye su propio reset 'Preflight', por lo que no es necesario resetear margin y padding aquí) */
body {
  font-family: var(--font-sans, Arial, sans-serif);
  background-color: #f4f4f9;
}
</style>
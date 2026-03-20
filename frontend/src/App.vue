<script setup>
// 1. Importamos el componente que acabas de crear
// (Asegúrate de que la ruta coincida con la carpeta donde lo guardaste)
import Nombres from './components/Names.vue'
</script>

<template>
  <main>
    <h1>Prueba de Conexión Frontend - Backend</h1>
    
    <Nombres />
    
  </main>
</template>

<style scoped>
main {
  padding: 2rem;
  font-family: Arial, sans-serif;
}
</style>
<template>
  <RouterView />
</template>

<script setup>
import { RouterView } from 'vue-router'
</script>



<style>
/* Estilos globales básicos para resetear márgenes */
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: Arial, sans-serif;
  background-color: #f4f4f9;
}
</style>
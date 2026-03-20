<template>
  <div class="contenedor-socdep">
    <h2>Nombres de Socios SOCDEP</h2>
    <ul>
      <li v-for="name in names" :key="name">
        {{ name }}
      </li>
    </ul>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios'; // La librería para hacer peticiones HTTP

// Declaramos nuestra variable reactiva vacía
const names = ref([]);

// onMounted = Justo cuando la pantalla cargue, ve a buscar los datos a Laravel
onMounted(async () => {
    try {
        const response = await axios.get('http://localhost:8000/api/names');
        names.value = response.data.names; // Llenamos la variable con el JSON
    } catch (error) {
        console.error("Error conectando con Laravel", error);
    }
});
</script>

<style scoped>
/* ¡AQUÍ ES DONDE DAS ESTILO! */
.contenedor-socdep {
  background-color: #f4f6f9;
  padding: 20px;
  border-radius: 8px;
  text-align: center;
  font-family: Arial, sans-serif;
}

h2 {
  color: #0056b3; /* Azul institucional */
}

li {
  list-style: none;
  font-size: 1.2rem;
  margin: 10px 0;
}
</style>
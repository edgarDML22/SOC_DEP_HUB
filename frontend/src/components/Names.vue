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
import axios from 'axios';

// Variable reactiva para guardar los nombres que lleguen del backend
const names = ref([]);

// Al cargar la pantalla, hacemos la petición a Laravel
onMounted(async () => {
    try {
        const response = await axios.get('http://localhost:8000/api/nombres');
        names.value = response.data.names; 
    } catch (error) {
        console.error("Error conectando con Laravel", error);
    }
});
</script>

<style scoped>
.contenedor-socdep {
  background-color: #f4f6f9;
  padding: 20px;
  border-radius: 8px;
  text-align: center;
  font-family: Arial, sans-serif;
}
h2 {
  color: #0056b3;
}
li {
  list-style: none;
  font-size: 1.2rem;
  margin: 10px 0;
}
</style>
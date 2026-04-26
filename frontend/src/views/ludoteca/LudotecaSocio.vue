<script setup>
import { useRouter } from "vue-router";
const router = useRouter()
</script>

<template>
  <div class="min-h-screen w-full bg-surface-50 font-sans p-4 md:p-6 lg:p-8 pb-24 md:pb-8 flex flex-col items-center">
    
    <div class="w-full max-w-5xl flex flex-col gap-6">
      <!-- HEADER CON BOTÓN -->
      <div>
        <button @click="router.back()" class="flex items-center gap-2 text-surface-500 hover:text-primary-600 transition-colors mb-4 font-medium text-sm w-fit group">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0 group-hover:-translate-x-1 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            Volver
        </button>
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-surface-200 pb-4">
            <div>
                <h2 class="text-2xl md:text-3xl font-extrabold text-surface-900 m-0 tracking-tight">Ludoteca</h2>
                <p class="text-surface-500 font-medium text-sm md:text-base m-0 mt-1">Gestiona los ingresos de tus pequeños</p>
            </div>
            <router-link class="px-5 py-2.5 bg-primary-600 hover:bg-primary-700 text-white font-bold rounded-xl shadow-sm transition-all w-full sm:w-auto text-center active:scale-95" :to="{ name: 'add-register' }">
              + Agregar registro
            </router-link>
        </div>
      </div>

      <!-- CONTENIDO -->
      <div class="w-full">
        <router-view />
      </div>

    </div>
  </div>
</template>
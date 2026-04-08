<script setup>
import InstructorNavBar from '@/components/instructor/InstructorNavBar.vue';
</script>

<template>
  <InstructorNavBar />
  <main class="main-content">
    <div class="page-header">
      <h1 class="page-title">Sesiones</h1>
      <p class="page-subtitle">Gestión de sesiones impartidas</p>
    </div>
    
    <div class="card empty-state">
      <p>Aquí aparecerá el historial y detalles de tus sesiones.</p>
    </div>
  </main>
</template>

<style scoped>
.main-content {
  padding: 2rem;
  padding-bottom: 90px;
  background-color: var(--p-surface-50, #f8fafc);
  min-height: 100vh;
  font-family: inherit;
}

.page-header {
  margin-bottom: 24px;
}

.page-title {
  font-size: 24px;
  font-weight: 700;
  margin: 0 0 4px 0;
  color: var(--p-surface-900, #111827);
}

.page-subtitle {
  font-size: 14px;
  color: var(--p-surface-500, #64748b);
  margin: 0;
}

.card {
  background-color: #ffffff;
  border: 1px solid var(--p-surface-200, #e2e8f0);
  border-radius: 12px;
  padding: 1.25rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.empty-state {
  text-align: center;
  padding: 3rem 1rem;
  color: var(--p-surface-500, #64748b);
}
</style>

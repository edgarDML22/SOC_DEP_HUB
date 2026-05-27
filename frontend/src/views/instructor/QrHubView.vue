<script setup>
import { onUnmounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useScannerStore } from '@/stores/profiles/scannerStore'
import HubHeader          from '@/components/instructor/scanner/HubHeader.vue'
import MenuCategorias     from '@/components/instructor/scanner/MenuCategorias.vue'
import ListaClases        from '@/components/instructor/scanner/ListaClases.vue'
import ListaReservaciones from '@/components/instructor/scanner/ListaReservaciones.vue'
import ListaEncuentros    from '@/components/instructor/scanner/ListaEncuentros.vue'
import SeleccionMetodo    from '@/components/instructor/scanner/SeleccionMetodo.vue'
import ScannerActivo      from '@/components/instructor/scanner/ScannerActivo.vue'
import ResultadoOutput    from '@/components/instructor/scanner/ResultadoOutput.vue'
import PaseLista          from '@/components/instructor/scanner/PaseLista.vue'

const store  = useScannerStore()
const router = useRouter()

// Ocultar "Volver" en OUTPUT exitoso — el botón "Listo" cierra el flujo
const ocultarVolver = computed(() =>
    store.paso === 'OUTPUT' && store.resultados[0]?.success === true
)

function handleVolver() {
    if (store.paso === 'MENU') {
        router.push('/instructor/home')
    } else {
        store.irAtras()
    }
}

onUnmounted(() => store.resetHub())
</script>

<template>
  <main class="w-full bg-surface-50 min-h-screen font-sans pb-24 md:pb-8">
    <div class="max-w-lg mx-auto p-4 md:p-8">

      <HubHeader
        :paso="store.paso"
        :ocultar-volver="ocultarVolver"
        :categoria="store.paso !== 'MENU' ? store.categoriaActiva : null"
        @volver="handleVolver"
      />

      <Transition name="hub-slide" mode="out-in">
        <MenuCategorias     v-if="store.paso === 'MENU'"                         :key="'menu'" />
        <ListaClases        v-else-if="store.paso === 'LIST_CLASES'"             :key="'list-clases'" />
        <ListaReservaciones v-else-if="store.paso === 'LIST_RESERVACIONES'"      :key="'list-reservas'" />
        <ListaEncuentros    v-else-if="store.paso === 'LIST_TORNEO'"             :key="'list-torneo'" />
        <SeleccionMetodo    v-else-if="store.paso === 'SELECCION_METODO'"        :key="'metodo'" />
        <ScannerActivo      v-else-if="store.paso === 'ESCANER_ACTIVO'"          :key="'escaner'" />
        <!-- PASE_LISTA: pase de lista completo para Mis Clases (cerradas y abiertas) -->
        <PaseLista          v-else-if="store.paso === 'PASE_LISTA'"              :key="'pase-lista'" />
        <!-- OUTPUT: resultado simple para Reservaciones y Torneos -->
        <ResultadoOutput    v-else-if="store.paso === 'OUTPUT'"                  :key="'output'" />
        <!-- fallback seguro -->
        <MenuCategorias     v-else :key="'fallback'" />
      </Transition>

    </div>
  </main>
</template>

<style scoped>
.hub-slide-enter-active,
.hub-slide-leave-active {
  transition: all 0.22s cubic-bezier(0.4, 0, 0.2, 1);
}
.hub-slide-enter-from { opacity: 0; transform: translateX(18px); }
.hub-slide-leave-to   { opacity: 0; transform: translateX(-18px); }
</style>

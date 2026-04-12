<script setup>
import { ref, watch } from 'vue'
import { useFriendStore } from '@/stores/community/friendStore'
import { useToast } from 'primevue/usetoast'
import api from '@/services/api'
//import { debounce } from 'lodash' // Se asume que lodash está instalado en el proyecto, o usamos setTimeout
import { useRouter } from 'vue-router'

const friendStore = useFriendStore()
const toast = useToast()
const router = useRouter()

const search = ref('')
const loadingSearch = ref(false)
const resultados = ref([])
const sendingId = ref(null) // Para controlar el estado de carga del botón de forma individual

let timeoutId = null

// Escuchar cambios en la búsqueda con un pequeño delay manual (debounce casero por seguridad si lodash falla)
watch(search, (newVal) => {
    if (timeoutId) clearTimeout(timeoutId)

    if (newVal.trim().length < 2) {
        resultados.value = []
        return
    }

    timeoutId = setTimeout(() => {
        buscarSocios(newVal)
    }, 500)
})

async function buscarSocios(query) {
    loadingSearch.value = true
    try {
        const response = await api.get(`/socios/search?query=${query}`)
        // Filtramos solo 'socio_titular' dado que la BD de amistades apunta a id_socio de titulares
        resultados.value = (response.data?.data || []).filter(item => item.tipo_perfil === 'socio_titular')
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo completar la búsqueda', life: 3000 })
    } finally {
        loadingSearch.value = false
    }
}

async function enviarSolicitud(socio) {
    sendingId.value = socio.id
    try {
        await friendStore.addFriend({ receptor_id: socio.id })
        toast.add({ severity: 'success', summary: 'Éxito', detail: `Solicitud enviada a ${socio.nombre}`, life: 3000 })

        // Redirigir de regreso a la lista
        router.push({ name: 'friends-list' })
    } catch (e) {
        const errorMsg = e.response?.data?.message || 'Hubo un error al enviar la solicitud'
        toast.add({ severity: 'error', summary: 'Ups...', detail: errorMsg, life: 4000 })
    } finally {
        sendingId.value = null
    }
}
</script>

<template>
    <div class="container">
        <div class="header">
            <div>
                <h2>Agregar Nuevo Amigo</h2>
                <p>Busca a otros socios por nombre para enviarles una solicitud.</p>
            </div>
            <router-link :to="{ name: 'friends-list' }" class="btn-cancel">
                Volver
            </router-link>
        </div>

        <div class="search-box">
            <input v-model="search" placeholder="Escribe el nombre del socio a buscar..." class="search" />
        </div>

        <!-- Indicador de carga -->
        <div v-if="loadingSearch" class="loading">
            Buscando socios...
        </div>

        <!-- Resultados -->
        <div v-else-if="search.length >= 2">
            <div v-if="resultados.length === 0" class="no-results text-muted">
                No se encontraron socios que coincidan con "{{ search }}".
            </div>

            <div v-else class="results-list">
                <div v-for="socio in resultados" :key="socio.id" class="card">
                    <div class="left">
                        <div class="avatar">{{ socio.nombre.charAt(0) }}</div>
                        <div>
                            <div class="nombre">{{ socio.nombre }}</div>
                            <div class="info">Socio Acción: {{ socio.numero_socio }}</div>
                        </div>
                    </div>

                    <div class="actions">
                        <button class="btn-primary" @click="enviarSolicitud(socio)" :disabled="sendingId === socio.id">
                            {{ sendingId === socio.id ? 'Enviando...' : 'Enviar Solicitud' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div v-else class="start-typing text-muted">
            Ingresa al menos 2 caracteres para comenzar la búsqueda.
        </div>
    </div>
</template>

<style scoped>
.container {
    padding: 20px;
    font-family: var(--p-font-family);
}

.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.header h2 {
    font-size: 22px;
    font-weight: 700;
    color: var(--p-surface-900);
}

.header p {
    color: var(--p-surface-500);
    font-size: 14px;
}

.search-box {
    margin-bottom: 16px;
}

.search {
    width: 100%;
    padding: 12px;
    border-radius: var(--p-border-radius-medium);
    border: 1px solid var(--p-surface-200);
    font-size: 16px;
    transition: box-shadow 0.2s, border-color 0.2s;
    outline: none;
}

.search:focus {
    border-color: var(--p-primary-500);
    box-shadow: 0 0 0 2px var(--p-primary-100);
}

.card {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: white;
    border-radius: var(--p-border-radius-medium);
    padding: 14px;
    margin-bottom: 12px;
    border: 1px solid var(--p-surface-200);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.card:hover {
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

.left {
    display: flex;
    gap: 12px;
}

.avatar {
    width: 42px;
    height: 42px;
    background: var(--p-surface-100);
    color: var(--p-primary-700);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    text-transform: uppercase;
}

.nombre {
    font-weight: 600;
    font-size: 15px;
    color: var(--p-surface-900);
}

.info {
    font-size: 13px;
    color: var(--p-surface-500);
}

.actions {
    display: flex;
    gap: 8px;
}

.btn-primary {
    background-color: var(--p-primary-700);
    color: white;
    border: none;
    padding: 8px 14px;
    border-radius: var(--p-border-radius-medium);
    cursor: pointer;
    font-weight: 500;
    transition: background-color 0.2s ease, opacity 0.2s ease;
}

.btn-primary:hover:not(:disabled) {
    background-color: var(--p-primary-800);
}

.btn-primary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.btn-cancel {
    background: white;
    color: var(--p-surface-900);
    border: 1px solid var(--p-surface-200);
    padding: 8px 16px;
    border-radius: var(--p-border-radius-medium);
    cursor: pointer;
    font-weight: 500;
    text-decoration: none;
    transition: background 0.2s ease;
}

.btn-cancel:hover {
    background: var(--p-surface-100);
}

.loading,
.no-results,
.start-typing {
    text-align: center;
    padding: 30px 20px;
    background: var(--p-surface-50);
    border-radius: var(--p-border-radius-medium);
    border: 1px dashed var(--p-surface-200);
}

.text-muted {
    color: var(--p-surface-500);
}
</style>

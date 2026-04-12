<script setup>
import { ref, onMounted, computed } from 'vue'
import { useFriendStore } from '@/stores/community/friendStore'
import { useToast } from 'primevue/usetoast'
import { IconMessage } from '@/components/icons'

const friendStore = useFriendStore()
const toast = useToast()

const search = ref('')
const showModal = ref(false)
const modalLoading = ref(false)
const filtro = ref('AMIGO') // AMIGO, SOLICITUD_ENVIADA, SOLICITUD_RECIBIDA

const selectedFriendId = ref(null)
const selectedFriendName = ref('')
const actionType = ref('') // 'eliminar_amigo' o 'cancelar_solicitud'

const amigosFiltrados = computed(() => {
    let list = Array.isArray(friendStore.friends) ? friendStore.friends : []

    // Filtro por pestaña (estado)
    if (filtro.value === 'AMIGO') {
        list = list.filter(f => f.estado === 'ACEPTADA')
    } else if (filtro.value === 'SOLICITUD_ENVIADA') {
        list = list.filter(f => f.estado === 'PENDIENTE' && f.solicitado_por_mi)
    } else if (filtro.value === 'SOLICITUD_RECIBIDA') {
        list = list.filter(f => f.estado === 'PENDIENTE' && !f.solicitado_por_mi)
    }

    // Filtro por búsqueda de texto (nombre o ID)
    if (search.value) {
        const lower = search.value.toLowerCase()
        list = list.filter(f =>
            f.nombre_amigo?.toLowerCase().includes(lower) ||
            String(f.id_amigo).toLowerCase().includes(lower)
        )
    }

    return list
})

onMounted(() => {
    friendStore.fetchFriends()
})

function cambiarFiltro(nuevoFiltro) {
    filtro.value = nuevoFiltro
}

function abrirModalEliminar(id_amistad, nombre, accion) {
    selectedFriendId.value = id_amistad
    selectedFriendName.value = nombre
    actionType.value = accion
    showModal.value = true
}

function cerrarModal() {
    showModal.value = false
    selectedFriendId.value = null
    selectedFriendName.value = ''
}

async function confirmarEliminar() {
    modalLoading.value = true
    try {
        await friendStore.removeFriend({ id_amistad: selectedFriendId.value })
        toast.add({
            severity: 'success',
            summary: 'Éxito',
            detail: actionType.value === 'eliminar_amigo' ? 'Amigo eliminado' : 'Solicitud cancelada',
            life: 3000
        })
        cerrarModal()
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Hubo un error al procesar la acción', life: 3000 })
    } finally {
        modalLoading.value = false
    }
}

async function aceptarSolicitud(id_amistad) {
    try {
        await friendStore.acceptFriend({ id_amistad })
        toast.add({ severity: 'success', summary: 'Éxito', detail: 'Solicitud aceptada', life: 3000 })
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Error al aceptar solicitud', life: 3000 })
    }
}

async function rechazarSolicitud(id_amistad) {
    try {
        await friendStore.rejectFriend({ id_amistad })
        toast.add({ severity: 'success', summary: 'Éxito', detail: 'Solicitud rechazada', life: 3000 })
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Error al rechazar solicitud', life: 3000 })
    }
}
</script>

<template>
    <div class="container">
        <div class="header">
            <div>
                <h2>Mis Amigos</h2>
                <p>Mantente conectado con las personas que más quieres</p>
            </div>

            <router-link class="btn-primary" :to="{ name: 'friends-add' }">
                +Agregar Amigo
            </router-link>
        </div>

        <input v-model="search" placeholder="Buscar amigo por nombre o ID..." class="search" />

        <div class="tabs">
            <button @click="cambiarFiltro('AMIGO')" :class="['tab', filtro === 'AMIGO' && 'active']">Mis Amigos</button>
            <button @click="cambiarFiltro('SOLICITUD_ENVIADA')"
                :class="['tab', filtro === 'SOLICITUD_ENVIADA' && 'active']">Solicitudes Enviadas</button>
            <button @click="cambiarFiltro('SOLICITUD_RECIBIDA')"
                :class="['tab', filtro === 'SOLICITUD_RECIBIDA' && 'active']">Solicitudes Recibidas</button>
        </div>

        <div v-if="friendStore.loading" class="loading">Cargando...</div>

        <div v-else>
            <div v-for="amigo in amigosFiltrados" :key="amigo.id_amistad" class="card">
                <div class="left">
                    <div class="avatar">{{ amigo.nombre_amigo?.charAt(0) || '?' }}</div>
                    <div>
                        <div class="nombre">
                            {{ amigo.nombre_amigo }}
                            <span v-if="amigo.estado === 'ACEPTADA'" class="badge activo">AMIGO</span>
                            <span v-else-if="amigo.estado === 'PENDIENTE' && amigo.solicitado_por_mi"
                                class="badge amarillo">ENVIADA</span>
                            <span v-else-if="amigo.estado === 'PENDIENTE' && !amigo.solicitado_por_mi"
                                class="badge expirado">RECIBIDA</span>
                        </div>
                        <div class="info" v-if="amigo.id_amigo">ID: {{ amigo.id_amigo }}</div>
                        <div class="info" v-if="amigo.created_at">Fecha: {{ new
                            Date(amigo.created_at).toLocaleDateString() }}</div>
                    </div>
                </div>

                <div class="actions">
                    <template v-if="filtro === 'AMIGO'">
                        <button class="btn-delete"
                            @click="abrirModalEliminar(amigo.id_amistad, amigo.nombre_amigo, 'eliminar_amigo')">Eliminar
                            Amigo</button>
                    </template>

                    <template v-else-if="filtro === 'SOLICITUD_ENVIADA'">
                        <button class="btn-revoke"
                            @click="abrirModalEliminar(amigo.id_amistad, amigo.nombre_amigo, 'cancelar_solicitud')">Cancelar</button>
                    </template>

                    <template v-else-if="filtro === 'SOLICITUD_RECIBIDA'">
                        <button class="btn-primary" @click="aceptarSolicitud(amigo.id_amistad)">Aceptar</button>
                        <button class="btn-delete" @click="rechazarSolicitud(amigo.id_amistad)">Rechazar</button>
                    </template>
                </div>
            </div>

            <div v-if="amigosFiltrados.length === 0" class="no-results text-muted">


                No se encontraron registros.
            </div>
        </div>

        <!-- Modal de Eliminación -->
        <div v-if="showModal" class="modal-overlay" @mousedown.self="cerrarModal">
            <div class="modal-card">
                <h3 class="text-danger">{{ actionType === 'eliminar_amigo' ? 'Eliminar Amigo' : 'Cancelar Solicitud' }}
                </h3>

                <div class="delete-warning">
                    <p>¿Estás seguro de que deseas {{ actionType === 'eliminar_amigo' ? 'eliminar a' : 'cancelar'
                        + 'solicitud'
                        }}
                        <strong>{{ selectedFriendName }}</strong>?
                    </p>
                    <p class="text-muted">Esta acción no se puede deshacer.</p>
                </div>

                <div class="modal-actions">
                    <button class="btn-cancel" @click="cerrarModal" :disabled="modalLoading">Cancelar</button>
                    <button class="btn-delete-confirm" @click="confirmarEliminar" :disabled="modalLoading">
                        {{ modalLoading ? 'Procesando...' : 'Sí, continuar' }}
                    </button>
                </div>
            </div>
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
    margin-bottom: 16px;
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

.search {
    width: 100%;
    padding: 10px;
    border-radius: var(--p-border-radius-medium);
    border: 1px solid var(--p-surface-200);
    margin-bottom: 12px;
}

.tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 16px;
}

.tab {
    flex: 1;
    padding: 10px;
    border-radius: var(--p-border-radius-medium);
    background: var(--p-surface-100);
    color: var(--p-surface-900);
    border: none;
    cursor: pointer;
    transition: background 0.2s ease;
}

.tab:hover {
    background: var(--p-surface-200);
}

.tab.active {
    background: var(--p-primary-100);
    color: var(--p-primary-700);
    font-weight: 600;
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
    flex-wrap: wrap;
}

/* BOTONES DE ACCIÓN */
.btn-delete {
    background: #fee2e2;
    border: none;
    padding: 6px 10px;
    border-radius: var(--p-border-radius-medium);
    color: #b91c1c;
    cursor: pointer;
    transition: background 0.2s ease;
}

.btn-delete:hover {
    background: #fecaca;
}

.btn-revoke {
    background: #fef08a;
    border: none;
    padding: 6px 10px;
    border-radius: var(--p-border-radius-medium);
    color: #854d0e;
    cursor: pointer;
    font-weight: 600;
    transition: 0.2s;
}

.btn-revoke:hover {
    background: #fde047;
}

.btn-primary {
    background-color: var(--p-primary-700);
    color: white;
    border: none;
    padding: 8px 14px;
    border-radius: var(--p-border-radius-medium);
    cursor: pointer;
    text-decoration: none;
    transition: background-color 0.2s ease;
}

.btn-primary:hover {
    background-color: var(--p-primary-800);
}

.badge {
    margin-left: 10px;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 600;
    background: transparent;
    border: 1.5px solid;
}

.badge.activo {
    color: #16a34a;
    border-color: #16a34a;
}

.badge.expirado {
    color: #dc2626;
    border-color: #dc2626;
}

.badge.amarillo {
    color: #ca8a04;
    border-color: #ca8a04;
}

.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.4);
    backdrop-filter: blur(2px);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1000;
}

.modal-card {
    background: white;
    padding: 24px;
    border-radius: var(--p-border-radius-medium);
    width: 90%;
    max-width: 400px;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.modal-card h3 {
    margin-top: 0;
    margin-bottom: 16px;
    font-size: 18px;
    color: var(--p-surface-900);
}

.text-danger {
    color: #dc2626;
}

.text-muted {
    color: var(--p-surface-500);
    font-size: 14px;
    margin-top: 4px;
}

.delete-warning p {
    margin: 0;
    font-size: 15px;
    color: var(--p-surface-900);
}

.modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    margin-top: 24px;
}

.btn-cancel {
    background: white;
    color: var(--p-surface-900);
    border: 1px solid var(--p-surface-200);
    padding: 8px 16px;
    border-radius: var(--p-border-radius-medium);
    cursor: pointer;
    font-weight: 500;
    transition: background 0.2s ease;
}

.btn-cancel:hover {
    background: var(--p-surface-100);
}

.btn-delete-confirm {
    background: #dc2626;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: var(--p-border-radius-medium);
    cursor: pointer;
    font-weight: 500;
    transition: background 0.2s ease;
}

.btn-delete-confirm:hover {
    background: #b91c1c;
}

.loading {
    text-align: center;
    padding: 20px;
}

.no-results {
    text-align: center;
    padding: 20px;
    border: 1px dashed var(--p-surface-200);
    border-radius: var(--p-border-radius-medium);
    margin-top: 10px;
}
</style>
<script setup>

import { ref, onMounted, computed } from 'vue'

import api from '@/services/api'

import { useProfileStore } from '@/stores/profiles/socioStore'

import Swal from 'sweetalert2'



const profileStore = useProfileStore()

const cancelingId = ref(null)

const invitados = ref([])

const filtro = ref('TODOS')

const search = ref('')

const loading = ref(false)



const idSocio = computed(() => {

  return profileStore.profileData?.id_socio || null

})



/**

 * SDH-2903: Propiedad computada para calcular la cuota actual (X/5)

 * Reacciona automáticamente cuando un pase cambia de estatus localmente.

 */

const activeCount = computed(() => {

  return invitados.value.filter(inv => inv.estatus_acceso === 'ACTIVO').length

})



const fetchInvitados = async () => {

  if (!idSocio.value) return



  loading.value = true



  try {

    let data = []



    if (filtro.value === 'TODOS') {

      const [activos, expirados] = await Promise.all([

        api.post('guest-status', { id: idSocio.value, status: 'ACTIVO' }),

        api.post('guest-status', { id: idSocio.value, status: 'EXPIRADO' })

      ])



      data = [

        ...(activos.data.data || []),

        ...(expirados.data.data || [])

      ]

    } else {

      const res = await api.post('guest-status', {

        id: idSocio.value,

        status: filtro.value

      })



      data = res.data.data || []

    }



    invitados.value = data

  } catch (error) {

    console.error(error)

  } finally {

    loading.value = false

  }

}



const handleCancel = async (inv) => {

  console.log(inv)

  const result = await Swal.fire({

    title: '¿Estás seguro?',

    text: `Se cancelará el pase de ${inv.nombre}. Esta acción liberará tu cuota de invitados.`,

    icon: 'warning',

    showCancelButton: true,

    confirmButtonColor: '#dc2626',

    cancelButtonColor: '#6b7280',

    confirmButtonText: 'Sí, cancelar pase',

    cancelButtonText: 'Regresar'

  })



  if (result.isConfirmed) {
    cancelingId.value = inv.id_pase

    try {

      await api.put(`guests/passes/${inv.id_pase}/cancel`)



      inv.estatus_acceso = 'EXPIRADO'



      Swal.fire(

        '¡Cancelado!',

        'El pase ha sido revocado correctamente.',

        'success'

      )

    } catch (error) {

      const errorMsg = error.response?.data?.message || 'No se pudo procesar la cancelación.'

      Swal.fire('Error', errorMsg, 'error')

    } finally {
      cancelingId.value = null
    }

  }

}



const cambiarFiltro = (nuevo) => {

  filtro.value = nuevo

  fetchInvitados()

}



const invitadosFiltrados = () => {

  if (!search.value) return invitados.value



  const s = search.value.toLowerCase()



  return invitados.value.filter(i =>

    i.nombre.toLowerCase().includes(s) ||

    String(i.id).includes(s)

  )

}



onMounted(fetchInvitados)

</script>



<template>

  <div class="container">



    <div class="header">

      <div>

        <h2>Mis Invitados</h2>

        <p>Consulta el estatus de tus invitados</p>

        <div class="quota-info" :class="{ 'limit-reached': activeCount >= 5 }">

           Cupo utilizado: <strong>{{ activeCount }} / 5</strong>

        </div>

      </div>

    </div>



    <input

      v-model="search"

      placeholder="Buscar invitado por nombre o ID..."

      class="search"

    />



    <div class="tabs">

      <button @click="cambiarFiltro('TODOS')" :class="['tab', filtro === 'TODOS' && 'active']">Todos</button>

      <button @click="cambiarFiltro('ACTIVO')" :class="['tab', filtro === 'ACTIVO' && 'active']">Activos</button>

      <button @click="cambiarFiltro('EXPIRADO')" :class="['tab', filtro === 'EXPIRADO' && 'active']">Cancelados</button>

    </div>



    <div v-if="loading" class="loading">Cargando...</div>



    <div v-else>

      <div v-for="inv in invitadosFiltrados()" :key="inv.id" class="card">



        <div class="left">

          <div class="avatar">

            {{ inv.nombre.charAt(0) }}

          </div>



          <div>

            <div class="nombre">

              {{ inv.nombre }}



              <span

                class="badge"

                :class="{

                  activo: inv.estatus_acceso === 'ACTIVO',

                  expirado: inv.estatus_acceso === 'EXPIRADO'

                }"

              >

                {{

                  inv.estatus_acceso === 'ACTIVO'

                    ? 'Activo'

                    : 'Cancelado'

                }}

              </span>

            </div>



            <div class="info">ID: {{ inv.id }}</div>

            <div class="info" v-if="inv.telefono">{{ inv.telefono }}</div>

            <div class="info" v-if="inv.correo">{{ inv.correo }}</div>

            <div class="info" v-if="inv.fecha_expiracion">

               Expira: {{ inv.fecha_expiracion }}

            </div>

          </div>

        </div>



        <div class="actions">

          <button

            v-if="inv.estatus_acceso === 'ACTIVO'"

            class="btn-delete"

            @click="handleCancel(inv)"
            :disabled="cancelingId === inv.id_pase"

          >

           {{ cancelingId === inv.id_pase ? 'Cancelando...' : 'Cancelar Pase' }}

          </button>

          <span v-else class="info-text">Sin acciones</span>

        </div>



      </div>

    </div>



  </div>

</template>



<style scoped>



.container { padding: 20px; }

.header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }

.header h2 { font-size: 22px; font-weight: 700; }

.header p { color: #6b7280; font-size: 14px; }





.quota-info { margin-top: 8px; font-size: 14px; color: #2563eb; }

.limit-reached { color: #dc2626; }



.search { width: 100%; padding: 10px; border-radius: 10px; border: 1px solid #e5e7eb; margin-bottom: 12px; }



.tabs { display: flex; gap: 10px; margin-bottom: 16px; }

.tab { flex: 1; padding: 10px; border-radius: 10px; background: #f3f4f6; border: none; cursor: pointer; }

.tab.active { background: #dbeafe; color: #1d4ed8; font-weight: 600; }



.card { display: flex; justify-content: space-between; align-items: center; background: white; border-radius: 12px; padding: 14px; margin-bottom: 12px; border: 1px solid #e5e7eb; }

.left { display: flex; gap: 12px; }

.avatar { width: 42px; height: 42px; background: #dbeafe; color: #1d4ed8; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; }

.nombre { font-weight: 600; font-size: 15px; }

.info { font-size: 13px; color: #6b7280; }

.info-text { font-size: 12px; color: #9ca3af; font-style: italic; }



.badge { margin-left: 10px; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; background: transparent; border: 1.5px solid; }

.badge.activo { color: #16a34a; border-color: #16a34a; }

.badge.expirado { color: #dc2626; border-color: #dc2626; }



.actions { display: flex; gap: 8px; }

.btn-delete { background: #fee2e2; border: none; padding: 8px 12px; border-radius: 8px; color: #b91c1c; cursor: pointer; font-weight: 500; transition: 0.2s; }

.btn-delete:hover { background: #fecaca; }

.btn-delete:disabled {
  background: #fca5a5;
  cursor: not-allowed;
  opacity: 0.7;
}



.loading { text-align: center; padding: 20px; }

</style>
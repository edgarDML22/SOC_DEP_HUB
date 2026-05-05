<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { storeToRefs } from 'pinia';
import api from '@/services/api';
import { useInstructorStore } from '@/stores/admin/instructorStore';
import LoadingSpinner from '@/components/gerente/ui/LoadingSpinner.vue';
import ConfirmButton from '@/components/gerente/ui/ConfirmButton.vue'
import CancelButton from '@/components/gerente/ui/CancelButton.vue'

const route = useRoute();
const router = useRouter();
const instructorStore = useInstructorStore();

const { isLoading, error: storeError } = storeToRefs(instructorStore);

const instructorId = route.params.id;

const instructor = ref(null);
const errorMsg = ref('');

// Modales
const showEditModal = ref(false);
const isSaving = ref(false);

const editForm = ref({
    nombre_completo: '',
    telefono: '',
    correo_electronico: '',
    fecha_nacimiento: '',
    hora_entrada: '',
    hora_salida: ''
});

onMounted(async () => {
    await fetchInstructorDetails();
});



const fetchInstructorDetails = async () => {
    errorMsg.value = '';
    try {
        const data = await instructorStore.fetchInstructorDetails(instructorId);
        instructor.value = data;
        // Popular formulario de edición
        editForm.value = {
            nombre_completo: data.nombre_completo,
            telefono: data.telefono || '',
            correo_electronico: data.correo_electronico || '',
            fecha_nacimiento: data.fecha_nacimiento || '',
            hora_entrada: data.hora_entrada || '',
            hora_salida: data.hora_salida || ''
        };
    } catch (error) {
        console.error("Error cargando detalles del instructor:", error);
        errorMsg.value = "Hubo un problema al cargar los detalles del instructor.";
    }
};



const saveEditInstructor = async () => {
    if (!editForm.value.nombre_completo) {
        alert("El nombre es requerido.");
        return;
    }

    isSaving.value = true;
    try {
        const res = await instructorStore.updateInstructor(instructorId, editForm.value);
        if (res.success) {
            showEditModal.value = false;
            // Al ser reactivo el store y nosotros usar instructor.value = data en fetch,
            // y el store actualizar la lista, deberíamos refrescar la referencia local.
            instructor.value = instructorStore.getInstructorById(instructorId);
        } else {
            alert(res.error || "Ocurrió un error al actualizar.");
        }
    } catch (error) {
        console.error("Error al actualizar instructor:", error);
    } finally {
        isSaving.value = false;
    }
};



const goBack = () => {
    router.push('/admin/instructors');
};
</script>

<template>
    <main class="min-h-screen bg-surface-50 p-6 lg:p-8 pb-20 font-sans">
        <div class="max-w-4xl mx-auto space-y-6">

            <!-- Navegación Superior -->
            <header class="flex items-center gap-4 mb-8">
                <button @click="goBack" class="w-11 h-11 bg-white border border-surface-200 rounded-xl flex items-center justify-center text-surface-600 hover:bg-surface-50 hover:text-primary-600 transition-all shadow-sm group">
                    <svg class="w-5 h-5 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                </button>
                <h1 class="text-2xl font-black text-surface-900 tracking-tight">Detalles del Instructor</h1>
            </header>

            <!-- Estado de Error -->
            <section v-if="errorMsg || storeError" class="text-center p-12 bg-red-50 rounded-4xl border border-red-100 animate-scale-in">
                <p class="text-red-600 font-bold">{{ errorMsg || storeError }}</p>
                <CancelButton label="Volver a los Instructores" @click="goBack" class="mt-4" />
            </section>

            <!-- Estado de Carga -->
            <section v-if="isLoading" class="flex flex-col items-center justify-center p-20">
                <LoadingSpinner />
                <p class="text-sm font-extrabold uppercase tracking-widest text-surface-400 mt-4">Cargando detalles...</p>
            </section>

            <!-- Contenido Detallado -->
            <Transition enter-active-class="transition-all duration-500 ease-out" enter-from-class="opacity-0 translate-y-4 scale-[0.98]" enter-to-class="opacity-100 translate-y-0 scale-100">
                <section v-if="instructor && !isLoading">
                    <!-- Tarjeta Principal Visual -->
                    <article class="bg-white rounded-[2.5rem] shadow-xl shadow-surface-200/40 border border-surface-200 overflow-hidden relative">
                        <div class="absolute top-0 left-0 right-0 h-2 bg-linear-to-r from-primary-600 to-primary-400"></div>

                        <div class="p-8 lg:p-10">
                            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
                                <div>
                                    <h2 class="text-3xl font-black text-surface-900 leading-tight mb-2">{{ instructor.nombre_completo }}</h2>
                                    <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-6 text-surface-500 font-medium">
                                        <p class="flex items-center gap-2">
                                            <svg class="w-4 h-4 text-primary-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l2.19-2.19a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z" /></svg>
                                            {{ instructor.telefono || 'Sin teléfono' }}
                                        </p>
                                        <p class="flex items-center gap-2">
                                            <svg class="w-4 h-4 text-primary-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" /><polyline points="22,6 12,13 2,6" /></svg>
                                            {{ instructor.correo_electronico || 'Sin correo' }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex flex-col items-end gap-2">
                                    <span class="px-4 py-1.5 rounded-xl text-xs font-black uppercase tracking-widest" :class="{
                                        'bg-green-50 text-green-700 border border-green-200': instructor.estatus === 'ACTIVO' || instructor.estatus === 'Activo',
                                        'bg-red-50 text-red-700 border border-red-200': instructor.estatus === 'INACTIVO' || instructor.estatus === 'Inactivo',
                                        'bg-amber-50 text-amber-700 border border-amber-200': instructor.estatus === 'BAJA_TEMPORAL'
                                    }">
                                        {{ instructor.estatus }}
                                    </span>
                                    <span class="text-xs font-bold text-surface-400 uppercase tracking-widest bg-surface-50 px-3 py-1 rounded-lg border border-surface-100">
                                        ID: #{{ instructor.id_instructor }}
                                    </span>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mt-8">
                                <div class="bg-surface-50/50 rounded-2xl p-4 border border-surface-100 flex flex-col gap-1.5">
                                    <span class="text-[10px] font-extrabold uppercase tracking-widest text-surface-500">Fecha de Contratación</span>
                                    <span class="text-base font-bold text-surface-900">{{ instructor.fecha_afiliacion || 'N/A' }}</span>
                                </div>
                                <div class="bg-surface-50/50 rounded-2xl p-4 border border-surface-100 flex flex-col gap-1.5">
                                    <span class="text-[10px] font-extrabold uppercase tracking-widest text-surface-500">Fecha de Nacimiento</span>
                                    <span class="text-base font-bold text-surface-900">{{ instructor.fecha_nacimiento || 'N/A' }}</span>
                                </div>
                                <div class="bg-surface-50/50 rounded-2xl p-4 border border-surface-100 flex flex-col gap-1.5">
                                    <span class="text-[10px] font-extrabold uppercase tracking-widest text-surface-500">Hora Entrada</span>
                                    <span class="text-base font-bold text-surface-900">{{ instructor.hora_entrada || 'N/A' }}</span>
                                </div>
                                <div class="bg-surface-50/50 rounded-2xl p-4 border border-surface-100 flex flex-col gap-1.5">
                                    <span class="text-[10px] font-extrabold uppercase tracking-widest text-surface-500">Hora Salida</span>
                                    <span class="text-base font-bold text-surface-900">{{ instructor.hora_salida || 'N/A' }}</span>
                                </div>
                            </div>

                            <div class="mt-8 pt-6 border-t border-surface-100 flex justify-end">
                                <ConfirmButton
                                    label="Gestionar Información Personal"
                                    @click="showEditModal = true"
                                    class="w-full sm:w-auto"
                                />
                            </div>
                        </div>
                    </article>
                </section>
            </Transition>
        </div>

        <!-- MODAL EDITAR INSTRUCTOR -->
        <Teleport to="body">
            <Transition enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition-all duration-200 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-surface-900/60 backdrop-blur-sm" @click.self="showEditModal = false">
                    <Transition enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0 scale-95 translate-y-4" enter-to-class="opacity-100 scale-100 translate-y-0">
                        <div v-if="showEditModal" class="bg-white rounded-[2.5rem] w-full max-w-xl shadow-2xl overflow-hidden flex flex-col max-h-[92vh]">
                            
                            <!-- Cabecera -->
                            <div class="flex justify-between items-center px-8 py-6 bg-white border-b border-surface-100">
                                <div>
                                    <h2 class="text-xl font-black text-surface-900">Editar Instructor</h2>
                                    <p class="text-xs font-bold text-surface-500 mt-1 uppercase tracking-wider">Modifica la información personal</p>
                                </div>
                                <button @click="showEditModal = false" class="w-10 h-10 rounded-xl bg-surface-100 hover:bg-surface-200 flex items-center justify-center text-surface-500 transition-colors">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                </button>
                            </div>

                            <!-- Cuerpo -->
                            <div class="p-8 overflow-y-auto space-y-6 bg-surface-50/50">
                                <div class="space-y-1.5">
                                    <label class="text-[10px] font-black uppercase tracking-widest text-surface-500 px-1">Nombre Completo <span class="text-red-400">*</span></label>
                                    <input type="text" v-model="editForm.nombre_completo" class="w-full px-4 py-3.5 bg-white border border-surface-200 rounded-xl text-sm font-bold text-surface-900 focus:ring-2 focus:ring-primary-500/40 focus:border-primary-400 transition-all outline-none" />
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="space-y-1.5">
                                        <label class="text-[10px] font-black uppercase tracking-widest text-surface-500 px-1">Teléfono</label>
                                        <input type="text" v-model="editForm.telefono" class="w-full px-4 py-3.5 bg-white border border-surface-200 rounded-xl text-sm font-bold text-surface-900 focus:ring-2 focus:ring-primary-500/40 focus:border-primary-400 transition-all outline-none" />
                                    </div>
                                    <div class="space-y-1.5">
                                        <label class="text-[10px] font-black uppercase tracking-widest text-surface-500 px-1">Fecha de Nacimiento</label>
                                        <input type="date" v-model="editForm.fecha_nacimiento" class="w-full px-4 py-3.5 bg-white border border-surface-200 rounded-xl text-sm font-bold text-surface-900 focus:ring-2 focus:ring-primary-500/40 focus:border-primary-400 transition-all outline-none" />
                                    </div>
                                </div>

                                <div class="space-y-1.5">
                                    <label class="text-[10px] font-black uppercase tracking-widest text-surface-500 px-1">Correo Electrónico</label>
                                    <input type="email" v-model="editForm.correo_electronico" class="w-full px-4 py-3.5 bg-white border border-surface-200 rounded-xl text-sm font-bold text-surface-900 focus:ring-2 focus:ring-primary-500/40 focus:border-primary-400 transition-all outline-none" />
                                </div>

                                <div class="grid grid-cols-2 gap-6">
                                    <div class="space-y-1.5">
                                        <label class="text-[10px] font-black uppercase tracking-widest text-surface-500 px-1">Hora Entrada</label>
                                        <input type="time" v-model="editForm.hora_entrada" class="w-full px-4 py-3.5 bg-white border border-surface-200 rounded-xl text-sm font-bold text-surface-900 focus:ring-2 focus:ring-primary-500/40 focus:border-primary-400 transition-all outline-none" />
                                    </div>
                                    <div class="space-y-1.5">
                                        <label class="text-[10px] font-black uppercase tracking-widest text-surface-500 px-1">Hora Salida</label>
                                        <input type="time" v-model="editForm.hora_salida" class="w-full px-4 py-3.5 bg-white border border-surface-200 rounded-xl text-sm font-bold text-surface-900 focus:ring-2 focus:ring-primary-500/40 focus:border-primary-400 transition-all outline-none" />
                                    </div>
                                </div>
                            </div>

                            <!-- Pie -->
                            <div class="px-8 py-5 bg-white border-t border-surface-100 flex items-center justify-end gap-3">
                                <CancelButton @click="showEditModal = false" />
                                <ConfirmButton
                                    label="Guardar Cambios"
                                    :loading="isSaving"
                                    @click="saveEditInstructor"
                                />
                            </div>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>
    </main>
</template>
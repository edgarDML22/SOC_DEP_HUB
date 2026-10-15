<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { storeToRefs } from 'pinia';
import api from '@/services/api';
import { useInstructorStore } from '@/stores/admin/instructorStore';
import LoadingSpinner from '@/components/gerente/ui/LoadingSpinner.vue';
import BadgeStatus from '@/components/gerente/ui/BadgeStatus.vue';
import ConfirmButton from '@/components/gerente/ui/ConfirmButton.vue'
import CancelButton from '@/components/gerente/ui/CancelButton.vue'
import { IconPhone, IconClock } from '@/components/icons'
import { useformat } from '@/utils/formatters';
import { useAlerts } from '@/composables/useAlerts';

const route = useRoute();
const router = useRouter();
const instructorStore = useInstructorStore();
const { formatText, dateFormat } = useformat();
const { toastInfo } = useAlerts();

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
    if (instructorStore.currentInstructor && String(instructorStore.currentInstructor.id_instructor) === String(instructorId)) {
        instructor.value = instructorStore.currentInstructor;
        // Popular formulario de edición
        editForm.value = {
            nombre_completo: instructor.value.nombre_completo,
            telefono: instructor.value.telefono || '',
            correo_electronico: instructor.value.correo_electronico || '',
            fecha_nacimiento: instructor.value.fecha_nacimiento || '',
            hora_entrada: instructor.value.hora_entrada || '',
            hora_salida: instructor.value.hora_salida || ''
        };
    }
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
        toastInfo("Campo requerido", "El nombre es requerido.", "error");
        return;
    }

    isSaving.value = true;
    try {
        const res = await instructorStore.updateInstructor(instructorId, editForm.value);
        if (res.success) {
            toastInfo("¡Éxito!", "Información del instructor actualizada correctamente.", "success");
            showEditModal.value = false;
            // Al ser reactivo el store y nosotros usar instructor.value = data en fetch,
            // y el store actualizar la lista, deberíamos refrescar la referencia local.
            instructor.value = instructorStore.getInstructorById(instructorId);
        } else {
            toastInfo("Error", res.error || "Ocurrió un error al actualizar.", "error");
        }
    } catch (error) {
        console.error("Error al actualizar instructor:", error);
    } finally {
        isSaving.value = false;
    }
};

// ── AVATAR (igual que en la lista) ────────────────────────────
const GRADIENTS = [
    'from-indigo-400 to-indigo-600',
    'from-emerald-400 to-emerald-600',
    'from-purple-400 to-purple-600',
    'from-sky-400 to-sky-600',
    'from-rose-400 to-rose-600',
    'from-amber-400 to-amber-600',
]
const avatarGradient = (name = '') => GRADIENTS[(name.charCodeAt(0) ?? 0) % GRADIENTS.length]
const initials = (name = '') => {
    const parts = name.trim().split(' ').filter(Boolean)
    return parts.length >= 2
        ? (parts[0][0] + parts[1][0]).toUpperCase()
        : (parts[0]?.[0] ?? '?').toUpperCase()
}

const goBack = () => {
    router.push('/admin/instructors');
};
</script>

<template>
    <main class="min-h-screen bg-surface-50 p-4 sm:p-6 lg:p-8 pb-20 font-sans">
        <div class="max-w-4xl mx-auto space-y-6">

            <!-- Navegación Superior -->
            <header class="flex items-center gap-4 mb-8">
                <button @click="goBack"
                    class="w-11 h-11 shrink-0 bg-white border border-surface-200 rounded-xl flex items-center justify-center text-surface-600 hover:bg-surface-50 hover:text-primary-600 transition-all shadow-sm group">
                    <svg class="w-5 h-5 group-hover:-translate-x-1 transition-transform" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                </button>
                <h1 class="text-xl sm:text-2xl font-black text-surface-900 tracking-tight">Detalles del Instructor</h1>
            </header>

            <!-- Estado de Error -->
            <section v-if="errorMsg || storeError"
                class="text-center p-12 bg-red-50 rounded-4xl border border-red-100 animate-scale-in">
                <p class="text-red-600 font-bold">{{ errorMsg || storeError }}</p>
                <button @click="goBack"
                    class="mt-4 px-6 py-2.5 bg-white border border-red-200 text-red-600 rounded-xl font-bold hover:bg-red-50 transition-colors shadow-sm">Volver
                    a los Instructores</button>
            </section>

            <!-- Estado de Carga -->
            <section v-if="isLoading" class="flex flex-col items-center justify-center p-20">
                <LoadingSpinner />
                <p class="text-sm font-extrabold uppercase tracking-widest text-surface-400 mt-4">Cargando detalles...
                </p>
            </section>

            <!-- Contenido Detallado -->
            <Transition enter-active-class="transition-all duration-500 ease-out"
                enter-from-class="opacity-0 translate-y-4 scale-[0.98]"
                enter-to-class="opacity-100 translate-y-0 scale-100">
                <section v-if="instructor && !isLoading">

                    <!-- TARJETA DESKTOP -->
                    <div class="hidden md:block">
                        <article
                            class="bg-white rounded-[2.5rem] shadow-xl shadow-surface-200/40 border border-surface-200 overflow-hidden relative">
                            <div
                                class="absolute top-0 left-0 right-0 h-2 bg-linear-to-r from-primary-600 to-primary-400">
                            </div>

                            <div class="p-8 lg:p-10">
                                <div
                                    class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
                                    <div>
                                        <h2 class="text-3xl font-black text-surface-900 leading-tight mb-2">{{
                                            instructor.nombre_completo }}</h2>
                                        <div
                                            class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-6 text-surface-500 font-medium">
                                            <p class="flex items-center gap-2">
                                                <svg class="w-4 h-4 text-primary-500" viewBox="0 0 24 24" fill="none"
                                                    stroke="currentColor" stroke-width="2.5">
                                                    <path
                                                        d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l2.19-2.19a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z" />
                                                </svg>
                                                {{ instructor.telefono || 'Sin teléfono' }}
                                            </p>
                                            <p class="flex items-center gap-2">
                                                <svg class="w-4 h-4 text-primary-500" viewBox="0 0 24 24" fill="none"
                                                    stroke="currentColor" stroke-width="2.5">
                                                    <path
                                                        d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" />
                                                    <polyline points="22,6 12,13 2,6" />
                                                </svg>
                                                {{ instructor.correo_electronico || 'Sin correo' }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex flex-col items-end gap-2">
                                        <BadgeStatus :status="instructor.estatus" size="md" />
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mt-8">
                                    <div
                                        class="bg-surface-50/50 rounded-2xl p-4 border border-surface-100 flex flex-col gap-1.5">
                                        <span
                                            class="text-[10px] font-extrabold uppercase tracking-widest text-surface-500">Fecha
                                            de Contratación</span>
                                        <span class="text-base font-bold text-surface-900">{{
                                            dateFormat(instructor.fecha_afiliacion) || 'N/A' }}</span>
                                    </div>
                                    <div
                                        class="bg-surface-50/50 rounded-2xl p-4 border border-surface-100 flex flex-col gap-1.5">
                                        <span
                                            class="text-[10px] font-extrabold uppercase tracking-widest text-surface-500">Fecha
                                            de Nacimiento</span>
                                        <span class="text-base font-bold text-surface-900">{{
                                            dateFormat(instructor.fecha_nacimiento) || 'N/A' }}</span>
                                    </div>
                                    <div
                                        class="bg-surface-50/50 rounded-2xl p-4 border border-surface-100 flex flex-col gap-1.5">
                                        <span
                                            class="text-[10px] font-extrabold uppercase tracking-widest text-surface-500">Hora
                                            Entrada</span>
                                        <span class="text-base font-bold text-surface-900">{{ instructor.hora_entrada ||
                                            'N/A' }}</span>
                                    </div>
                                    <div
                                        class="bg-surface-50/50 rounded-2xl p-4 border border-surface-100 flex flex-col gap-1.5">
                                        <span
                                            class="text-[10px] font-extrabold uppercase tracking-widest text-surface-500">Hora
                                            Salida</span>
                                        <span class="text-base font-bold text-surface-900">{{ instructor.hora_salida ||
                                            'N/A' }}</span>
                                    </div>
                                </div>

                                <div class="mt-8 pt-6 border-t border-surface-100 flex justify-end">
                                    <ConfirmButton label="Gestionar Información Personal" @click="showEditModal = true"
                                        class="w-full sm:w-auto" />
                                </div>
                            </div>
                        </article>
                    </div>

                    <!-- TARJETA MÓVIL -->
                    <article class="md:hidden bg-white border border-surface-200 rounded-2xl shadow-sm">

                        <!-- Encabezado: avatar, nombre y correo -->
                        <div class="flex items-center gap-3 px-4 py-3">
                            <div class="w-10 h-10 rounded-xl bg-linear-to-br flex items-center justify-center text-white font-black text-xs shrink-0 shadow-sm"
                                :class="avatarGradient(instructor.nombre_completo)">
                                {{ initials(instructor.nombre_completo) }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-bold text-surface-900 truncate leading-tight">
                                    {{ instructor.nombre_completo }}
                                </p>
                                <p class="text-xs text-surface-400 truncate">
                                    {{ instructor.correo_electronico ?? 'Sin correo' }}
                                </p>
                            </div>
                        </div>

                        <!-- Cuerpo: estatus, horario y teléfono -->
                        <div class="px-4 py-3 border-t border-surface-100 space-y-3">
                            <div class="flex items-center justify-between gap-3">
                                <BadgeStatus :status="instructor.estatus" />
                                <span v-if="instructor.hora_entrada || instructor.hora_salida"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-surface-50 text-surface-700 border border-surface-200/50">
                                    <IconClock class="w-3.5 h-3.5 text-surface-400 shrink-0" />
                                    {{ instructor.hora_entrada?.substring(0, 5) ?? '--' }} – {{
                                        instructor.hora_salida?.substring(0, 5) ?? '--' }}
                                </span>
                                <span v-else class="text-xs font-medium text-surface-400">Sin horario</span>
                            </div>

                            <a v-if="instructor.telefono" :href="`tel:${instructor.telefono}`"
                                class="inline-flex items-center gap-2 text-sm font-semibold text-primary-600 hover:underline">
                                <IconPhone class="w-4 h-4 shrink-0" />
                                {{ instructor.telefono }}
                            </a>
                            <span v-else class="inline-flex items-center gap-2 text-sm text-surface-400">
                                <IconPhone class="w-4 h-4 shrink-0" />
                                Sin teléfono
                            </span>
                        </div>

                        <!-- Fechas -->
                        <div class="px-4 py-3 border-t border-surface-100 space-y-2">
                            <div class="flex items-center justify-between text-sm">
                                <span class="font-semibold text-surface-500">Contratación</span>
                                <span class="font-bold text-surface-900">{{ dateFormat(instructor.fecha_afiliacion) ||
                                    'N/A' }}</span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="font-semibold text-surface-500">Nacimiento</span>
                                <span class="font-bold text-surface-900">{{ dateFormat(instructor.fecha_nacimiento) ||
                                    'N/A' }}</span>
                            </div>
                        </div>

                        <!-- Pie: acción -->
                        <div class="px-4 py-3 border-t border-surface-100">
                            <ConfirmButton label="Gestionar Información Personal" @click="showEditModal = true"
                                class="w-full" />
                        </div>
                    </article>

                </section>
            </Transition>
        </div>

        <!-- MODAL EDITAR INSTRUCTOR -->
        <Teleport to="body">
            <Transition enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0"
                enter-to-class="opacity-100" leave-active-class="transition-all duration-200 ease-in"
                leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="showEditModal"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-surface-900/60 backdrop-blur-sm"
                    @click.self="showEditModal = false">
                    <Transition enter-active-class="transition-all duration-300 ease-out"
                        enter-from-class="opacity-0 scale-95 translate-y-4"
                        enter-to-class="opacity-100 scale-100 translate-y-0">
                        <div v-if="showEditModal"
                            class="bg-white rounded-2xl sm:rounded-4xl w-full max-w-xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">

                            <!-- Cabecera -->
                            <div
                                class="flex justify-between items-center px-5 sm:px-8 py-5 sm:py-6 bg-white border-b border-surface-100">
                                <div>
                                    <h2 class="text-lg font-black text-surface-900">Editar Instructor</h2>
                                    <p class="text-xs font-medium text-surface-500 mt-1 uppercase tracking-wider">
                                        Modifica
                                        la información personal</p>
                                </div>
                                <button @click="showEditModal = false"
                                    class="w-9 h-9 rounded-xl bg-surface-100 hover:bg-surface-200 flex items-center justify-center text-surface-500 transition-colors">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Cuerpo -->
                            <div class="p-5 sm:p-6 overflow-y-auto space-y-5 sm:space-y-6 bg-surface-50/30">
                                <div class="space-y-1.5">
                                    <label
                                        class="text-[10px] font-black uppercase tracking-widest text-surface-400 px-1">Nombre
                                        Completo <span class="text-red-400">*</span></label>
                                    <input type="text" v-model="editForm.nombre_completo"
                                        class="w-full px-4 py-3.5 sm:py-3 bg-white border border-surface-200 rounded-xl text-sm font-medium text-surface-900 focus:ring-2 focus:ring-blue-500/40 focus:border-blue-400 transition-all outline-none" />
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 sm:gap-6">
                                    <div class="space-y-1.5">
                                        <label
                                            class="text-[10px] font-black uppercase tracking-widest text-surface-400 px-1">Teléfono</label>
                                        <input type="text" v-model="editForm.telefono"
                                            class="w-full px-4 py-3.5 sm:py-3 bg-white border border-surface-200 rounded-xl text-sm font-medium text-surface-900 focus:ring-2 focus:ring-blue-500/40 focus:border-blue-400 transition-all outline-none" />
                                    </div>
                                    <div class="space-y-1.5">
                                        <label
                                            class="text-[10px] font-black uppercase tracking-widest text-surface-400 px-1">Fecha
                                            de Nacimiento</label>
                                        <input type="date" v-model="editForm.fecha_nacimiento"
                                            class="w-full px-4 py-3.5 sm:py-3 bg-white border border-surface-200 rounded-xl text-sm font-medium text-surface-900 focus:ring-2 focus:ring-blue-500/40 focus:border-blue-400 transition-all outline-none" />
                                    </div>
                                </div>

                                <div class="space-y-1.5">
                                    <label
                                        class="text-[10px] font-black uppercase tracking-widest text-surface-400 px-1">Correo
                                        Electrónico</label>
                                    <input type="email" v-model="editForm.correo_electronico" readonly
                                        class="w-full px-4 py-3.5 sm:py-3 bg-surface-50 border border-surface-200 rounded-xl text-sm font-medium text-surface-400 cursor-not-allowed outline-none" />
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 sm:gap-6">
                                    <div class="space-y-1.5">
                                        <label
                                            class="text-[10px] font-black uppercase tracking-widest text-surface-400 px-1">Hora
                                            Entrada</label>
                                        <input type="time" v-model="editForm.hora_entrada"
                                            class="w-full px-4 py-3.5 sm:py-3 bg-white border border-surface-200 rounded-xl text-sm font-medium text-surface-900 focus:ring-2 focus:ring-blue-500/40 focus:border-blue-400 transition-all outline-none" />
                                    </div>
                                    <div class="space-y-1.5">
                                        <label
                                            class="text-[10px] font-black uppercase tracking-widest text-surface-400 px-1">Hora
                                            Salida</label>
                                        <input type="time" v-model="editForm.hora_salida"
                                            class="w-full px-4 py-3.5 sm:py-3 bg-white border border-surface-200 rounded-xl text-sm font-medium text-surface-900 focus:ring-2 focus:ring-blue-500/40 focus:border-blue-400 transition-all outline-none" />
                                    </div>
                                </div>
                            </div>

                            <!-- Pie -->
                            <div
                                class="px-5 sm:px-8 py-4 sm:py-5 bg-white border-t border-surface-100 flex items-center justify-end gap-3">
                                <CancelButton @click="showEditModal = false" />
                                <ConfirmButton label="Guardar Cambios" :loading="isSaving"
                                    @click="saveEditInstructor" />
                            </div>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>
    </main>
</template>
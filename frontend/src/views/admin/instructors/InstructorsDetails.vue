<script setup>
import { ref, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { storeToRefs } from 'pinia';
import api from '@/services/api';
import { useInstructorStore } from '@/stores/admin/instructorStore';
import LoadingSpinner from '@/components/gerente/ui/LoadingSpinner.vue';
import BadgeStatus from '@/components/gerente/ui/BadgeStatus.vue';
import AppModal from '@/components/ui/modal/AppModal.vue';
import AppButton from '@/components/ui/AppButton.vue';
import AppField from '@/components/ui/form/AppField.vue';
import AppInput from '@/components/ui/form/AppInput.vue';
import AppDatePicker from '@/components/ui/form/AppDatePicker.vue';
import AppTimePicker from '@/components/ui/form/AppTimePicker.vue';
import { IconPhone, IconClock, IconUser, IconMail, IconEdit } from '@/components/icons'
import { useformat } from '@/utils/formatters';
import { useAlerts } from '@/composables/useAlerts';

const route = useRoute();
const router = useRouter();
const instructorStore = useInstructorStore();
const { formatText, dateFormat } = useformat();
const { toastSuccess, toastError } = useAlerts();

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

const editErrors = ref({});
const hoyIso = new Intl.DateTimeFormat('en-CA', { timeZone: 'America/Mexico_City' }).format(new Date());

const validateEdit = () => {
    const f = editForm.value;
    const errors = {};
    if (!f.nombre_completo?.trim()) errors.nombre_completo = 'Escribe el nombre completo.';
    if (f.telefono && !/^\d{10}$/.test(String(f.telefono).trim())) errors.telefono = 'El teléfono debe tener 10 dígitos.';
    if (f.hora_entrada && f.hora_salida && f.hora_salida.slice(0, 5) <= f.hora_entrada.slice(0, 5)) errors.hora_salida = 'Debe ser posterior a la hora de entrada.';
    editErrors.value = errors;
    return Object.keys(errors).length === 0;
};

// Al corregir un campo marcado, su error desaparece
watch(editForm, () => {
    const prev = editErrors.value;
    if (!Object.keys(prev).length) return;
    validateEdit();
    editErrors.value = Object.fromEntries(Object.entries(editErrors.value).filter(([key]) => key in prev));
}, { deep: true });

const openEditModal = () => {
    if (instructor.value) {
        editForm.value = {
            nombre_completo: instructor.value.nombre_completo,
            telefono: instructor.value.telefono || '',
            correo_electronico: instructor.value.correo_electronico || '',
            fecha_nacimiento: instructor.value.fecha_nacimiento || '',
            hora_entrada: instructor.value.hora_entrada || '',
            hora_salida: instructor.value.hora_salida || ''
        };
    }
    editErrors.value = {};
    showEditModal.value = true;
};

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
    if (!validateEdit()) return;

    isSaving.value = true;
    try {
        const res = await instructorStore.updateInstructor(instructorId, editForm.value);
        if (res.success) {
            toastSuccess("Información del instructor actualizada");
            showEditModal.value = false;
            // Al ser reactivo el store y nosotros usar instructor.value = data en fetch,
            // y el store actualizar la lista, deberíamos refrescar la referencia local.
            instructor.value = instructorStore.getInstructorById(instructorId);
        } else {
            toastError(res.error || "No se pudo actualizar la información");
        }
    } catch (error) {
        console.error("Error al actualizar instructor:", error);
        toastError("No se pudo actualizar la información");
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

                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mt-8">
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
                                        <span class="text-base font-bold text-surface-900">{{ instructor.hora_entrada?.substring(0, 5) ||
                                            'N/A' }}</span>
                                    </div>
                                    <div
                                        class="bg-surface-50/50 rounded-2xl p-4 border border-surface-100 flex flex-col gap-1.5">
                                        <span
                                            class="text-[10px] font-extrabold uppercase tracking-widest text-surface-500">Hora
                                            Salida</span>
                                        <span class="text-base font-bold text-surface-900">{{ instructor.hora_salida?.substring(0, 5) ||
                                            'N/A' }}</span>
                                    </div>
                                </div>

                                <div class="mt-8 pt-6 border-t border-surface-100 flex justify-end">
                                    <AppButton class="w-full sm:w-auto" @click="openEditModal">
                                        <template #icon><IconEdit class="h-4 w-4" /></template>
                                        Gestionar información personal
                                    </AppButton>
                                </div>
                            </div>
                        </article>
                    </div>

                    <!-- TARJETA MÓVIL -->
                    <article class="md:hidden bg-white border border-surface-200 rounded-2xl shadow-sm overflow-hidden">

                        <!-- Cabecera: avatar grande + nombre (centrado en móvil) -->
                        <div
                            class="flex flex-col items-center sm:flex-row sm:items-start text-center sm:text-left gap-3 sm:gap-4 px-4 py-6">
                            <div class="w-20 h-20 rounded-2xl bg-linear-to-br flex items-center justify-center text-white font-black text-2xl shrink-0 shadow-md"
                                :class="avatarGradient(instructor.nombre_completo)">
                                {{ initials(instructor.nombre_completo) }}
                            </div>
                            <div class="min-w-0 w-full sm:flex-1">
                                <h2 class="text-lg font-black text-surface-900 leading-tight wrap-break-word">
                                    {{ instructor.nombre_completo }}
                                </h2>
                                <div class="mt-2 flex justify-center sm:justify-start">
                                    <BadgeStatus :status="instructor.estatus" />
                                </div>
                            </div>
                        </div>

                        <!-- Contacto: lista clave-valor táctil -->
                        <div class="border-t border-surface-100 divide-y divide-surface-100">
                            <a v-if="instructor.telefono" :href="`tel:${instructor.telefono}`"
                                class="flex items-center justify-between gap-3 px-4 min-h-14 py-3 active:bg-surface-50 transition-colors">
                                <span class="flex items-center gap-2 text-sm font-semibold text-surface-500">
                                    <IconPhone class="w-4 h-4 shrink-0" /> Teléfono
                                </span>
                                <span class="text-sm font-bold text-primary-600 truncate">{{ instructor.telefono
                                    }}</span>
                            </a>
                            <div v-else class="flex items-center justify-between gap-3 px-4 min-h-14 py-3">
                                <span class="flex items-center gap-2 text-sm font-semibold text-surface-500">
                                    <IconPhone class="w-4 h-4 shrink-0" /> Teléfono
                                </span>
                                <span class="text-sm text-surface-400">Sin teléfono</span>
                            </div>

                            <a v-if="instructor.correo_electronico" :href="`mailto:${instructor.correo_electronico}`"
                                class="flex items-center justify-between gap-3 px-4 min-h-14 py-3 active:bg-surface-50 transition-colors">
                                <span class="text-sm font-semibold text-surface-500 shrink-0">Correo</span>
                                <span class="text-sm font-bold text-primary-600 truncate">{{
                                    instructor.correo_electronico }}</span>
                            </a>
                            <div v-else class="flex items-center justify-between gap-3 px-4 min-h-14 py-3">
                                <span class="text-sm font-semibold text-surface-500">Correo</span>
                                <span class="text-sm text-surface-400">Sin correo</span>
                            </div>
                        </div>

                        <!-- Turnos y horarios: lista clave-valor táctil -->
                        <div class="border-t border-surface-100">
                            <p
                                class="px-4 pt-3 pb-1 text-[10px] font-extrabold uppercase tracking-widest text-surface-400">
                                Turno y horario laboral
                            </p>
                            <div class="divide-y divide-surface-100">
                                <div class="flex items-center justify-between gap-3 px-4 min-h-12 py-3">
                                    <span class="flex items-center gap-2 text-sm font-semibold text-surface-500">
                                        <IconClock class="w-4 h-4 text-surface-400 shrink-0" /> Hora entrada
                                    </span>
                                    <span class="text-sm font-bold text-surface-900">{{
                                        instructor.hora_entrada?.substring(0, 5) || 'N/A' }}</span>
                                </div>
                                <div class="flex items-center justify-between gap-3 px-4 min-h-12 py-3">
                                    <span class="flex items-center gap-2 text-sm font-semibold text-surface-500">
                                        <IconClock class="w-4 h-4 text-surface-400 shrink-0" /> Hora salida
                                    </span>
                                    <span class="text-sm font-bold text-surface-900">{{
                                        instructor.hora_salida?.substring(0, 5) || 'N/A' }}</span>
                                </div>
                                <div class="flex items-center justify-between gap-3 px-4 min-h-12 py-3">
                                    <span class="text-sm font-semibold text-surface-500">Contratación</span>
                                    <span class="text-sm font-bold text-surface-900">{{
                                        dateFormat(instructor.fecha_afiliacion) || 'N/A' }}</span>
                                </div>
                                <div class="flex items-center justify-between gap-3 px-4 min-h-12 py-3">
                                    <span class="text-sm font-semibold text-surface-500">Nacimiento</span>
                                    <span class="text-sm font-bold text-surface-900">{{
                                        dateFormat(instructor.fecha_nacimiento) || 'N/A' }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Pie -->
                        <div class="px-4 py-4 border-t border-surface-100">
                            <AppButton class="w-full" @click="openEditModal">
                                <template #icon><IconEdit class="h-4 w-4" /></template>
                                Gestionar información personal
                            </AppButton>
                        </div>
                    </article>

                </section>
            </Transition>
        </div>

        <!-- MODAL EDITAR INSTRUCTOR -->
        <AppModal
            v-model="showEditModal"
            title="Información personal"
            :subtitle="instructor?.nombre_completo"
            :icon="IconEdit"
            :persistent="isSaving"
        >
            <form id="instructor-edit-form" class="grid grid-cols-1 gap-4 sm:grid-cols-2" novalidate @submit.prevent="saveEditInstructor">
                <AppField label="Nombre completo" for="edit-nombre" required :error="editErrors.nombre_completo" class="sm:col-span-2">
                    <AppInput id="edit-nombre" v-model="editForm.nombre_completo" :icon="IconUser" autocomplete="off" :invalid="!!editErrors.nombre_completo" />
                </AppField>

                <AppField label="Teléfono" for="edit-telefono" :error="editErrors.telefono">
                    <AppInput id="edit-telefono" v-model="editForm.telefono" type="tel" inputmode="numeric" maxlength="10" :icon="IconPhone" placeholder="10 dígitos" autocomplete="off" :invalid="!!editErrors.telefono" />
                </AppField>

                <AppField label="Fecha de nacimiento">
                    <AppDatePicker v-model="editForm.fecha_nacimiento" :max-date="hoyIso" />
                </AppField>

                <AppField label="Correo electrónico" for="edit-correo" hint="Lo genera el sistema; no se puede modificar." class="sm:col-span-2">
                    <AppInput id="edit-correo" :model-value="editForm.correo_electronico" :icon="IconMail" readonly tabindex="-1" />
                </AppField>

                <AppField label="Hora de entrada">
                    <AppTimePicker v-model="editForm.hora_entrada" />
                </AppField>

                <AppField label="Hora de salida" :error="editErrors.hora_salida">
                    <AppTimePicker v-model="editForm.hora_salida" :invalid="!!editErrors.hora_salida" />
                </AppField>
            </form>

            <template #footer>
                <AppButton variant="secondary" :disabled="isSaving" @click="showEditModal = false">Cancelar</AppButton>
                <AppButton type="submit" form="instructor-edit-form" :loading="isSaving">Guardar cambios</AppButton>
            </template>
        </AppModal>
    </main>
</template>
<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { storeToRefs } from 'pinia';
import { useDisciplinesStore } from '@/stores/admin/disciplines';

const route = useRoute();
const router = useRouter();
const disciplinesStore = useDisciplinesStore();

const { categories, isLoading: storeLoading } = storeToRefs(disciplinesStore);

const discipline = ref(null);
const isLoading = ref(true);
const isSaving = ref(false);
const isEditing = ref(false);
const showDeleteModal = ref(false);

const editForm = ref({
    nombre_disciplina: '',
    id_categoria: null,
    descripcion: '',
    estatus: ''
});

onMounted(async () => {
    try {
        await disciplinesStore.fetchCategories();
        const data = await disciplinesStore.fetchDisciplineDetails(route.params.id);
        discipline.value = data;
        if (route.query.edit === 'true') {
            isEditing.value = true;
        }
        resetForm();
    } catch (error) {
        console.error(error);
        alert("Error al cargar los detalles.");
    } finally {
        isLoading.value = false;
    }
});

const resetForm = () => {
    editForm.value = {
        nombre_disciplina: discipline.value.nombre_disciplina,
        id_categoria: discipline.value.id_categoria,
        descripcion: discipline.value.descripcion || '',
        estatus: discipline.value.estatus
    };
};

const toggleEdit = () => {
    if (isEditing.value) resetForm();
    isEditing.value = !isEditing.value;
};

const handleUpdate = async () => {
    isSaving.value = true;
    const res = await disciplinesStore.updateDiscipline(discipline.value.id_disciplina, editForm.value);
    isSaving.value = false;
    if (res.success) {
        discipline.value = res.data;
        isEditing.value = false;
    } else {
        alert(res.error);
    }
};

const openDeleteModal = () => {
    showDeleteModal.value = true;
};

const confirmDelete = async () => {
    isSaving.value = true;
    const res = await disciplinesStore.deleteDiscipline(discipline.value.id_disciplina);
    isSaving.value = false;
    if (res.success) {
        showDeleteModal.value = false;
        router.push({ name: 'disciplines-list' });
    } else {
        alert(res.error);
    }
};

const goBack = () => router.push({ name: 'disciplines-list' });
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
                <h1 class="text-2xl font-black text-surface-900 tracking-tight">Detalles de Disciplina</h1>
            </header>

            <!-- Loading -->
            <section v-if="isLoading" class="flex flex-col items-center justify-center p-20">
                <div class="w-12 h-12 border-4 border-surface-200 border-t-primary-600 rounded-full animate-spin mb-4"></div>
                <p class="text-sm font-extrabold uppercase tracking-widest text-surface-400">Cargando detalles...</p>
            </section>

            <Transition enter-active-class="transition-all duration-500 ease-out" enter-from-class="opacity-0 translate-y-4 scale-[0.98]" enter-to-class="opacity-100 translate-y-0 scale-100">
                <div v-if="discipline && !isLoading" class="space-y-6">
                    
                    <!-- Main Info Card -->
                    <article class="bg-white rounded-[2.5rem] shadow-xl shadow-surface-200/40 border border-surface-200 overflow-hidden relative">
                        <!-- Accent Bar -->
                        <div class="absolute top-0 left-0 right-0 h-2 bg-linear-to-r from-primary-600 to-primary-400"></div>

                        <div class="p-8 lg:p-10">
                            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 mb-8">
                                <div class="flex gap-6 items-center">
                                    <div class="p-5 bg-primary-50 text-primary-600 rounded-2xl shadow-sm border border-primary-100 shrink-0">
                                        <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8h1a4 4 0 0 1 0 8h-1"/><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/><line x1="6" x2="6" y1="1" y2="4"/><line x1="10" x2="10" y1="1" y2="4"/><line x1="14" x2="14" y1="1" y2="4"/></svg>
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-3 mb-1">
                                            <h2 class="text-3xl font-black text-surface-900 leading-tight">{{ discipline.nombre_disciplina }}</h2>
                                            <span :class="{
                                                'bg-green-50 text-green-700 border-green-200': discipline.estatus === 'ACTIVO',
                                                'bg-amber-50 text-amber-700 border-amber-200': discipline.estatus === 'MANTENIMIENTO',
                                                'bg-red-50 text-red-700 border-red-200': discipline.estatus === 'DESHABILITADO',
                                                'bg-surface-50 text-surface-600 border-surface-200': discipline.estatus === 'INACTIVO'
                                            }" class="px-3 py-1 rounded-lg border text-[10px] font-black uppercase tracking-widest shadow-sm">
                                                {{ discipline.estatus }}
                                            </span>
                                        </div>
                                        <p class="text-primary-600 font-bold uppercase tracking-widest text-xs flex items-center gap-2 mt-2">
                                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                                            {{ discipline.categoria?.nombre_categoria || discipline.categoria_disciplina }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex flex-col items-end gap-2 shrink-0">
                                    <button @click="toggleEdit" class="px-5 py-2.5 bg-primary-50 text-primary-700 rounded-xl font-bold hover:bg-primary-600 hover:text-white transition-colors text-sm flex items-center gap-2 border border-primary-200 hover:border-primary-600 shadow-sm w-full md:w-auto justify-center">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                        {{ isEditing ? 'Cancelar Edición' : 'Editar Información' }}
                                    </button>
                                    <button v-if="!isEditing" @click="openDeleteModal" class="px-5 py-2.5 bg-white text-red-600 rounded-xl font-bold hover:bg-red-50 transition-colors text-sm flex items-center gap-2 border border-surface-200 hover:border-red-200 shadow-sm w-full md:w-auto justify-center">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        Deshabilitar
                                    </button>
                                </div>
                            </div>

                            <div v-if="!isEditing" class="grid grid-cols-1 md:grid-cols-2 gap-8 border-t border-surface-100 pt-8 mt-8">
                                <div class="space-y-6">
                                    <div class="bg-surface-50/50 rounded-2xl p-4 border border-surface-100 flex flex-col gap-1.5">
                                        <span class="text-[10px] font-extrabold uppercase tracking-widest text-surface-500">ID Disciplina</span>
                                        <span class="text-base font-bold text-surface-900">#{{ discipline.id_disciplina }}</span>
                                    </div>
                                    <div class="bg-surface-50/50 rounded-2xl p-4 border border-surface-100 flex flex-col gap-1.5">
                                        <span class="text-[10px] font-extrabold uppercase tracking-widest text-surface-500">Categoría</span>
                                        <span class="text-base font-bold text-surface-900">
                                            {{ discipline.categoria?.nombre_categoria || discipline.categoria_disciplina }}
                                        </span>
                                    </div>
                                </div>
                                <div class="space-y-6 h-full">
                                    <div class="bg-surface-50/50 rounded-2xl p-5 border border-surface-100 h-full flex flex-col gap-2">
                                        <span class="text-[10px] font-extrabold uppercase tracking-widest text-surface-500">Descripción</span>
                                        <p class="text-sm font-medium text-surface-700 leading-relaxed">{{ discipline.descripcion || 'Sin descripción disponible.' }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Edit Form -->
                            <div v-else class="space-y-6 animate-in slide-in-from-top duration-300 border-t border-surface-100 pt-8 mt-8">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="space-y-1.5">
                                        <label class="text-[10px] font-black uppercase tracking-widest text-surface-500 px-1">Nombre</label>
                                        <input v-model="editForm.nombre_disciplina" type="text" class="w-full px-4 py-3.5 bg-surface-50 border border-surface-200 rounded-xl text-sm font-bold text-surface-900 focus:ring-2 focus:ring-primary-500/40 focus:border-primary-400 transition-all outline-none">
                                    </div>
                                    <div class="space-y-1.5">
                                        <label class="text-[10px] font-black uppercase tracking-widest text-surface-500 px-1">Categoría</label>
                                        <select v-model="editForm.id_categoria" class="w-full px-4 py-3.5 bg-surface-50 border border-surface-200 rounded-xl text-sm font-bold text-surface-900 focus:ring-2 focus:ring-primary-500/40 focus:border-primary-400 transition-all outline-none cursor-pointer">
                                            <option :value="null" disabled>Selecciona una categoría</option>
                                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.nombre_categoria }}</option>
                                        </select>
                                    </div>
                                    <div class="space-y-1.5">
                                        <label class="text-[10px] font-black uppercase tracking-widest text-surface-500 px-1">Estatus</label>
                                        <select v-model="editForm.estatus" class="w-full px-4 py-3.5 bg-surface-50 border border-surface-200 rounded-xl text-sm font-bold text-surface-900 focus:ring-2 focus:ring-primary-500/40 focus:border-primary-400 transition-all outline-none cursor-pointer">
                                            <option value="ACTIVO">ACTIVO</option>
                                            <option value="MANTENIMIENTO">MANTENIMIENTO</option>
                                            <option value="DESHABILITADO">DESHABILITADO</option>
                                            <option value="INACTIVO">INACTIVO</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-[10px] font-black uppercase tracking-widest text-surface-500 px-1">Descripción</label>
                                    <textarea v-model="editForm.descripcion" rows="4" class="w-full px-4 py-3.5 bg-surface-50 border border-surface-200 rounded-xl text-sm font-medium text-surface-900 focus:ring-2 focus:ring-primary-500/40 focus:border-primary-400 transition-all outline-none resize-none"></textarea>
                                </div>
                                <div class="flex justify-end gap-3 pt-4">
                                    <button @click="handleUpdate" :disabled="isSaving" class="bg-primary-600 hover:bg-primary-700 text-white px-8 py-3.5 rounded-xl font-bold transition-all shadow-sm hover:shadow-md disabled:opacity-50 flex items-center gap-2">
                                        <svg v-if="isSaving" class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                                        {{ isSaving ? 'Guardando...' : 'Guardar Cambios' }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </article>

                    <!-- Instructors Section -->
                    <article class="bg-white rounded-[2.5rem] shadow-xl shadow-surface-200/40 border border-surface-200 overflow-hidden">
                        <div class="p-8 lg:p-10">
                            <h3 class="text-xl font-black text-surface-900 mb-6 flex items-center gap-3">
                                <div class="p-2 bg-primary-50 rounded-xl text-primary-600 border border-primary-100">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" /><circle cx="9" cy="7" r="4" /><path d="M23 21v-2a4 4 0 0 0-3-3.87" /><path d="M16 3.13a4 4 0 0 1 0 7.75" /></svg>
                                </div>
                                Instructores Asignados
                            </h3>
                            
                            <div v-if="discipline.instructores?.length" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div v-for="ins in discipline.instructores" :key="ins.id_instructor" 
                                     class="flex items-center gap-4 p-4 bg-surface-50/50 rounded-2xl border border-surface-100 group hover:border-primary-200 hover:bg-white hover:shadow-sm transition-all">
                                    <div class="w-12 h-12 bg-linear-to-br from-primary-500 to-primary-700 text-white rounded-[1.25rem] flex items-center justify-center font-black text-lg shadow-inner group-hover:scale-105 transition-transform shrink-0">
                                        {{ ins.nombre_completo.charAt(0) }}
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="font-bold text-surface-900 truncate">{{ ins.nombre_completo }}</p>
                                        <p class="text-[11px] font-bold text-surface-400 uppercase tracking-widest mt-0.5">ID: #{{ ins.id_instructor }}</p>
                                    </div>
                                    <div class="w-2.5 h-2.5 rounded-full bg-green-500 shadow-sm shrink-0"></div>
                                </div>
                            </div>
                            <div v-else class="text-center p-12 bg-surface-50 rounded-[2rem] border-2 border-dashed border-surface-200">
                                <p class="text-sm font-bold text-surface-400 italic">No hay instructores asignados a esta disciplina.</p>
                            </div>
                        </div>
                    </article>
                </div>
            </Transition>
        </div>

        <!-- Deactivation Modal -->
        <Teleport to="body">
            <Transition enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition-all duration-200 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-surface-900/60 backdrop-blur-sm" @click.self="showDeleteModal = false">
                    <Transition enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0 scale-95 translate-y-4" enter-to-class="opacity-100 scale-100 translate-y-0">
                        <div v-if="showDeleteModal" class="bg-white rounded-[2.5rem] w-full max-w-md shadow-2xl overflow-hidden text-center">
                            <div class="p-8">
                                <div class="w-20 h-20 bg-red-50 text-red-600 rounded-[1.5rem] flex items-center justify-center mx-auto mb-6 border border-red-100">
                                    <svg class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                                </div>
                                <h3 class="text-2xl font-black text-surface-900 mb-3">¿Deshabilitar Disciplina?</h3>
                                <p class="text-sm font-medium text-surface-500 mb-8 leading-relaxed">¿Estás seguro de deshabilitar <b class="text-surface-900">{{ discipline?.nombre_disciplina }}</b>? El sistema validará que no haya actividades ni instructores activos.</p>
                                
                                <div class="flex gap-3">
                                    <button @click="showDeleteModal = false" class="flex-1 py-3.5 bg-white border border-surface-200 hover:bg-surface-50 text-surface-700 rounded-xl font-bold transition-all text-sm">
                                        Cancelar
                                    </button>
                                    <button @click="confirmDelete" :disabled="isSaving" class="flex-1 py-3.5 bg-red-600 hover:bg-red-700 text-white rounded-xl font-bold transition-all shadow-sm hover:shadow-md disabled:opacity-50 text-sm flex justify-center items-center gap-2">
                                        <svg v-if="isSaving" class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                                        {{ isSaving ? 'Procesando...' : 'Deshabilitar' }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>
    </main>
</template>

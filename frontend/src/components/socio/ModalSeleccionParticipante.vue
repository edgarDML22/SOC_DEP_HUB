<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useProfileStore } from '@/stores/profiles/socioStore';
import { useFamilyStore } from '@/stores/community/familyStore';
import { IconUser, IconGuests, IconCalendar } from '@/components/icons';

const props = defineProps({
  isOpen: {
    type: Boolean,
    required: true
  },
  torneo: {
    type: Object,
    required: true
  }
});

const emit = defineEmits(['close', 'confirm']);

const profileStore = useProfileStore();
const familyStore = useFamilyStore();

const selectedType = ref(null); // 'SOCIO' o 'FAMILIAR'
const selectedFamiliarId = ref('');
const rankingDeclarado = ref(0);
const loading = ref(false);

const isFamilyRegistered = computed(() => {
  return props.torneo?.familiares_inscritos && props.torneo.familiares_inscritos.length > 0;
});

watch(
  () => props.isOpen,
  (newVal) => {
    if (newVal) {
      if (props.torneo?.modalidad_plan === 'INDIVIDUAL') {
        selectedType.value = 'SOCIO';
      } else {
        selectedType.value = null;
      }
      selectedFamiliarId.value = '';
      rankingDeclarado.value = 0;
    }
  }
);

onMounted(async () => {
  if (!profileStore.profileData) {
    await profileStore.fetchProfile();
  }
  if (props.torneo?.modalidad_plan === 'INDIVIDUAL') {
    selectedType.value = 'SOCIO';
  }
});

// Cambiar tipo de participante y cargar familiares en caso de ser necesario
const selectType = async (type) => {
  selectedType.value = type;
  if (type === 'FAMILIAR') {
    loading.value = true;
    try {
      await familyStore.fetchMiembrosFamiliares();
    } finally {
      loading.value = false;
    }
  } else {
    selectedFamiliarId.value = '';
  }
};

// Helper para calcular edad
const calculateAge = (birthDateStr) => {
  if (!birthDateStr) return 0;
  const birthDate = new Date(birthDateStr);
  const today = new Date();
  let age = today.getFullYear() - birthDate.getFullYear();
  const monthDiff = today.getMonth() - birthDate.getMonth();
  if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
    age--;
  }
  return age;
};

// Calcular elegibilidad de un participante dado
const getEligibility = (participant) => {
  const cat = props.torneo?.categoria;
  if (!cat) return { eligible: true };

  const bday = participant.fecha_nacimiento || participant.fechaNacimiento;
  const age = calculateAge(bday);

  // 1. Rango de edad
  if (cat.edad_minima !== null && cat.edad_minima !== undefined && age < cat.edad_minima) {
    return { eligible: false, reason: `No cumple la edad: requiere mín. ${cat.edad_minima}, tiene ${age}` };
  }
  if (cat.edad_maxima !== null && cat.edad_maxima !== undefined && age > cat.edad_maxima) {
    return { eligible: false, reason: `No cumple la edad: requiere máx. ${cat.edad_maxima}, tiene ${age}` };
  }

  // 2. Género
  const genderReq = String(cat.genero_requerido).toUpperCase();
  const partGender = String(participant.genero).toUpperCase();

  if (genderReq !== 'MIXTO') {
    if (genderReq === 'M' && partGender !== 'M') {
      return { eligible: false, reason: 'Requiere rama Varonil' };
    }
    if (genderReq === 'F' && partGender !== 'F') {
      return { eligible: false, reason: 'Requiere rama Femenil' };
    }
    if (genderReq !== 'M' && genderReq !== 'F' && genderReq !== partGender) {
      return { eligible: false, reason: 'El género no coincide con el torneo' };
    }
  }

  return { eligible: true };
};

// Datos del Titular mapeados
const titularData = computed(() => {
  return {
    nombre_completo: profileStore.fullName,
    fecha_nacimiento: profileStore.fechaNacimiento,
    genero: profileStore.genero
  };
});

// Elegibilidad del Titular
const titularEligibility = computed(() => {
  return getEligibility(titularData.value);
});

// Familiar seleccionado
const selectedFamiliar = computed(() => {
  if (!selectedFamiliarId.value) return null;
  return familyStore.miembrosFamiliares.find(
    (m) => m.id_miembro === parseInt(selectedFamiliarId.value)
  );
});

// Elegibilidad del Familiar seleccionado
const familiarEligibility = computed(() => {
  if (!selectedFamiliar.value) return { eligible: false, reason: 'Seleccione un familiar' };
  return getEligibility(selectedFamiliar.value);
});

// Elegibilidad del participante activo actual
const activeEligibility = computed(() => {
  if (selectedType.value === 'SOCIO') {
    return titularEligibility.value;
  }
  if (selectedType.value === 'FAMILIAR') {
    return familiarEligibility.value;
  }
  return { eligible: false, reason: 'Seleccione un participante' };
});

// Validación de ranking
const isRankingValido = computed(() => {
  const val = parseInt(rankingDeclarado.value);
  return !isNaN(val) && val >= 0 && val <= 500;
});

// Validador final de formulario
const isFormValido = computed(() => {
  if (!selectedType.value) return false;
  if (selectedType.value === 'FAMILIAR' && !selectedFamiliarId.value) return false;
  if (!activeEligibility.value.eligible) return false;
  return isRankingValido.value;
});

// Confirmar
const handleConfirm = () => {
  if (!isFormValido.value) return;

  const payload = {
    participante_type: selectedType.value,
    participante_id: selectedType.value === 'SOCIO' 
      ? profileStore.idSocio 
      : parseInt(selectedFamiliarId.value),
    ranking_declarado: parseInt(rankingDeclarado.value)
  };

  emit('confirm', payload);
};

const handleClose = () => {
  selectedType.value = null;
  selectedFamiliarId.value = '';
  rankingDeclarado.value = 0;
  emit('close');
};
</script>

<template>
  <div
    v-if="isOpen"
    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm transition-opacity duration-300"
    @click.self="handleClose"
  >
    <div
      class="bg-white rounded-3xl w-full max-w-lg overflow-hidden shadow-2xl border border-surface-100 transform scale-100 transition-all duration-300 flex flex-col max-h-[90vh]"
    >
      <!-- Cabecera -->
      <div class="px-6 py-5 border-b border-surface-100 bg-surface-50 flex items-center justify-between shrink-0">
        <div>
          <h3 class="text-xl font-extrabold text-surface-900 m-0">Inscripción al Torneo</h3>
          <p class="text-xs font-semibold text-primary-600 mt-1 uppercase tracking-wider">
            {{ torneo?.nombre_torneo }}
          </p>
        </div>
        <button
          @click="handleClose"
          class="p-2 text-surface-400 hover:text-surface-600 rounded-full hover:bg-surface-100 transition-colors focus:outline-none"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Cuerpo del Modal -->
      <div class="p-6 overflow-y-auto scrollbar-thin flex-1 space-y-6">
        <!-- Rango de Categoría del Torneo (Informativo) -->
        <div class="bg-primary-50/50 border border-primary-100 rounded-2xl p-4 flex gap-3 items-start">
          <div class="p-2 bg-primary-100 text-primary-600 rounded-xl shrink-0">
            <IconCalendar class="w-5 h-5" />
          </div>
          <div>
            <p class="text-sm font-extrabold text-primary-950">Información de la Categoría</p>
            <div class="text-xs text-primary-800 font-semibold space-y-1 mt-1">
              <p>Categoría: <span class="font-extrabold">{{ torneo?.categoria?.nombre_categoria }}</span></p>
              <p>Edades: <span class="font-extrabold">{{ torneo?.categoria?.edad_minima }} a {{ torneo?.categoria?.edad_maxima }} años</span></p>
              <p>Género Requerido: <span class="font-extrabold">{{ torneo?.categoria?.genero_requerido === 'M' ? 'Varonil' : (torneo?.categoria?.genero_requerido === 'F' ? 'Femenil' : 'Mixto') }}</span></p>
            </div>
          </div>
        </div>

        <!-- 2 Option Cards: Titular vs Familiar -->
        <div v-if="props.torneo?.modalidad_plan !== 'INDIVIDUAL'">
          <label class="text-sm font-extrabold text-surface-700 block mb-3">Selecciona el tipo de participante</label>
          <div class="grid grid-cols-2 gap-4">
            <!-- Card Titular -->
            <button
              @click="!isFamilyRegistered && selectType('SOCIO')"
              :disabled="isFamilyRegistered"
              class="border-2 rounded-2xl p-4 flex flex-col items-center justify-center gap-3 transition-all focus:outline-none w-full text-center group"
              :class="[
                isFamilyRegistered
                  ? 'border-surface-100 bg-surface-50 text-surface-400 cursor-not-allowed opacity-60'
                  : selectedType === 'SOCIO'
                    ? 'border-primary-600 bg-primary-50/40 shadow-sm'
                    : 'border-surface-200 hover:border-surface-300 hover:bg-surface-50/50'
              ]"
            >
              <div
                class="w-12 h-12 rounded-xl flex items-center justify-center transition-colors shrink-0"
                :class="[
                  isFamilyRegistered
                    ? 'bg-surface-200 text-surface-400'
                    : selectedType === 'SOCIO'
                      ? 'bg-primary-100 text-primary-600'
                      : 'bg-surface-100 text-surface-500 group-hover:bg-surface-200'
                ]"
              >
                <IconUser class="w-6 h-6" />
              </div>
              <div>
                <p class="text-sm font-extrabold text-surface-900" :class="{'text-surface-400': isFamilyRegistered}">Titular (Yo)</p>
                <p class="text-[10px] font-semibold text-surface-450 mt-0.5" :class="{'text-rose-500 font-extrabold': isFamilyRegistered}">
                  {{ isFamilyRegistered ? 'Familiar inscrito' : 'Socio Titular' }}
                </p>
              </div>
            </button>

            <!-- Card Familiar -->
            <button
              @click="!props.torneo?.titular_inscrito && selectType('FAMILIAR')"
              :disabled="props.torneo?.titular_inscrito"
              class="border-2 rounded-2xl p-4 flex flex-col items-center justify-center gap-3 transition-all focus:outline-none w-full text-center group"
              :class="[
                props.torneo?.titular_inscrito
                  ? 'border-surface-100 bg-surface-50 text-surface-400 cursor-not-allowed opacity-60'
                  : selectedType === 'FAMILIAR'
                    ? 'border-primary-600 bg-primary-50/40 shadow-sm'
                    : 'border-surface-200 hover:border-surface-300 hover:bg-surface-50/50'
              ]"
            >
              <div
                class="w-12 h-12 rounded-xl flex items-center justify-center transition-colors shrink-0"
                :class="[
                  props.torneo?.titular_inscrito
                    ? 'bg-surface-200 text-surface-400'
                    : selectedType === 'FAMILIAR'
                      ? 'bg-primary-100 text-primary-600'
                      : 'bg-surface-100 text-surface-500 group-hover:bg-surface-200'
                ]"
              >
                <IconGuests class="w-6 h-6" />
              </div>
              <div>
                <p class="text-sm font-extrabold text-surface-900" :class="{'text-surface-400': props.torneo?.titular_inscrito}">Familiar</p>
                <p class="text-[10px] font-semibold text-surface-450 mt-0.5" :class="{'text-rose-500 font-extrabold': props.torneo?.titular_inscrito}">
                  {{ props.torneo?.titular_inscrito ? 'Titular inscrito' : 'Miembro Familiar' }}
                </p>
              </div>
            </button>
          </div>
        </div>

        <!-- Seccion Seleccion Familiar (si type === FAMILIAR) -->
        <div v-if="selectedType === 'FAMILIAR'" class="space-y-3">
          <label class="text-sm font-extrabold text-surface-700 block">Miembro Familiar</label>
          <div v-if="loading" class="flex justify-center py-4">
            <svg class="animate-spin h-6 w-6 text-primary-600" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
          </div>
          <div v-else-if="familyStore.miembrosFamiliares.length === 0">
            <p class="text-xs text-surface-500 font-semibold bg-surface-50 rounded-xl p-3 border border-dashed border-surface-200">
              No tienes miembros familiares registrados en tu plan.
            </p>
          </div>
          <div v-else>
            <select
              v-model="selectedFamiliarId"
              class="w-full bg-white border border-surface-200 rounded-xl px-4 py-3 text-sm font-bold text-surface-850 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 shadow-sm"
            >
              <option value="" disabled>Seleccione un familiar...</option>
              <option
                v-for="m in familyStore.miembrosFamiliares"
                :key="m.id_miembro"
                :value="m.id_miembro"
                :disabled="props.torneo?.familiares_inscritos?.includes(m.id_miembro)"
              >
                {{ m.nombre_completo }} ({{ m.parentesco }}){{ props.torneo?.familiares_inscritos?.includes(m.id_miembro) ? ' - Ya inscrito' : '' }}
              </option>
            </select>
          </div>
        </div>

        <!-- Badge de Elegibilidad y Datos calculados -->
        <div
          v-if="selectedType === 'SOCIO' || (selectedType === 'FAMILIAR' && selectedFamiliarId)"
          class="border border-surface-150 rounded-2xl p-4 space-y-3"
        >
          <div class="flex items-center justify-between">
            <span class="text-xs font-extrabold text-surface-500 uppercase tracking-wider">Resultado Elegibilidad</span>
            <!-- Badge Elegible Verde -->
            <span
              v-if="activeEligibility.eligible"
              class="bg-emerald-100 text-emerald-800 text-[10px] font-extrabold px-2.5 py-1 rounded-full uppercase tracking-wider border border-emerald-200 flex items-center gap-1 shadow-sm"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
              </svg>
              Elegible
            </span>
            <!-- Badge Inelegible Rojo -->
            <span
              v-else
              class="bg-rose-100 text-rose-800 text-[10px] font-extrabold px-2.5 py-1 rounded-full uppercase tracking-wider border border-rose-200 flex items-center gap-1 shadow-sm"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
              </svg>
              No cumple
            </span>
          </div>

          <!-- Mensaje de no cumple -->
          <p
            v-if="!activeEligibility.eligible"
            class="text-xs font-bold text-rose-600 bg-rose-50 border border-rose-100 rounded-xl p-3"
          >
            Motivo: {{ activeEligibility.reason }}
          </p>

          <!-- Datos calculados -->
          <div class="grid grid-cols-2 gap-2 text-xs font-semibold text-surface-600 bg-surface-50 p-3 rounded-xl border border-surface-100">
            <p>Edad Calculada: <span class="font-extrabold text-surface-900">{{ calculateAge(selectedType === 'SOCIO' ? profileStore.fechaNacimiento : selectedFamiliar?.fecha_nacimiento) }} años</span></p>
            <p>Género: <span class="font-extrabold text-surface-900">{{ (selectedType === 'SOCIO' ? profileStore.genero : selectedFamiliar?.genero) === 'M' ? 'Masculino' : 'Femenino' }}</span></p>
          </div>
        </div>

        <!-- Campo Ranking Declarado -->
        <div v-if="activeEligibility.eligible" class="space-y-2">
          <div class="flex justify-between items-center">
            <label class="text-sm font-extrabold text-surface-700 block">Ranking Declarado (0 - 500)</label>
            <span
              v-if="!isRankingValido"
              class="text-[10px] font-bold text-rose-600 bg-rose-50 px-2 py-0.5 rounded-md border border-rose-100"
            >
              Fuera de rango
            </span>
          </div>
          <input
            type="number"
            v-model="rankingDeclarado"
            min="0"
            max="500"
            class="w-full bg-white border rounded-xl px-4 py-3 text-sm font-bold text-surface-850 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 shadow-sm"
            :class="!isRankingValido ? 'border-rose-300 ring-rose-500/10' : 'border-surface-200'"
          />
          <p class="text-[10px] text-surface-400 font-semibold">
            Declare su puntuación o nivel de ranking actual para emparejamientos y brackets.
          </p>
        </div>
      </div>

      <!-- Pie del Modal -->
      <div class="px-6 py-5 border-t border-surface-100 bg-surface-50 flex items-center justify-end gap-3 shrink-0">
        <button
          @click="handleClose"
          class="px-5 py-2.5 text-sm font-bold text-surface-600 hover:text-surface-800 rounded-xl hover:bg-surface-100 transition-colors focus:outline-none"
        >
          Cancelar
        </button>
        <button
          @click="handleConfirm"
          :disabled="!isFormValido"
          class="px-6 py-2.5 text-sm font-bold text-white rounded-xl shadow-md transition-all focus:outline-none"
          :class="isFormValido
            ? 'bg-primary-600 hover:bg-primary-700 active:scale-95 shadow-primary-500/20'
            : 'bg-surface-300 cursor-not-allowed shadow-none'"
        >
          Confirmar Inscripción
        </button>
      </div>
    </div>
  </div>
</template>

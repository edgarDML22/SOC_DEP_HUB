<script setup>
import { onMounted, defineAsyncComponent, ref, computed } from 'vue';
import { storeToRefs } from 'pinia';
import { useReservationStore } from '@/stores/reservationStore';
import { useProfileStore } from '@/stores/profileStore'; 

// Imports de PrimeVue 4
import Stepper from 'primevue/stepper';
import StepList from 'primevue/steplist';
import Step from 'primevue/step';
import StepPanels from 'primevue/steppanels';
import StepPanel from 'primevue/steppanel';
import Button from 'primevue/button';
import Select from 'primevue/select';
import Dialog from 'primevue/dialog';

// Inicializamos el store
const reservationStore = useReservationStore();

// Extraemos el ESTADO y GETTERS (Reactivos)
const {
    pasoActual,
    cargando,
    disciplinasUnicas,
    horariosDisponibles,
    reservaPayload,
    opcionesHoras,
    horaInicioTemp,
    horaFinTemp,
    esHorarioValidoParaPreview,
    errorValidacion,
    errorNavegacion
} = storeToRefs(reservationStore);

// Extraemos ACCIONES
const {
    fetchDisponibilidadEspacios,
    seleccionarDisciplina,
    obtenerIconoName,
    validarHorario,
    buscarReservaActiva
} = reservationStore;

// --- CICLO DE VIDA ---
onMounted(async () => {
    // 1. Disparamos la carga de deportes DE INMEDIATO. 
    // No lleva 'await' porque no queremos que bloquee el resto del código.
    reservationStore.fetchDisponibilidadEspacios();

    // 2. Cargamos el perfil y buscamos el borrador en paralelo.
    const profileStore = useProfileStore();
    
    // Nos aseguramos de tener el perfil primero
    await profileStore.fetchProfile(); 
    
    // Ahora buscamos el borrador
    const tieneDraft = await reservationStore.buscarReservaActiva();
    
    if (tieneDraft) {
        mostrarModalDraft.value = true;
    }
});


// --- DIBUJO DEL CALENDARIO ---
const horaApertura = 7;
const horaCierre = 23;
const totalHoras = horaCierre - horaApertura;
const mostrarModalDraft = ref(false);



// Funciones para los botones del Modal
const reanudarReserva = () => {
    mostrarModalDraft.value = false;
    reservationStore.pasoActual = "4"; // Lo mandamos directo a acompañantes
};

const ignorarReserva = async () => {
    mostrarModalDraft.value = false;
    await reservationStore.descartarBorrador();
};

const formatearHora = (horaString) => {
    if (!horaString) return '';
    return horaString.substring(0, 5);
};

const calcularPosicionGrid = (inicio, fin) => {
    if (!inicio || !fin) return '1 / 2';

    const horaInicio = parseInt(inicio.split(':')[0], 10);
    const horaFin = parseInt(fin.split(':')[0], 10);
    const filaInicio = (horaInicio - horaApertura) + 1;
    const filaFin = (horaFin - horaApertura) + 1;

    return `${filaInicio} / ${filaFin}`;
};

const IconoDeporte = (disciplina) => {
    const nombreArchivo = obtenerIconoName(disciplina);
    return defineAsyncComponent(() => import(`@/components/icons/sports/${nombreArchivo}.vue`));
};
</script>


<template>

    <Dialog v-model:visible="mostrarModalDraft" modal :closable="false" class="modal-borrador-minimal">
        
        <template #header>
            <div class="custom-modal-header">
                <h3>Reserva pendiente</h3>
            </div>
        </template>

        <div class="custom-modal-body">
            <p class="modal-description">
                Tienes una reservación que no terminaste de confirmar. El espacio sigue resevado temporalmente para ti.
            </p>

            <div class="draft-details-list">
                <div class="detail-item">
                    <span class="detail-label">Deporte:</span>
                    <strong class="detail-value">{{ reservaPayload.disciplinaSeleccionada }}</strong>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Horario:</span>
                    <strong class="detail-value">
                        {{ formatearHora(reservaPayload.hora_inicio) }} - {{ formatearHora(reservaPayload.hora_fin) }}
                    </strong>
                </div>
            </div>

            <p class="modal-question">
                ¿Qué deseas hacer?
            </p>
        </div>
        
        <template #footer>
            <div class="custom-modal-footer">
                <Button label="Ignorar y empezar de cero" class="btn-descartar" @click="ignorarReserva" />
                <Button label="Continuar mi reserva" icon="pi pi-arrow-right" iconPos="right" class="btn-continuar" @click="reanudarReserva" />
            </div>
        </template>

    </Dialog>
    
    

    <div class="page-wrapper">
        <div class="main-card">
            <Stepper :value="pasoActual" @update:value="reservationStore.intentarCambioPaso">

                <StepList>
                    <Step value="1">Deporte</Step>
                    <Step value="2">Espacio</Step>
                    <Step value="3">Horario</Step>
                    <Step value="4">Acompañantes</Step>
                    <Step value="5">Confirmación</Step>
                </StepList>

                <div v-if="errorNavegacion" class="alerta-navegacion">
                    <i class="pi pi-exclamation-triangle"></i>
                    <span>{{ errorNavegacion }}</span>
                </div>

                <StepPanels>
                    <StepPanel value="1">
                        <div class="step-content-wrapper">

                            <div class="w-full">
                                <h3 class="section-title">¿Qué vas a jugar hoy?</h3>

                                <div v-if="cargando" class="loader-container">
                                    <i class="pi pi-spin pi-spinner loader-icon"></i>
                                    <p>Cargando opciones...</p>
                                </div>

                                <div v-else class="contenedor-tarjetas">
                                    <button v-for="disciplina in disciplinasUnicas" :key="disciplina"
                                        @click="seleccionarDisciplina(disciplina)" class="tarjeta-deporte">

                                        <div class="icono-contenedor">
                                            <component :is="IconoDeporte(disciplina)" class="icono-svg" />
                                        </div>
                                        <span class="texto-disciplina">
                                            {{ disciplina }}
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </StepPanel>

                    <StepPanel value="2">
                        <div class="canchas-container">

                            <div class="action-bar">
                                <Button icon="pi pi-arrow-left" label="Elegir otro deporte" class="btn-retroceder"
                                    @click="reservationStore.volverADisciplinas()" />
                            </div>

                            <div class="header-canchas">
                                <h3 class="section-title">Selecciona tu cancha de <span class="highlight-deporte">
                                        {{ reservaPayload.disciplinaSeleccionada }}
                                    </span></h3>
                            </div>

                            <div v-if="reservationStore.espaciosPorDisciplina.length" class="capacidad-badge">
                                <i class="pi pi-users capacidad-icon"></i>
                                <span class="capacidad-text">
                                    Capacidad Máxima: <strong class="capacidad-number">{{
                                        reservationStore.espaciosPorDisciplina[0]?.capacidad_maxima || 'N/A' }}
                                        personas</strong> por cancha
                                </span>
                            </div>

                            <div class="grid-canchas">
                                <button v-for="cancha in reservationStore.espaciosPorDisciplina"
                                    :key="cancha.id_espacio"
                                    @click="reservationStore.seleccionarEspacio(cancha.id_espacio)"
                                    :disabled="cancha.estatus === 'Bloqueado por Mantenimiento'" class="cancha-card"
                                    :class="[cancha.estatus === 'Disponible' ? 'is-available' : 'is-disabled']">

                                    <!-- Info del espacio -->
                                    <div class="cancha-left-info">
                                        <div class="cancha-icon-wrapper">
                                            <component :is="IconoDeporte(reservaPayload.disciplinaSeleccionada)"
                                                class="icon-cancha-svg" />
                                        </div>

                                        <div>
                                            <div class="cancha-name">
                                                {{ cancha.nombre_espacio }}
                                            </div>
                                        </div>
                                    </div>

                                    <!-- ESTATUS -->
                                    <div class="shrink-0 ml-4">
                                        <span class="cancha-status-badge"
                                            :class="cancha.estatus === 'Disponible' ? 'status-available' : 'status-maintenance'">
                                            {{ cancha.estatus === 'Disponible' ? 'Disponible' : 'Mantenimiento' }}
                                        </span>
                                    </div>
                                </button>
                            </div>
                        </div>
                    </StepPanel>


                    <StepPanel value="3">
                        <div class="step-content-inner">
                            <div class="action-bar mb-action-bar">
                                <Button icon="pi pi-arrow-left" label="Elegir otra cancha" class="btn-retroceder"
                                    @click="reservationStore.volverAEspacios()" />
                            </div>

                            <h3 class="section-title text-large mb-header">
                                Elige tu Horario de Juego
                            </h3>

                            <div class="horario-grid-container">

                                <div class="panel-izquierdo">
                                    <div class="info-box">
                                        <i class="pi pi-clock"></i>
                                        <p class="texto-instrucciones">Selecciona tu hora de inicio y fin. Recuerda que
                                            la reserva <strong class="texto-resaltado">máxima es de 2 horas</strong>.
                                        </p>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label">Hora de Inicio</label>
                                        <Select v-model="horaInicioTemp" :options="opcionesHoras"
                                            placeholder="Ej. 09:00" class="w-full custom-select" appendTo="self" />
                                    </div>

                                    <div class="form-group mt-medium">
                                        <label class="form-label">Hora de Fin</label>
                                        <Select v-model="horaFinTemp" :options="opcionesHoras" placeholder="Ej. 11:00"
                                            class="w-full custom-select" appendTo="self" />
                                    </div>

                                    <div class="mensajes-validacion-container">
                                        <div v-if="errorValidacion" class="mensaje-error-box">
                                            <i class="pi pi-exclamation-circle"></i>
                                            <span>{{ errorValidacion }}</span>
                                        </div>

                                        <div v-else-if="esHorarioValidoParaPreview" class="mensaje-exito-box">
                                            <i class="pi pi-check-circle"></i>
                                            <span>¡Horario disponible y listo para reservar!</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="panel-derecho">
                                    <h4 class="calendar-title">Disponibilidad de Hoy</h4>

                                    <div class="calendar-visual-box">
                                        <div class="calendario-grid"
                                            :style="{ gridTemplateRows: `repeat(${totalHoras}, 60px)` }">

                                            <div v-for="i in totalHoras" :key="i" class="fila-hora"
                                                :style="{ gridRow: `${i} / ${i + 1}` }">
                                                <span class="etiqueta-hora">{{ i + horaApertura - 1 }}:00</span>
                                                <div class="linea-divisoria"></div>
                                            </div>

                                            <template v-if="horariosDisponibles && horariosDisponibles.length">
                                                <div v-for="(bloque, index) in horariosDisponibles" :key="index"
                                                    class="evento-bloque"
                                                    :class="bloque.tipo === 'sesion' ? 'evento-sesion' : 'reserva'"
                                                    :style="{ gridRow: calcularPosicionGrid(bloque.inicio, bloque.fin) }">
                                                    <div class="evento-info">
                                                        <span class="evento-titulo">
                                                            <i
                                                                :class="bloque.tipo === 'sesion' ? 'pi pi-bolt' : 'pi pi-user'"></i>
                                                            {{ bloque.tipo === 'sesion' ? 'Clase Programada' : 'Reserva de Socio'}}
                                                            


                                                            <br>
                                                        </span>
                                                        <span class="evento-horas">{{ formatearHora(bloque.inicio) }} -
                                                            {{ formatearHora(bloque.fin) }}</span>

                                                    </div>
                                                </div>
                                            </template>

                                            <div v-if="esHorarioValidoParaPreview" class="evento-bloque evento-preview"
                                                :style="{ gridRow: calcularPosicionGrid(horaInicioTemp, horaFinTemp) }">
                                                <div class="evento-info">
                                                    <span class="evento-titulo">
                                                        <i class="pi pi-sparkles"></i> Tu Reserva
                                                    </span>
                                                    <br>
                                                    <span class="evento-horas">{{ horaInicioTemp }} - {{
                                                        horaFinTemp }}</span>

                                                </div>
                                            </div>



                                            <div v-if="!cargando && horariosDisponibles.length === 0 && !esHorarioValidoParaPreview"
                                                class="sin-eventos">
                                                No hay actividades programadas.
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>


                            <div class="action-bottom-right">
                                <Button label="Confirmar Horario" icon="pi pi-check" iconPos="right"
                                    class="btn-retroceder" :disabled="!esHorarioValidoParaPreview"
                                    @click="validarHorario()" />
                            </div>

                        </div>
                    </StepPanel>

                    <StepPanel value="4">...</StepPanel>
                    <StepPanel value="5">...</StepPanel>

                </StepPanels>
            </Stepper>
        </div>
    </div>
</template>

<style scoped>
/* =========================================
   1. CONTENEDORES PRINCIPALES (LIMPIEZA TW)
========================================= */
.page-wrapper {
    padding: 1rem;
    background-color: var(--p-surface-50);
    min-height: 100vh;
    display: flex;
    justify-content: center;
    font-family: var(--p-font-family);
}

@media (min-width: 768px) {
    .page-wrapper {
        padding: 1.5rem;
    }
}

.main-card {
    width: 100%;
    max-width: 80rem;
    /* 7xl */
    background-color: #ffffff;
    padding: 1.5rem;
    border-radius: 1rem;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
    /* shadow-xl */
    border: 1px solid var(--p-surface-100);
}

@media (min-width: 768px) {
    .main-card {
        padding: 2.5rem;
    }
}

.main-title {
    font-size: 1.875rem;
    /* 3xl */
    font-weight: 800;
    color: var(--p-surface-900);
    margin-bottom: 2.5rem;
    /* mb-10 */
    text-align: center;
    letter-spacing: -0.025em;
    /* tracking-tight */
}

/* =========================================
   2. DISEÑO PROFESIONAL DEL STEPPER
========================================= */
/* =========================================
   2. DISEÑO ULTRA PRO DEL STEPPER
========================================= */
:deep(.p-stepper) {
    width: 100%;
}

/* Contenedor principal de la lista */
:deep(.p-steplist) {
    margin-bottom: 3.5rem !important; /* Más espacio para respirar hacia abajo */
    padding: 0.5rem 1rem !important;
}

/* --- CÍRCULOS (Pasos Inactivos / Futuros) --- */
:deep(.p-step-number) {
    font-family: var(--p-font-family) !important;
    font-weight: 700 !important;
    width: 2.75rem !important; /* Un poco más grandes */
    height: 2.75rem !important;
    font-size: 1.15rem !important;
    background-color: #ffffff !important;
    color: var(--p-surface-400) !important;
    border: 2px solid var(--p-surface-200) !important;
    border-radius: 50% !important;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
    box-shadow: 0 2px 4px -1px rgba(0, 0, 0, 0.05) !important; /* Sombra sutil */
}

/* --- TEXTO DE LOS PASOS --- */
:deep(.p-step-title) {
    font-family: var(--p-font-family) !important;
    font-weight: 600 !important;
    color: var(--p-surface-400) !important;
    margin-left: 0.75rem !important;
    font-size: 1.05rem !important;
    transition: all 0.3s ease !important;
}

/* --- PASO ACTIVO (Donde estás posicionado) --- */
:deep(.p-step-active .p-step-number) {
    background-color: var(--p-primary-600) !important; /* Relleno azul sólido */
    color: #ffffff !important; /* Número blanco */
    border: 2px solid var(--p-primary-600) !important;
    /* Efecto de anillo exterior (glow) que lo hace ver súper pro */
    box-shadow: 0 0 0 6px var(--p-primary-100), 0 4px 6px -1px rgba(0, 0, 0, 0.1) !important;
    transform: scale(1.1); /* Ligero aumento de tamaño (efecto pop) */
}

:deep(.p-step-active .p-step-title) {
    color: var(--p-primary-700) !important;
    font-weight: 800 !important;
    transform: translateX(4px); /* Pequeño desplazamiento a la derecha para destacar */
}

/* --- LÍNEAS CONECTORAS --- */
:deep(.p-steplist-separator) {
    height: 3px !important; /* De 1px a 3px para darle fuerza visual */
    background-color: var(--p-surface-200) !important;
    border-radius: 2px !important;
    margin: 0 1.5rem !important; /* Separación de los círculos */
    transition: background-color 0.3s ease;
}

/* Quitamos los estilos base de los botones invisibles de PrimeVue para no estorbar el diseño */
:deep(.p-step) {
    background: transparent !important;
    border: none !important;
    box-shadow: none !important;
    padding: 0.5rem !important;
}

/* =========================================
   3. ESTILOS VISTAS INTERNAS (LIMPIEZA TW)
========================================= */
.step-content-wrapper {
    padding: 1rem 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    width: 100%;
}

/* Títulos internos */
.section-title {
    font-size: 1.5rem;
    /* 2xl */
    font-weight: 700;
    color: var(--p-surface-900);
    margin-bottom: 2.5rem;
    /* mb-10 */
    text-align: center;
}

@media (min-width: 768px) {
    .section-title {
        font-size: 1.875rem;
        /* 3xl */
    }
}

/* Loader */
.loader-container {
    text-align: center;
    padding: 2.5rem 0;
    color: var(--p-surface-500);
}

.loader-icon {
    font-size: 2.25rem;
    /* 4xl */
    margin-bottom: 1rem;
}

/* =========================================
   4. TARJETAS DE DEPORTES (Ya funcionaban)
========================================= */
.contenedor-tarjetas {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 2rem;
    /* 8 */
    width: 100%;
    max-width: 72rem;
    /* 6xl */
    margin: 0 auto;
}

button.tarjeta-deporte {
    background-color: var(--p-primary-700);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    width: 100%;
    max-width: 250px;
    aspect-ratio: 1 / 1;
    border-radius: 1rem;
    /* 2xl */
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    /* shadow-lg */
    padding: 1.5rem;
    text-decoration: none;
    font-family: var(--p-font-family);
}

button.tarjeta-deporte:hover {
    background-color: var(--p-primary-800);
    transform: translateY(-5px);
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    /* shadow-2xl */
}

.icono-contenedor {
    display: flex;
    justify-content: center;
    align-items: center;
    width: 75px;
    height: 75px;
    color: #ffffff;
    transition: transform 0.3s ease;
}

button.tarjeta-deporte:hover .icono-contenedor {
    transform: scale(1.1);
}

.icono-svg,
.icono-svg svg {
    width: 100%;
    height: 100%;
    fill: currentColor;
}

.texto-disciplina {
    color: #ffffff;
    font-weight: 700;
    font-size: 1.25rem;
    /* xl */
    text-align: center;
    margin-top: 1.5rem;
    /* 6 */
}

/* =========================================
   5. SELECCIÓN DE CANCHAS (LIMPIEZA TW)
========================================= */
.canchas-container {
    width: 100%;
    max-width: 64rem;
    /* 5xl */
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding-top: 0.5rem;
}

.action-bar {
    width: 100%;
    display: flex;
    justify-content: flex-start;
    margin-bottom: 2.5rem;
    /* mb-10 */
}

/* Botón "Elegir otro deporte" (Ajuste de estilo native) */
/* Botón "Elegir otro deporte" (Recuperando el color sólido) */
:deep(.btn-retroceder.p-button) {
    font-weight: 700 !important;
    color: #ffffff !important;
    /* Texto blanco */
    background-color: var(--p-primary-700) !important;
    /* Tu azul principal */
    padding: 0.625rem 1.5rem !important;
    /* Padding un poco más amplio */
    border-radius: 0.75rem !important;
    /* xl */
    transition: all 0.2s ease !important;
    border: none !important;
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
}

:deep(.btn-retroceder.p-button:hover) {
    color: #ffffff !important;
    background-color: var(--p-primary-800) !important;
    /* Azul más oscuro en hover */
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1) !important;
    transform: translateY(-2px) !important;
}

.header-canchas {
    text-align: center;
    margin-bottom: 2rem;
}

.main-title-canchas {
    font-size: 1.4rem;
    /* 3xl */
    font-weight: 600;
    color: var(--p-surface-900);
    letter-spacing: -0.025em;
}

@media (min-width: 768px) {
    .main-title-canchas {
        font-size: 2.25rem;
        /* 4xl */
    }
}

.highlight-deporte {
    color: var(--p-primary-600);
    padding-bottom: 0.25rem;
    display: inline-block;
    margin-top: 0.5rem;
}

@media (min-width: 768px) {
    .highlight-deporte {
        margin-top: 0;
    }
}

/* Capacidad Info */
.capacidad-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    padding: 0.75rem 1.5rem;
    margin-bottom: 3.5rem;
    /* mb-14 */
    background-color: var(--p-primary-50);
    border: 1px solid var(--p-primary-100);
    border-radius: 9999px;
    /* rounded-full */
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    /* shadow-sm */
}

@media (min-width: 768px) {
    .capacidad-badge {
        padding: 1rem 2.5rem;
    }
}

.capacidad-icon {
    font-size: 1.5rem;
    color: var(--p-primary-600);
}

.capacidad-text {
    font-weight: 500;
    font-size: 1.125rem;
    /* lg */
    color: var(--p-primary-900);
}

.capacidad-number {
    font-weight: 700;
    font-size: 1.25rem;
    /* xl */
}

/* Grid Canchas */
.grid-canchas {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.25rem;
    width: 100%;
}

@media (min-width: 1024px) {
    .grid-canchas {
        grid-template-columns: repeat(2, 1fr);
        gap: 1.5rem;
    }
}

/* Tarjeta Cancha */
.cancha-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    padding: 1rem;
    border-radius: 1rem;
    border: 2px solid var(--p-surface-200);
    transition: all 0.3s ease;
    text-align: left;
    background-color: #ffffff;
    cursor: pointer;
    font-family: var(--p-font-family);
}

@media (min-width: 768px) {
    .cancha-card {
        padding: 1.25rem;
    }
}

/* Estado Disponible */
.cancha-card.is-available {
    border-color: var(--p-surface-200);
}

.cancha-card.is-available:hover {
    border-color: var(--p-primary-500);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    /* shadow-md */
    transform: translateY(-0.25rem);
    /* -translate-y-1 */
}

/* Estado Mantenimiento */
.cancha-card.is-disabled {
    cursor: not-allowed;
    opacity: 0.6;
    background-color: var(--p-surface-100);
    border-color: var(--p-surface-200);
}

.cancha-left-info {
    display: flex;
    align-items: center;
    gap: 1rem;
}

@media (min-width: 768px) {
    .cancha-left-info {
        gap: 1.5rem;
    }
}

.cancha-icon-wrapper {
    flex-shrink: 0;
    width: 3.5rem;
    height: 3.5rem;
    border-radius: 9999px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background-color 0.3s;
}

@media (min-width: 768px) {
    .cancha-icon-wrapper {
        width: 4rem;
        height: 4rem;
    }
}

.cancha-card.is-available .cancha-icon-wrapper {
    background-color: var(--p-primary-50);
    color: var(--p-primary-600);
}

.cancha-card.is-available:hover .cancha-icon-wrapper {
    background-color: var(--p-primary-100);
    color: var(--p-primary-700);
}

.cancha-card.is-disabled .cancha-icon-wrapper {
    background-color: #e5e7eb;
    /* gray-200 */
    color: #6b7280;
    /* gray-500 */
}

.icon-cancha-svg {
    width: 1.75rem;
    height: 1.75rem;
    fill: currentColor;
}

@media (min-width: 768px) {
    .icon-cancha-svg {
        width: 2rem;
        height: 2rem;
    }
}

.cancha-name {
    font-weight: 700;
    font-size: 1.125rem;
    /* lg */
    color: var(--p-surface-900);
}

@media (min-width: 768px) {
    .cancha-name {
        font-size: 1.25rem;
        /* xl */
    }
}

.cancha-status-badge {
    flex-shrink: 0;
    margin-left: 1rem;
    padding: 0.375rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.6875rem;
    /* 11px */
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    /* tracking-wider */
}

@media (min-width: 768px) {
    .cancha-status-badge {
        padding: 0.5rem 1rem;
        font-size: 0.75rem;
        /* xs */
    }
}

.status-available {
    background-color: #dcfce7;
    /* green-100 */
    color: #166534;
    /* green-800 */
}

.status-maintenance {
    background-color: #fee2e2;
    /* red-100 */
    color: #991b1b;
    /* red-800 */
}

/* =========================================
   6. VISTAS STEP 2 (LIMPIEZA TW)
========================================= */
.step-content-inner {
    width: 100%;
    max-width: 64rem;
    /* 5xl */
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding-top: 0.5rem;
}

.btn-secondary-outline {
    background-color: transparent !important;
    border: none !important;
    font-weight: 700 !important;
    color: var(--p-surface-600) !important;
    padding: 0.5rem 1rem !important;
}

.btn-secondary-outline:hover {
    color: var(--p-surface-900) !important;
    background-color: var(--p-surface-100) !important;
}

.btn-primary-action {
    padding: 0.75rem 2rem !important;
    font-weight: 700 !important;
    font-size: 1.125rem !important;
}

/* Espaciadores útiles */
.mb-action-bar {
    margin-bottom: 2.5rem;
}

.mb-header {
    margin-bottom: 2.5rem;
}

.mt-final-action {
    margin-top: 2.5rem;
}

/* =========================================
   7. STEP 3: LAYOUT DE HORARIOS
========================================= */

/* El contenedor principal dividido en 2 columnas */
.horario-grid-container {
    display: grid;
    grid-template-columns: 1fr;
    /* Móvil: 1 columna */
    gap: 2.5rem;
    margin-bottom: 3rem;
}

@media (min-width: 1024px) {
    .horario-grid-container {
        grid-template-columns: 1fr 1.5fr;
        /* Desktop: Izquierda más chica, Derecha más grande */
    }
}

/* --- PANEL IZQUIERDO --- */
.panel-izquierdo {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.info-box {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    background-color: var(--p-primary-50);
    padding: 1rem 1.25rem;
    border-radius: 0.75rem;
    border: 1px solid var(--p-primary-100);
    color: var(--p-primary-900);
    font-size: 0.95rem;
    line-height: 1.5;
}

.info-box i {
    color: var(--p-primary-600);
    margin-top: 0.15rem;
    font-size: 1.25rem;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.form-label {
    font-weight: 700;
    color: var(--p-surface-800);
    font-size: 1rem;
}

/* Modificamos el Select de PrimeVue para que combine con tus inputs */
:deep(.custom-select) {
    border-radius: 0.75rem !important;
    border: 1px solid var(--p-surface-300) !important;
    padding: 0.25rem !important;
    font-family: var(--p-font-family) !important;
}

:deep(.custom-select:hover) {
    border-color: var(--p-primary-500) !important;
}

/* --- PANEL DERECHO --- */
.panel-derecho {
    background-color: #ffffff;
    border: 1px solid var(--p-surface-200);
    border-radius: 1rem;
    padding: 1.5rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
}

.calendar-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--p-surface-900);
    margin-bottom: 1.5rem;
    text-align: center;
    border-bottom: 2px solid var(--p-surface-100);
    padding-bottom: 1rem;
}

.calendar-visual-box {
    width: 100%;
    height: 480px;
    background-color: #ffffff;
    border-radius: 1rem;
    border: 1px solid var(--p-surface-200);
    overflow-y: auto;
    position: relative;
    box-shadow: inset 0 2px 4px 0 rgba(0, 0, 0, 0.02);

    /* ESTO ES LO CLAVE: Sobrescribe cualquier comportamiento flex anterior */
    display: block;
}

/* --- BOTÓN FINAL --- */
.action-bottom-right {
    display: flex;
    justify-content: flex-end;
    width: 100%;
    border-top: 2px solid var(--p-surface-100);
    padding-top: 2rem;
}

/* =========================================
   8. VISTA TIPO GOOGLE CALENDAR
========================================= */
/* =========================================
   ESTILOS MEJORADOS PARA LOS SELECTS
========================================= */
:deep(.custom-select) {
    border-radius: 0.75rem !important;
    border: 1px solid var(--p-surface-300) !important;
    font-family: var(--p-font-family) !important;
    display: flex;
    align-items: center;
    height: 3rem;
    /* Lo hacemos más altito y elegante */
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
}

:deep(.custom-select:hover) {
    border-color: var(--p-primary-500) !important;
}

/* Mejora la lista desplegable del Select */
:deep(.p-select-list-container) {
    font-family: var(--p-font-family) !important;
    padding: 0.5rem;
}

:deep(.p-select-option) {
    border-radius: 0.5rem;
    margin-bottom: 0.25rem;
    transition: background-color 0.2s;
}

/* =========================================
   EL CALENDARIO Y LA PREVISUALIZACIÓN
========================================= */


/* Ocultamos la scrollbar fea pero permitimos scroll */
.calendar-visual-box::-webkit-scrollbar {
    width: 6px;
}

.calendar-visual-box::-webkit-scrollbar-thumb {
    background-color: var(--p-surface-300);
    border-radius: 10px;
}

.calendario-grid {
    display: grid;
    grid-template-columns: 70px 1fr;
    /* Más espacio para la hora a la izquierda */
    position: relative;
    min-height: 100%;
    padding-bottom: 1rem;
}

.fila-hora {
    grid-column: 1 / -1;
    display: grid;
    grid-template-columns: 70px 1fr;
}

.etiqueta-hora {
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--p-surface-500);
    text-align: right;
    padding-right: 1rem;
    transform: translateY(-0.6rem);
}

.linea-divisoria {
    border-top: 1px dashed var(--p-surface-200);
    /* Línea punteada más limpia */
    width: 100%;
}

/* Los bloques dinámicos */
.evento-bloque {
    grid-column: 2 / 3;
    margin: 2px 15px 2px 0;
    /* Más margen derecho */
    border-radius: 0.5rem;
    padding: 0.5rem 0.75rem;
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
    overflow: hidden;
    box-shadow: 0 2px 4px -1px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.evento-bloque:hover {
    transform: translateX(4px);
    /* Animación chula al pasar el mouse */
}

/* Estilo para LA PREVISUALIZACIÓN en tiempo real */
.evento-preview {
    background-color: var(--p-primary-50);
    border: 2px var(--p-primary-500);
    color: var(--p-primary-700);
    z-index: 10;
    /* Para que quede encima de las líneas */
    animation: pulse-preview 2s infinite ease-in-out;
}

@keyframes pulse-preview {
    0% {
        opacity: 0.7;
        box-shadow: 0 0 0 0 rgba(37, 99, 235, 0.4);
    }

    50% {
        opacity: 1;
        box-shadow: 0 0 0 6px rgba(37, 99, 235, 0);
    }

    100% {
        opacity: 0.7;
        box-shadow: 0 0 0 0 rgba(37, 99, 235, 0);
    }
}

.evento-sesion {
    background-color: var(--p-primary-600);
    color: #ffffff;
    border-left: 5px solid var(--p-primary-800);
}

.reserva {
    /* Asegúrate de que el nombre de la clase coincida con tu template */
    background-color: #f97316;
    color: #ffffff;
    border-left: 5px solid #c2410c;
}

/* --- MEJORAS DEL SELECT Y TEXTOS (Izquierda) --- */
.texto-instrucciones {
    font-size: 1.05rem;
    /* Texto de instrucciones más grande */
}

.texto-resaltado {
    font-size: 1.15rem;
    /* El "2 horas" aún más grande y notorio */
    color: var(--p-primary-800);
}

/* Espaciamos los números del dropdown para que no se amontonen */
:deep(.p-select-list-container) {
    padding: 0.5rem !important;
}

:deep(.p-select-option) {
    padding: 0.75rem 1.25rem !important;
    /* Más espacio para respirar */
    font-size: 1.05rem !important;
    margin-bottom: 0.25rem !important;
    border-radius: 0.5rem !important;
    transition: background-color 0.2s;
}

/* --- COLOR DE LA PREVISUALIZACIÓN (Verde Esmeralda) --- */
.evento-preview {
    background-color: #ecfdf5 !important;
    /* Verde muy clarito */
    border: 2px dashed #10b981 !important;
    /* Borde punteado esmeralda */
    border-left: 5px solid #10b981 !important;
    color: #065f46 !important;
    /* Texto verde oscuro */
    z-index: 10;
    /* Se pone por encima de todo */
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2) !important;
    animation: pulse-preview 2s infinite ease-in-out;
}

/* Animación de latido suave para indicar que está en "borrador" */
@keyframes pulse-preview {
    0% {
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4);
    }

    70% {
        box-shadow: 0 0 0 8px rgba(16, 185, 129, 0);
    }

    100% {
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0);
    }
}

/* Centrado vertical perfecto en las horas */
.etiqueta-hora {
    transform: translateY(-0.6rem);
    /* Ajuste milimétrico para alinear el texto con la raya */
}

/* --- ZONA DE MENSAJES DE VALIDACIÓN --- */
.mensajes-validacion-container {
    margin-top: 0.5rem; /* Espaciado extra entre el select y el mensaje */
}

/* CAJA DE MENSAJE DE ERROR */
.mensaje-error-box {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    background-color: #fef2f2; /* Rojo muy claro */
    border: 1px solid #fca5a5; /* Borde rojo suave */
    color: #b91c1c; /* Texto rojo oscuro */
    padding: 1rem 1.25rem;
    border-radius: 0.75rem;
    margin-top: 1rem;
    font-weight: 500;
    font-size: 0.95rem;
    animation: fadeInMessage 0.3s ease-out;
}

.mensaje-error-box i {
    font-size: 1.25rem;
    color: #ef4444; /* Rojo vibrante para el ícono */
}

/* CAJA DE MENSAJE DE ÉXITO (NUEVO) */
.mensaje-exito-box {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    background-color: #ecfdf5; /* Verde esmeralda muy claro */
    border: 1px solid #6ee7b7; /* Borde verde suave */
    color: #047857; /* Texto verde esmeralda oscuro */
    padding: 1rem 1.25rem;
    border-radius: 0.75rem;
    margin-top: 1rem;
    font-weight: 500;
    font-size: 0.95rem;
    animation: fadeInMessage 0.3s ease-out;
}

.mensaje-exito-box i {
    font-size: 1.25rem;
    color: #10b981; /* Verde esmeralda vibrante para el ícono */
}

/* Animación unificada para ambos mensajes */
@keyframes fadeInMessage {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* =========================================
   ALERTA DE NAVEGACIÓN GLOBAL (ESTÁTICA)
========================================= */
.alerta-navegacion {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    max-width: 40rem;
    margin: -1rem auto 2.5rem auto; 
    background-color: #fef2f2; /* Rojo muy claro */
    border: 1px solid #fca5a5; /* Borde rojo suave */
    color: #b91c1c; /* Texto rojo oscuro */
    padding: 0.85rem 1.5rem;
    border-radius: 0.75rem; 
    font-weight: 600;
    font-size: 0.95rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    animation: fadeInStatic 0.2s ease-out; /* Solo entrada */
}

.alerta-navegacion i {
    font-size: 1.25rem;
    color: #ef4444;
}

@keyframes fadeInStatic {
    from { opacity: 0; transform: translateY(-5px); }
    to { opacity: 1; transform: translateY(0); }
}

/* =========================================
   MODAL DE BORRADOR (DRAFT) MINIMALISTA Y PRO
========================================= */

/* 1. Resetear PrimeVue y poner el borde general */
:deep(.modal-borrador-minimal.p-dialog),
:deep(.modal-borrador-minimal) {
    width: 90vw !important;
    max-width: 480px !important;
    border-radius: 12px !important;
    border: 2px solid var(--p-primary-500) !important;
    background-color: #ffffff !important;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15) !important;
    font-family: var(--p-font-family) !important;
    overflow: hidden !important;
}

/* Apagamos el padding rebelde de PrimeVue */
:deep(.modal-borrador-minimal .p-dialog-header),
:deep(.modal-borrador-minimal .p-dialog-content),
:deep(.modal-borrador-minimal .p-dialog-footer) {
    padding: 0 !important;
    background: transparent !important;
    border: none !important;
}

/* 2. NUESTROS CONTENEDORES CON PADDING PERFECTO */
.custom-modal-header {
    padding: 1.75rem 2rem 0.5rem 2rem; /* Espacio arriba y a los lados */
}

.custom-modal-header h3 {
    font-weight: 700;
    font-size: 1.35rem;
    color: var(--p-surface-900);
    margin: 0;
}

.custom-modal-body {
    padding: 0.5rem 2rem 1.5rem 2rem; /* Espacio a los lados alineado con el header */
}

.custom-modal-footer {
    padding: 0 2rem 2rem 2rem; /* Espacio abajo y a los lados */
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
}

/* 3. Textos internos */
.modal-description {
    font-size: 1.05rem;
    color: var(--p-surface-600);
    line-height: 1.5;
    margin: 0 0 1.5rem 0;
}

.modal-question {
    margin: 1.5rem 0 0 0;
    text-align: center;
    font-weight: 600;
    color: var(--p-surface-900);
}

/* 4. Lista de Detalles Limpia */
.draft-details-list {
    background-color: var(--p-surface-50);
    border: 1px solid var(--p-surface-200);
    border-radius: 8px;
    padding: 1rem 1.25rem;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.detail-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.detail-label {
    color: var(--p-surface-500);
    font-size: 0.9rem;
    font-weight: 500;
}

.detail-value {
    color: var(--p-primary-900);
    font-size: 1.05rem;
    font-weight: 700;
}

/* 5. Botones */
:deep(.btn-descartar.p-button) {
    background-color: transparent !important;
    border: 1px solid var(--p-surface-300) !important;
    color: var(--p-surface-600) !important;
    font-weight: 600 !important;
    padding: 0.625rem 1.25rem !important;
    border-radius: 0.75rem !important;
    transition: all 0.2s ease !important;
}

:deep(.btn-descartar.p-button:hover) {
    background-color: var(--p-surface-100) !important;
    border-color: var(--p-surface-400) !important;
    color: var(--p-surface-900) !important;
}

:deep(.btn-continuar.p-button) {
    background-color: var(--p-primary-600) !important;
    color: #ffffff !important;
    font-weight: 700 !important;
    padding: 0.625rem 1.5rem !important;
    border-radius: 0.75rem !important;
    border: none !important;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1) !important;
    transition: all 0.2s ease !important;
}

:deep(.btn-continuar.p-button:hover) {
    background-color: var(--p-primary-700) !important;
    transform: translateY(-2px) !important;
}

/* Ajuste para celulares */
@media (max-width: 600px) {
    .custom-modal-footer {
        flex-direction: column-reverse;
    }
    :deep(.btn-descartar.p-button),
    :deep(.btn-continuar.p-button) {
        width: 100% !important;
        justify-content: center !important;
    }
}

</style>

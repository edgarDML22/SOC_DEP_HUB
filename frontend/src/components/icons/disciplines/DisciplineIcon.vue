<script setup>
import { computed } from 'vue';

// Import all icons
import IconFutbol from './IconFutbol.vue';
import IconBasquetbol from './IconBasquetbol.vue';
import IconTenis from './IconTenis.vue';
import IconVoleibol from './IconVoleibol.vue';
import IconSquash from './IconSquash.vue';
import IconFrontenis from './IconFrontenis.vue';
import IconPadel from './IconPadel.vue';
import IconDefault from './IconDefault.vue';
import {
  IconDance, IconYoga, IconGym, IconMartialArts, IconSpinning,
  IconGymnastics, IconSwimming, IconAerobic, IconBarre,
  IconHigieneColumna, IconJazz, IconMeditation, IconPilates, IconZumba, IconBaby
} from '../../icons';

const props = defineProps({
  name: {
    type: String,
    default: ''
  },
  icon: {
    type: String,
    default: ''
  }
});

const iconComponent = computed(() => {
  const iconToUse = props.icon || props.name;
  if (!iconToUse) return IconDefault;
  const n = iconToUse.toLowerCase();
  
  if (n.includes('basquetbol') || n.includes('baloncesto') || n.includes('basketball')) return IconBasquetbol;
  if (n.includes('frontenis')) return IconFrontenis;
  if (n.includes('futbol') || n.includes('fútbol') || n.includes('soccer')) return IconFutbol;
  if (n.includes('padel') || n.includes('pádel')) return IconPadel;
  if (n.includes('squash')) return IconSquash;
  if (n.includes('tenis') || n.includes('tennis')) return IconTenis;
  if (n.includes('voleibol') || n.includes('volleyball')) return IconVoleibol;
  if (n.includes('aerobics') || n.includes('acondicionamiento') || n.includes('entrenamiento')) return IconAerobic;
  if (n.includes('jazz')) return IconJazz;
  if (n.includes('zumba')) return IconZumba;
  if (n.includes('baile')) return IconDance;
  if (n.includes('meditación') || n.includes('meditacion')) return IconMeditation;
  if (n.includes('pilates')) return IconPilates;
  if (n.includes('barre')) return IconBarre;
  if (n.includes('yoga')) return IconYoga;
  if (n.includes('columna')) return IconHigieneColumna;
  if (n.includes('gym') || n.includes('gimnasio') || n.includes('pesas') || n.includes('fuerza') || n.includes('crossfit') || n.includes('funcional')) return IconGym;
  if (n.includes('tae kwon do') || n.includes('artes marciales') || n.includes('karate') || n.includes('box')) return IconMartialArts;
  if (n.includes('spinning') || n.includes('bici')) return IconSpinning;
  if (n.includes('gimnasia')) return IconGymnastics;
  if (n.includes('natación') || n.includes('natacion') || n.includes('acuatico') || n.includes('acuático') || n.includes('alberca')) return IconSwimming;
  if (n.includes('ludoteca')) return IconBaby;
  
  return IconDefault;
});
</script>

<template>
  <component :is="iconComponent" v-bind="$attrs" />
</template>

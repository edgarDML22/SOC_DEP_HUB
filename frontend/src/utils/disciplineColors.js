// Colores por disciplina (fondo suave + color de ícono), compartidos por sesiones y reservas.
const PALETTE = {
  emerald: { bg: 'bg-emerald-50 border-emerald-200/60 dark:bg-emerald-500/10 dark:border-emerald-500/20', icon: 'text-emerald-600 dark:text-emerald-300' },
  purple: { bg: 'bg-purple-50 border-purple-200/60 dark:bg-purple-500/10 dark:border-purple-500/20', icon: 'text-purple-600 dark:text-purple-300' },
  primary: { bg: 'bg-primary-50 border-primary-200/60 dark:bg-primary-500/10 dark:border-primary-500/20', icon: 'text-primary-600 dark:text-primary-300' },
  rose: { bg: 'bg-rose-50 border-rose-200/60 dark:bg-rose-500/10 dark:border-rose-500/20', icon: 'text-rose-600 dark:text-rose-300' },
  amber: { bg: 'bg-amber-50 border-amber-200/60 dark:bg-amber-500/10 dark:border-amber-500/20', icon: 'text-amber-600 dark:text-amber-300' },
  sky: { bg: 'bg-sky-50 border-sky-200/60 dark:bg-sky-500/10 dark:border-sky-500/20', icon: 'text-sky-600 dark:text-sky-300' },
  teal: { bg: 'bg-teal-50 border-teal-200/60 dark:bg-teal-500/10 dark:border-teal-500/20', icon: 'text-teal-600 dark:text-teal-300' },
  green: { bg: 'bg-green-50 border-green-200/60 dark:bg-green-500/10 dark:border-green-500/20', icon: 'text-green-600 dark:text-green-300' },
  orange: { bg: 'bg-orange-50 border-orange-200/60 dark:bg-orange-500/10 dark:border-orange-500/20', icon: 'text-orange-600 dark:text-orange-300' },
  red: { bg: 'bg-red-50 border-red-200/60 dark:bg-red-500/10 dark:border-red-500/20', icon: 'text-red-600 dark:text-red-300' },
};

const RULES = [
  [['TENIS'], 'emerald'],
  [['MEDITAC', 'YOGA', 'PILATES'], 'purple'],
  [['SPINNING', 'CYCLE', 'BICI'], 'primary'],
  [['ZUMBA', 'BAILE', 'DANCE'], 'rose'],
  [['GYM', 'FITNESS', 'INSTRUCTOR', 'FUNCIONAL', 'CROSSFIT'], 'amber'],
  [['NATAC', 'ALBERCA', 'SWIM'], 'sky'],
  [['PADEL', 'PÁDEL', 'SQUASH'], 'teal'],
  [['FUTBOL', 'FÚTBOL', 'SOCCER'], 'green'],
  [['BASKET', 'BÁSQUET', 'BALONCESTO'], 'orange'],
  [['BOX', 'KICKBOX', 'KARATE', 'COMBATE'], 'red'],
];

const FALLBACK = ['primary', 'emerald', 'purple', 'rose', 'amber', 'sky', 'teal'];

export const getDisciplineColorClasses = (name) => {
  const cleanName = String(name || '').toUpperCase().trim();
  const match = RULES.find(([keys]) => keys.some((key) => cleanName.includes(key)));
  if (match) return PALETTE[match[1]];

  const charCodeSum = cleanName.split('').reduce((acc, char) => acc + char.charCodeAt(0), 0);
  return PALETTE[FALLBACK[charCodeSum % FALLBACK.length]];
};

// Paleta de tonos compartida (dashboard, modals, accesos directos).
// Cada tono trae su versión para modo claro y oscuro.
export const TONES = {
  blue: {
    solid: 'bg-gradient-to-br from-primary-500 to-primary-700',
    soft: 'bg-primary-50 dark:bg-primary-500/10',
    text: 'text-primary-700 dark:text-primary-300',
    ring: 'ring-primary-100 dark:ring-primary-500/20',
    bar: 'bg-gradient-to-r from-primary-600 to-sky-400',
    glow: 'bg-primary-400/25',
    shadow: 'shadow-[0_10px_20px_-10px_rgba(29,78,216,0.65)]',
  },
  emerald: {
    solid: 'bg-gradient-to-br from-emerald-400 to-emerald-600',
    soft: 'bg-emerald-50 dark:bg-emerald-500/10',
    text: 'text-emerald-700 dark:text-emerald-300',
    ring: 'ring-emerald-100 dark:ring-emerald-500/20',
    bar: 'bg-gradient-to-r from-emerald-500 to-teal-400',
    glow: 'bg-emerald-400/25',
    shadow: 'shadow-[0_10px_20px_-10px_rgba(5,150,105,0.65)]',
  },
  amber: {
    solid: 'bg-gradient-to-br from-amber-400 to-orange-500',
    soft: 'bg-amber-50 dark:bg-amber-500/10',
    text: 'text-amber-700 dark:text-amber-300',
    ring: 'ring-amber-100 dark:ring-amber-500/20',
    bar: 'bg-gradient-to-r from-amber-400 to-orange-400',
    glow: 'bg-amber-400/25',
    shadow: 'shadow-[0_10px_20px_-10px_rgba(234,88,12,0.6)]',
  },
  rose: {
    solid: 'bg-gradient-to-br from-rose-400 to-rose-600',
    soft: 'bg-rose-50 dark:bg-rose-500/10',
    text: 'text-rose-700 dark:text-rose-300',
    ring: 'ring-rose-100 dark:ring-rose-500/20',
    bar: 'bg-gradient-to-r from-rose-500 to-pink-400',
    glow: 'bg-rose-400/25',
    shadow: 'shadow-[0_10px_20px_-10px_rgba(225,29,72,0.6)]',
  },
  violet: {
    solid: 'bg-gradient-to-br from-violet-400 to-indigo-600',
    soft: 'bg-violet-50 dark:bg-violet-500/10',
    text: 'text-violet-700 dark:text-violet-300',
    ring: 'ring-violet-100 dark:ring-violet-500/20',
    bar: 'bg-gradient-to-r from-violet-500 to-indigo-400',
    glow: 'bg-violet-400/25',
    shadow: 'shadow-[0_10px_20px_-10px_rgba(109,40,217,0.6)]',
  },
};

export const toneOf = (tone) => TONES[tone] || TONES.blue;

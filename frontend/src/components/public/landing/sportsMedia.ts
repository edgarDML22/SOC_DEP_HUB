/**
 * FOTOGRAFÍAS DE DISCIPLINAS (carrusel del hero y marquesina)
 * Coloca tus imágenes en: frontend/src/assets/hero/
 * Los nombres conocidos (tenis, padel, natacion...) reciben título y descripción;
 * cualquier otra imagen se agrega al final usando su nombre de archivo como título.
 */
export interface SportSlide {
  id: string
  src: string
  title: string
  tagline: string
}

const localModules = import.meta.glob('/src/assets/hero/*.{jpg,jpeg,png,webp,avif}', {
  eager: true,
  import: 'default'
}) as Record<string, string>

const sportMeta: Record<string, { title: string; tagline: string }> = {
  tenis: { title: 'Tenis', tagline: 'Canchas profesionales con iluminación LED' },
  padel: { title: 'Pádel', tagline: 'Juega, compite y convive en comunidad' },
  natacion: { title: 'Natación', tagline: 'Alberca climatizada todo el año' },
  futbol: { title: 'Fútbol', tagline: 'Academia formativa para todas las edades' },
  basket: { title: 'Básquetbol', tagline: 'Entrena en equipo y compite en ligas internas' },
  correr: { title: 'Atletismo', tagline: 'Actívate en cada etapa de tu vida' }
}

const preferredOrder = Object.keys(sportMeta)

const toTitle = (id: string) =>
  id.replace(/[-_]+/g, ' ').replace(/\b\w/g, (letter) => letter.toUpperCase())

export const sportSlides: SportSlide[] = Object.entries(localModules)
  .map(([path, src]) => {
    const id = (path.split('/').pop() ?? 'deporte').replace(/\.[^.]+$/, '')
    const meta = sportMeta[id]
    return {
      id,
      src,
      title: meta?.title ?? toTitle(id),
      tagline: meta?.tagline ?? 'Vive el deporte en Britania Club'
    }
  })
  .sort((a, b) => {
    const rankA = preferredOrder.indexOf(a.id)
    const rankB = preferredOrder.indexOf(b.id)
    if (rankA === -1 && rankB === -1) return a.id.localeCompare(b.id)
    if (rankA === -1) return 1
    if (rankB === -1) return -1
    return rankA - rankB
  })

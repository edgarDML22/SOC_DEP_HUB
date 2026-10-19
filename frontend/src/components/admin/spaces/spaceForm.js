// Formulario de espacio compartido por "Nuevo espacio" y "Editar información".

export const emptySpaceForm = () => ({
  nombre_espacio: '',
  capacidad_maxima: 10,
  es_reserva_on_demand: true,
  es_clase_programada: false,
  es_uso_libre: false,
  estatus: 'ACTIVO',
  descripcion: '',
  disciplinas: [],
});

export const spaceToForm = (space) => ({
  nombre_espacio: space.nombre_espacio,
  capacidad_maxima: space.capacidad_maxima,
  es_reserva_on_demand: !!space.es_reserva_on_demand,
  es_clase_programada: !!space.es_clase_programada,
  es_uso_libre: !!space.es_uso_libre,
  estatus: space.estatus,
  descripcion: space.descripcion || '',
  disciplinas: (space.disciplinas || []).map((d) => d.id_disciplina),
});

export const validateSpaceForm = (form) => {
  const errors = {};
  if (!form.nombre_espacio?.trim()) errors.nombre_espacio = 'Escribe el nombre del espacio.';
  const capacidad = Number(form.capacidad_maxima);
  if (!Number.isInteger(capacidad) || capacidad < 1) errors.capacidad_maxima = 'Debe ser un número entero mayor a 0.';
  if (!form.es_reserva_on_demand && !form.es_clase_programada && !form.es_uso_libre) errors.uso = 'Elige al menos un tipo de uso.';
  return errors;
};

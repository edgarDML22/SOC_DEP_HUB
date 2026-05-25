import { jsPDF } from 'jspdf';
import { Chart } from 'chart.js/auto';
import logoSocDep from '@/assets/LogoSocDep.png';

const loadImage = (url) => {
  return new Promise((resolve) => {
    const img = new Image();
    img.crossOrigin = 'anonymous';
    img.onload = () => resolve(img);
    img.onerror = () => resolve(null);
    img.src = url;
  });
};


const getChartImage = (type, data, options = {}, aspectWidth = 1200, aspectHeight = 600) => {
  const scale = aspectWidth / 400; // Dynamic scale factor based on resolution width
  
  const canvas = document.createElement('canvas');
  canvas.width = aspectWidth;
  canvas.height = aspectHeight;
  canvas.style.position = 'absolute';
  canvas.style.left = '-9999px';
  canvas.style.top = '-9999px';
  canvas.style.visibility = 'hidden';
  canvas.style.width = `${aspectWidth / 2}px`;
  canvas.style.height = `${aspectHeight / 2}px`;
  document.body.appendChild(canvas);

  const ctx = canvas.getContext('2d');

  // Fill canvas with opaque white background
  ctx.fillStyle = '#ffffff';
  ctx.fillRect(0, 0, canvas.width, canvas.height);

  // Inject beautiful linear gradients, borders and spacing dynamically
  if (data.datasets && Array.isArray(data.datasets)) {
    data.datasets.forEach((dataset, dsIdx) => {
      // 1. Beautiful linear gradients for Bar and Line charts
      if (type === 'bar' || type === 'line') {
        const gradient = ctx.createLinearGradient(0, canvas.height, 0, 0);
        const colorVal = Array.isArray(dataset.backgroundColor) 
          ? dataset.backgroundColor[0] 
          : dataset.backgroundColor;
          
        if (colorVal === '#3b82f6' || colorVal === 'rgba(99, 102, 241, 0.85)' || colorVal === '#6366f1' || colorVal === '#2563eb') {
          // Vibrant Blue-to-Indigo Gradient (matches reference image)
          gradient.addColorStop(0, '#1e70eb'); // Deep blue
          gradient.addColorStop(1, '#4ea2f7'); // Bright sky blue
          dataset.backgroundColor = gradient;
        } else if (colorVal === '#10b981') {
          // Vivid Emerald-to-Teal Gradient
          gradient.addColorStop(0, '#059669'); // Emerald-600
          gradient.addColorStop(1, '#34d399'); // Emerald-400
          dataset.backgroundColor = gradient;
        } else if (colorVal === '#f43f5e' || colorVal === 'rgba(244, 63, 94, 0.1)' || colorVal === '#ef4444') {
          // Vivid Rose-Red Gradient
          gradient.addColorStop(0, '#e11d48'); // Rose-600
          gradient.addColorStop(1, '#fb7185'); // Rose-400
          if (type === 'line') {
            dataset.backgroundColor = 'rgba(244, 63, 94, 0.12)'; // Soft pastel background area
          } else {
            dataset.backgroundColor = gradient;
          }
        } else {
          // Generic elegant theme gradient (Purple/Violet)
          gradient.addColorStop(0, '#7c3aed');
          gradient.addColorStop(1, '#c084fc');
          dataset.backgroundColor = gradient;
        }

        // Rounded corners for Bar charts (Top corners only, dynamically scaled!)
        if (type === 'bar') {
          dataset.borderRadius = {
            topLeft: Math.round(5 * scale),
            topRight: Math.round(5 * scale),
            bottomLeft: 0,
            bottomRight: 0
          };
          dataset.borderSkipped = false;
        }
      }

      // 2. Beautiful gaps and rounded segments for Doughnut/Pie charts
      if (type === 'doughnut') {
        dataset.borderRadius = Math.round(2.5 * scale);
        dataset.spacing = Math.round(1.8 * scale); // Adds whitespace gap
        dataset.cutout = '65%'; // Modern thick ring cutout
        
        // Map demographic colors to ultra-vivid matching palettes
        if (dataset.backgroundColor && Array.isArray(dataset.backgroundColor)) {
          const premiumPalette = ['#2563eb', '#8b5cf6', '#ec4899', '#10b981', '#f59e0b', '#06b6d4', '#64748b'];
          dataset.backgroundColor = dataset.backgroundColor.map((col, idx) => premiumPalette[idx % premiumPalette.length]);
        }
      }

      // 3. Thick vivid lines with circle markers for Line charts (Dynamically scaled!)
      if (type === 'line') {
        dataset.borderWidth = Math.round(1.5 * scale); // Scaled elegant line thickness
        dataset.borderColor = '#e11d48'; // Rose
        dataset.pointRadius = Math.round(2 * scale);
        dataset.pointHoverRadius = Math.round(2.5 * scale);
        dataset.pointBackgroundColor = '#ffffff';
        dataset.pointBorderColor = '#e11d48';
        dataset.pointBorderWidth = Math.round(1 * scale);
        dataset.tension = 0.35; // Smooth tension bezier curve
      }
    });
  }

  const chartOptions = {
    ...options,
    responsive: false,
    animation: false,
    devicePixelRatio: 2, // High resolution crisp text and graphics
    plugins: {
      ...options.plugins,
      legend: {
        ...options.plugins?.legend,
        labels: {
          ...options.plugins?.legend?.labels,
          font: { 
            family: 'Helvetica', 
            size: Math.round(9.5 * scale), // Scaled font size
            weight: 'bold' 
          },
          color: '#1e293b', // slate-800
          boxWidth: Math.round(6 * scale), // Scaled box width
          padding: Math.round(5 * scale) // Scaled padding
        }
      }
    },
    scales: (type === 'bar' || type === 'line') ? {
      x: {
        ...options.scales?.x,
        ticks: {
          ...options.scales?.x?.ticks,
          font: { 
            family: 'Helvetica', 
            size: Math.round(8.5 * scale), // Scaled tick fonts
            weight: 'bold' 
          },
          color: '#475569' // Slate-600
        },
        grid: {
          display: false
        }
      },
      y: {
        ...options.scales?.y,
        ticks: {
          ...options.scales?.y?.ticks,
          font: { 
            family: 'Helvetica', 
            size: Math.round(8.5 * scale), // Scaled tick fonts
            weight: 'bold' 
          },
          color: '#475569' // Slate-600
        },
        grid: {
          color: '#e2e8f0', // slate-200 grid lines
          lineWidth: Math.round(0.4 * scale), // Scaled grid lines width
          drawBorder: false
        }
      }
    } : undefined
  };

  const chart = new Chart(canvas, {
    type,
    data,
    options: chartOptions
  });

  // Force synchronous render immediately
  chart.update('none');

  const imgData = canvas.toDataURL('image/png');
  
  // Clean up completely
  chart.destroy();
  canvas.remove();

  return imgData;
};

/**
 * Formatter helper to turn database enums like BLOQUEADO_TEMPORAL into Bloqueado Temporal.
 */
const formatEstatusPenalizacion = (estatus) => {
  if (!estatus || estatus === 'SIN_PENALIZACION') return 'Sin Sanción';
  return estatus.replace(/_/g, ' ').split(' ').map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase()).join(' ');
};

/**
 * Helper to draw a beautifully styled rounded card container for charts.
 */
const drawChartCard = (doc, title, subtitle, x, y, width, height) => {
  // Outer soft border/card container
  doc.setFillColor(255, 255, 255);
  doc.setDrawColor(226, 232, 240); // slate-200
  doc.setLineWidth(0.4);
  doc.roundedRect(x, y, width, height, 4, 4, 'FD'); // radius 4mm
  
  // Card header title
  doc.setFont('helvetica', 'bold');
  doc.setFontSize(10.5);
  doc.setTextColor(15, 23, 42); // slate-900 (vibrant slate)
  doc.text(title, x + 6, y + 8);
  
  // Subtitle
  doc.setFont('helvetica', 'normal');
  doc.setFontSize(8);
  doc.setTextColor(100, 116, 139); // slate-500
  doc.text(subtitle, x + 6, y + 13);
};



/**
 * Helper to draw a clean vector progress/bar chart in the PDF.
 * Draws a label, followed by a colored bar representing a percentage value.
 */
const drawProgressBar = (doc, label, value, max, x, y, width, height, barColor) => {
  const percentage = max > 0 ? Math.min(1, value / max) : 0;
  
  // Label and value
  doc.setFont('helvetica', 'bold');
  doc.setFontSize(9);
  doc.setTextColor(15, 23, 42); // slate-900
  doc.text(label, x, y - 2);
  
  doc.setFont('helvetica', 'bold');
  doc.text(`${value}`, x + width, y - 2, { align: 'right' });

  // Background track
  doc.setFillColor(241, 245, 249); // slate-100
  doc.rect(x, y, width, height, 'F');

  // Fill bar
  if (percentage > 0) {
    doc.setFillColor(barColor[0], barColor[1], barColor[2]);
    doc.rect(x, y, width * percentage, height, 'F');
  }
};

/**
 * Draws a metric/KPI card.
 */
const drawKPICard = (doc, title, value, subtext, x, y, width, height, themeColor) => {
  // Shadow-like border or simple card container
  doc.setFillColor(255, 255, 255);
  doc.rect(x, y, width, height, 'F');
  
  doc.setDrawColor(226, 232, 240); // slate-200
  doc.setLineWidth(1);
  doc.rect(x, y, width, height, 'S');

  // Colored left border accent
  doc.setFillColor(themeColor[0], themeColor[1], themeColor[2]);
  doc.rect(x, y, 4, height, 'F');

  // Title
  doc.setFont('helvetica', 'bold');
  doc.setFontSize(8);
  doc.setTextColor(100, 116, 139); // slate-500
  doc.text(title.toUpperCase(), x + 10, y + 12);

  // Large Value
  doc.setFont('helvetica', 'black');
  doc.setFontSize(18);
  doc.setTextColor(15, 23, 42); // slate-900
  doc.text(`${value}`, x + 10, y + 28);

  // Subtext
  doc.setFont('helvetica', 'normal');
  doc.setFontSize(7.5);
  doc.setTextColor(148, 163, 184); // slate-400
  doc.text(subtext, x + 10, y + 38);
};

export const generateExecutiveReportPDF = async (allStats, exporterName = 'Subgerente de Operaciones') => {
  const doc = new jsPDF({
    orientation: 'portrait',
    unit: 'mm',
    format: 'a4'
  });

  const totalPages = 6;
  const now = new Date();
  const dateFormatted = now.toLocaleDateString('es-ES', { year: 'numeric', month: 'long', day: 'numeric' });

  let logoImg = null;
  try {
    logoImg = await loadImage(logoSocDep);
  } catch (e) {
    console.error("Error loading logo image", e);
  }

  // ==========================================
  // PAGE 1: PORTADA (COVER PAGE)
  // ==========================================
  // Title Accent Stripe
  doc.setFillColor(15, 23, 42); // slate-900 (deep navy dark)
  doc.rect(0, 0, 210, 85, 'F');

  // Accent stripe
  doc.setFillColor(59, 130, 246); // blue-500
  doc.rect(0, 85, 210, 4, 'F');

  // Title text with Logo
  if (logoImg) {
    doc.addImage(logoImg, 'PNG', 15, 12, 18, 18);
    doc.setTextColor(255, 255, 255);
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(20);
    doc.text('REPORTE OPERATIVO Y EJECUTIVO DE BI', 15, 46);
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(11.5);
    doc.setTextColor(191, 219, 254); // blue-200
    doc.text('Consolidado General de Operaciones y Rendimiento del Club', 15, 56);
  } else {
    doc.setTextColor(255, 255, 255);
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(22);
    doc.text('REPORTE OPERATIVO Y EJECUTIVO DE BI', 15, 42);
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(13);
    doc.setTextColor(191, 219, 254); // blue-200
    doc.text('Consolidado General de Operaciones y Rendimiento del Club', 15, 54);
  }

  // Metadata block
  doc.setTextColor(15, 23, 42); // slate-900
  doc.setFont('helvetica', 'bold');
  doc.setFontSize(12);
  doc.text('DETALLES DEL INFORME', 15, 115);
  
  doc.setDrawColor(226, 232, 240);
  doc.line(15, 119, 195, 119);

  doc.setFont('helvetica', 'normal');
  doc.setFontSize(10);
  doc.setTextColor(71, 85, 105); // slate-600

  const metadata = [
    { label: 'Destinatario:', value: 'Dirección General y Subgerencia de Operaciones' },
    { label: 'Autor / Generado por:', value: exporterName },
    { label: 'Fecha de Emisión:', value: dateFormatted },
    { label: 'Estado del Centro:', value: 'Activo y en Monitoreo en Tiempo Real' },
    { label: 'Alcance del Reporte:', value: 'Consolidado General de Auditoría, Clases, Espacios, Torneos y Ludoteca' }
  ];

  let currentY = 129;
  metadata.forEach(item => {
    doc.setFont('helvetica', 'bold');
    doc.text(item.label, 15, currentY);
    doc.setFont('helvetica', 'normal');
    doc.text(item.value, 65, currentY);
    currentY += 10;
  });

  // Abstract / Intro box
  doc.setFillColor(248, 250, 252); // slate-50
  doc.rect(15, 185, 180, 58, 'F');
  doc.setDrawColor(203, 213, 225); // slate-300
  doc.rect(15, 185, 180, 58, 'S');

  doc.setFont('helvetica', 'bold');
  doc.setFontSize(11);
  doc.setTextColor(15, 23, 42);
  doc.text('RESUMEN EJECUTIVO', 22, 198);
  
  doc.setFont('helvetica', 'normal');
  doc.setFontSize(9.5);
  doc.setTextColor(71, 85, 105);
  
  const introParagraphs = [
    'Este documento técnico consolida los indicadores clave de rendimiento (KPIs) y analíticas operativas de',
    'las cinco áreas centrales de la plataforma SocDep HUB. Su propósito es proveer a la Subgerencia una visión',
    'unificada para la toma de decisiones basada en datos concretos del club deportivo, incluyendo la afluencia',
    'de reservaciones, niveles de uso de canchas, comportamiento académico de alumnos, auditoría de la',
    'cartera de socios activos e inactivos, y el control de alertas y estancia en la Ludoteca infantil.'
  ];
  
  currentY = 208;
  introParagraphs.forEach(line => {
    doc.text(line, 22, currentY);
    currentY += 6;
  });

  // Bottom decoration stripe
  doc.setFillColor(15, 23, 42);
  doc.rect(15, 276, 180, 1.5, 'F');

  // Page number footer for cover
  doc.setFont('helvetica', 'normal');
  doc.setFontSize(8);
  doc.setTextColor(148, 163, 184);
  doc.text('SOCDEP HUB - INFORME CONFIDENCIAL', 15, 286);
  doc.text(`Página 1 de ${totalPages}`, 195, 286, { align: 'right' });


  // ==========================================
  // PAGE 2: LUDOTECA Y DASHBOARD EN VIVO
  // ==========================================
  doc.addPage();
  
  // Header and Footer for Page 2
  const addHeaderAndFooter = (title, pageNum) => {
    if (logoImg) {
      doc.addImage(logoImg, 'PNG', 15, 5, 8, 8);
      doc.setFont('helvetica', 'normal');
      doc.setFontSize(8);
      doc.setTextColor(148, 163, 184);
      doc.text('SOCDEP HUB - REPORTE DE BI Y OPERACIONES', 26, 10);
    } else {
      doc.setFont('helvetica', 'normal');
      doc.setFontSize(8);
      doc.setTextColor(148, 163, 184);
      doc.text('SOCDEP HUB - REPORTE DE BI Y OPERACIONES', 15, 10);
    }
    doc.text(title.toUpperCase(), 195, 10, { align: 'right' });
    doc.setDrawColor(226, 232, 240);
    doc.setLineWidth(0.5);
    doc.line(15, 15, 195, 15);

    doc.line(15, 280, 195, 280);
    doc.text(`Generado por: ${exporterName} | ${dateFormatted}`, 15, 286);
    doc.text(`Página ${pageNum} de ${totalPages}`, 195, 286, { align: 'right' });
  };

  addHeaderAndFooter('1. Monitor y Control en Vivo (Ludoteca)', 2);

  // Content for Page 2
  doc.setFont('helvetica', 'bold');
  doc.setFontSize(14);
  doc.setTextColor(15, 23, 42);
  doc.text('1. Monitor y Control en Vivo (Ludoteca)', 15, 25);

  doc.setFont('helvetica', 'normal');
  doc.setFontSize(9.5);
  doc.setTextColor(71, 85, 105);
  doc.text('Monitoreo del día en curso para evaluar el uso del área de ludoteca y la afluencia operativa general.', 15, 30);

  // KPI cards (Only two cards now: Niños en Sala and Alertas de Tiempo. Occupying 87mm each)
  const kpis = allStats.ludoteca_kpis || {};
  const ninosActivos = kpis.ninos_activos ?? 0;
  const alertasTiempo = kpis.alertas_tiempo ?? 0;
  
  drawKPICard(doc, 'Niños en Sala', `${ninosActivos}`, 'Menores activos actualmente en ludoteca', 15, 38, 87, 45, [244, 63, 94]); // Rose
  drawKPICard(doc, 'Alertas de Tiempo', `${alertasTiempo}`, 'Niños con estancia mayor a 90 min', 108, 38, 87, 45, [239, 68, 68]); // Red

  // Visual Horizontal Bar Chart of top spaces today and Estatus Operativo doughnut in rounded cards
  const topEspacios = allStats.top_espacios || {};
  const topEspaciosLabels = topEspacios.labels || [];
  const topEspaciosData = topEspacios.data || [];
  
  const spacesData = topEspaciosLabels.map((lbl, idx) => ({
    label: lbl,
    value: topEspaciosData[idx] || 0
  })).slice(0, 5);

  const maxReservations = Math.max(...(topEspaciosData.length > 0 ? topEspaciosData : [1]), 5);

  // Left card: Espacios más demandados
  drawChartCard(doc, 'Espacios más Demandados Hoy', 'Tránsito acumulado de espacios en el transcurso del día de hoy', 15, 92, 105, 92);
  
  let barY = 112;
  if (spacesData.length === 0) {
    doc.setFont('helvetica', 'italic');
    doc.setFontSize(9);
    doc.setTextColor(148, 163, 184);
    doc.text('No se han registrado reservaciones en el transcurso del día hoy.', 21, 125);
  } else {
    spacesData.forEach(item => {
      drawProgressBar(doc, item.label, item.value, maxReservations, 21, barY, 93, 5, [99, 102, 241]); // Inside the card (x = 21, w = 93)
      barY += 14;
    });
  }

  // Right card: Estatus Operativo
  drawChartCard(doc, 'Estatus Operativo', 'Estatus de Reservas de hoy', 127, 92, 68, 92);

  const estatusOperativo = allStats.estatus_operativo || {};
  const chartEstatusData = {
    labels: (estatusOperativo.labels || []).map(l => l.replace('_', ' ')),
    datasets: [{
      data: estatusOperativo.data || [],
      backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#f43f5e', '#64748b']
    }]
  };
  
  // High quality square aspect ratio 800x800 doughnut chart to place perfectly in the card
  const estatusImg = getChartImage('doughnut', chartEstatusData, {
    plugins: {
      legend: { display: true, position: 'bottom', labels: { boxWidth: 10, padding: 8, font: { weight: 'bold' } } }
    }
  }, 800, 800);
  
  doc.addImage(estatusImg, 'PNG', 129, 114, 64, 60);

  // Bottom Card: Afluencia de Hoy Bar Chart
  const reservasHoy = allStats.reservas_hoy_hora || {};
  const reservasHoyData = {
    labels: reservasHoy.labels || [],
    datasets: [{
      label: 'Reservas por hora',
      data: reservasHoy.data || [],
      backgroundColor: 'rgba(99, 102, 241, 0.85)'
    }]
  };
  
  // Custom canvas aspect ratio to match precisely wide 176x33 (approx 5.33:1)
  const reservasHoyImg = getChartImage('bar', reservasHoyData, {
    plugins: { legend: { display: true, position: 'top', labels: { boxWidth: 10 } } },
    scales: {
      x: { grid: { display: false } },
      y: { beginAtZero: true, ticks: { stepSize: 1 } }
    }
  }, 1600, 300);
  
  drawChartCard(doc, 'Curva de Afluencia de Reservaciones por Horas (Hoy)', 'Tránsito horario acumulado en el día (Horas del día vs. Cantidad de reservas)', 15, 192, 180, 50);
  doc.addImage(reservasHoyImg, 'PNG', 17, 206, 176, 33);


  // Dashboard Summary text
  doc.setFillColor(248, 250, 252);
  doc.rect(15, 247, 180, 26, 'F');
  doc.setDrawColor(226, 232, 240);
  doc.rect(15, 247, 180, 26, 'S');

  doc.setFont('helvetica', 'bold');
  doc.setFontSize(8.5);
  doc.setTextColor(15, 23, 42);
  doc.text('ANÁLISIS OPERATIVO DEL DÍA', 20, 253);

  doc.setFont('helvetica', 'normal');
  doc.setFontSize(8);
  doc.setTextColor(71, 85, 105);
  
  const dailyAnalysisText = [
    `Al día de hoy, el centro deportivo SocDep HUB cuenta con un registro de ${ninosActivos} niños activos en la Ludoteca. Se han`,
    `detectado ${alertasTiempo} alertas por exceso de estancia (>90 minutos) que requieren atención. El monitor de afluencia general`,
    `y los espacios de mayor tránsito e instalaciones saturadas se visualizan consolidados en los gráficos del reporte.`
  ];

  let textY = 259;
  dailyAnalysisText.forEach(line => {
    doc.text(line, 20, textY);
    textY += 4.5;
  });


  // ==========================================
  // PAGE 3: RENDIMIENTO ACADÉMICO Y CLASES
  // ==========================================
  doc.addPage();
  addHeaderAndFooter('2. Rendimiento Académico y Disciplinas', 3);

  doc.setFont('helvetica', 'bold');
  doc.setFontSize(14);
  doc.setTextColor(15, 23, 42);
  doc.text('2. Rendimiento Académico e Instructores', 15, 25);

  doc.setFont('helvetica', 'normal');
  doc.setFontSize(9.5);
  doc.setTextColor(71, 85, 105);
  doc.text('Desglose de asistencia de alumnos y llenado promedio de las clases dirigidas por instructores.', 15, 30);

  // Table 1: Instructor Convocation Ranking
  doc.setFont('helvetica', 'bold');
  doc.setFontSize(11);
  doc.setTextColor(15, 23, 42);
  doc.text('Ranking de Instructores por Nivel de Llenado', 15, 40);

  const academic = allStats.academic || {};
  const rankingInstructores = academic.ranking_instructores || { labels: [], data: [] };
  const rankingInstructoresLabels = rankingInstructores.labels || [];
  const rankingInstructoresData = rankingInstructores.data || [];
  
  const instructorsRank = rankingInstructoresLabels.map((lbl, idx) => ({
    name: lbl,
    percentage: rankingInstructoresData[idx] || 0
  })).slice(0, 5);

  // Draw table for Instructor Ranking
  doc.setFillColor(15, 23, 42); // slate-900 header
  doc.rect(15, 46, 180, 8, 'F');
  
  doc.setFont('helvetica', 'bold');
  doc.setFontSize(8.5);
  doc.setTextColor(255, 255, 255);
  doc.text('INSTRUCTOR', 18, 51.5);
  doc.text('LLENADO PROMEDIO DE CLASE (%)', 192, 51.5, { align: 'right' });

  let rowY = 54;
  if (instructorsRank.length === 0) {
    doc.setFont('helvetica', 'italic');
    doc.setFontSize(9);
    doc.setTextColor(148, 163, 184);
    doc.text('No hay registros de instructores disponibles en este periodo.', 18, 64);
  } else {
    instructorsRank.forEach((inst, index) => {
      // Alternating row background
      if (index % 2 === 0) {
        doc.setFillColor(248, 250, 252);
        doc.rect(15, rowY, 180, 8, 'F');
      }
      doc.setFont('helvetica', 'normal');
      doc.setFontSize(8.5);
      doc.setTextColor(71, 85, 105);
      doc.text(inst.name, 18, rowY + 5.5);
      
      // Right-aligned percentage
      doc.setFont('helvetica', 'bold');
      doc.setTextColor(59, 130, 246);
      doc.text(`${inst.percentage}%`, 192, rowY + 5.5, { align: 'right' });
      
      // Thin line separation
      doc.setDrawColor(241, 245, 249);
      doc.line(15, rowY + 8, 195, rowY + 8);
      rowY += 8;
    });
  }

  // Table 2: Attendance and Abandono (No-Shows) by Discipline
  doc.setFont('helvetica', 'bold');
  doc.setFontSize(11);
  doc.setTextColor(15, 23, 42);
  doc.text('Tasa de Asistencia vs. No-Shows por Disciplina', 15, rowY + 10);

  const asistenciaDisciplina = academic.asistencia_disciplina || { labels: [], asistencias: [], no_shows: [] };
  const asistenciaDisciplinaLabels = asistenciaDisciplina.labels || [];
  const asistenciaDisciplinaAsistencias = asistenciaDisciplina.asistencias || [];
  const asistenciaDisciplinaNoShows = asistenciaDisciplina.no_shows || [];

  const disciplineStats = asistenciaDisciplinaLabels.map((lbl, idx) => {
    const checkin = asistenciaDisciplinaAsistencias[idx] || 0;
    const noshow = asistenciaDisciplinaNoShows[idx] || 0;
    const total = checkin + noshow;
    const rate = total > 0 ? Math.round((checkin / total) * 100) : 0;
    return { name: lbl, checkin, noshow, rate };
  }).slice(0, 5);

  const startTableY = rowY + 16;
  doc.setFillColor(15, 23, 42); // slate-900 header
  doc.rect(15, startTableY, 180, 8, 'F');
  
  doc.setFont('helvetica', 'bold');
  doc.setFontSize(8.5);
  doc.setTextColor(255, 255, 255);
  doc.text('DISCIPLINA', 18, startTableY + 5.5);
  doc.text('CHECK-INS', 90, startTableY + 5.5, { align: 'center' });
  doc.text('NO-SHOWS', 135, startTableY + 5.5, { align: 'center' });
  doc.text('TASA DE ASISTENCIA (%)', 192, startTableY + 5.5, { align: 'right' });

  rowY = startTableY + 8;
  if (disciplineStats.length === 0) {
    doc.setFont('helvetica', 'italic');
    doc.setFontSize(9);
    doc.setTextColor(148, 163, 184);
    doc.text('No hay registros de asistencia escolar en este periodo.', 18, rowY + 12);
  } else {
    disciplineStats.forEach((disc, index) => {
      if (index % 2 === 0) {
        doc.setFillColor(248, 250, 252);
        doc.rect(15, rowY, 180, 8, 'F');
      }
      doc.setFont('helvetica', 'normal');
      doc.setFontSize(8.5);
      doc.setTextColor(71, 85, 105);
      doc.text(disc.name, 18, rowY + 5.5);
      doc.text(`${disc.checkin}`, 90, rowY + 5.5, { align: 'center' });
      
      doc.setTextColor(239, 68, 68); // red no show
      doc.text(`${disc.noshow}`, 135, rowY + 5.5, { align: 'center' });
      
      doc.setFont('helvetica', 'bold');
      doc.setTextColor(16, 185, 129); // green rate
      doc.text(`${disc.rate}%`, 192, rowY + 5.5, { align: 'right' });

      doc.setDrawColor(241, 245, 249);
      doc.line(15, rowY + 8, 195, rowY + 8);
      rowY += 8;
    });
  }

  // Academic Charts side-by-side inside beautiful rounded card containers
  const rankingData = {
    labels: rankingInstructoresLabels,
    datasets: [{
      label: 'Llenado (%)',
      data: rankingInstructoresData,
      backgroundColor: 'rgba(99, 102, 241, 0.85)'
    }]
  };
  
  // Aspect ratio is 83x34 (approx 2.44:1), matching 1600x655 perfectly
  const rankingImg = getChartImage('bar', rankingData, {
    plugins: { legend: { display: true, position: 'top', labels: { boxWidth: 10 } } },
    scales: {
      x: { grid: { display: false } },
      y: { beginAtZero: true }
    }
  }, 1600, 655);
  
  const cardY = rowY + 12;
  const cardH = 56;
  
  drawChartCard(doc, 'Ocupación de Clases', 'Ocupación promedio (%) de clase por instructor', 15, cardY, 87, cardH);
  doc.addImage(rankingImg, 'PNG', 17, cardY + 18, 83, 34);

  const discAsistenciaData = {
    labels: asistenciaDisciplinaLabels,
    datasets: [
      {
        label: 'Asistencias',
        data: asistenciaDisciplinaAsistencias,
        backgroundColor: '#10b981'
      },
      {
        label: 'No-Shows',
        data: asistenciaDisciplinaNoShows,
        backgroundColor: '#f43f5e'
      }
    ]
  };
  
  // Aspect ratio is 83x34 (approx 2.44:1), matching 1600x655 perfectly
  const discAsistenciaImg = getChartImage('bar', discAsistenciaData, {
    plugins: { legend: { display: true, position: 'top', labels: { boxWidth: 10 } } },
    scales: {
      x: { stacked: true, grid: { display: false } },
      y: { stacked: true, beginAtZero: true }
    }
  }, 1600, 655);
  
  drawChartCard(doc, 'Check-ins vs. No-Shows', 'Check-ins vs No-Shows por disciplina', 108, cardY, 87, cardH);
  doc.addImage(discAsistenciaImg, 'PNG', 110, cardY + 18, 83, 34);



  // ==========================================
  // PAGE 4: OCUPACIÓN DE ESPACIOS E INFRAESTRUCTURA
  // ==========================================
  doc.addPage();
  addHeaderAndFooter('3. Ocupación de Espacios e Infraestructura', 4);

  doc.setFont('helvetica', 'bold');
  doc.setFontSize(14);
  doc.setTextColor(15, 23, 42);
  doc.text('3. Ocupación de Espacios e Infraestructura', 15, 25);

  doc.setFont('helvetica', 'normal');
  doc.setFontSize(9.5);
  doc.setTextColor(71, 85, 105);
  doc.text('Porcentaje de uso y volumen de reservaciones on-demand en la infraestructura del club.', 15, 30);

  // Table of spaces usage volume
  doc.setFont('helvetica', 'bold');
  doc.setFontSize(11);
  doc.setTextColor(15, 23, 42);
  doc.text('Volumen de Reservaciones por Instalación Deportiva', 15, 40);

  const spaces = allStats.spaces || {};
  const ocupacionPorTipo = spaces.ocupacion_por_tipo || { labels: [], data: [] };
  const ocupacionPorTipoLabels = ocupacionPorTipo.labels || [];
  const ocupacionPorTipoData = ocupacionPorTipo.data || [];

  const spacesUsage = ocupacionPorTipoLabels.map((lbl, idx) => ({
    name: lbl,
    reservations: ocupacionPorTipoData[idx] || 0
  })).sort((a,b) => b.reservations - a.reservations);

  doc.setFillColor(15, 23, 42);
  doc.rect(15, 46, 180, 8, 'F');
  
  doc.setFont('helvetica', 'bold');
  doc.setFontSize(8.5);
  doc.setTextColor(255, 255, 255);
  doc.text('CANCHA / ESPACIO FÍSICO', 18, 51.5);
  doc.text('RESERVACIONES ACUMULADAS', 192, 51.5, { align: 'right' });

  rowY = 54;
  const maxSpaceReservations = Math.max(...(ocupacionPorTipoData.length > 0 ? ocupacionPorTipoData : [1]), 10);
  
  if (spacesUsage.length === 0) {
    doc.setFont('helvetica', 'italic');
    doc.setFontSize(9);
    doc.setTextColor(148, 163, 184);
    doc.text('No hay reservaciones acumuladas en este periodo.', 18, 64);
  } else {
    spacesUsage.forEach((space, index) => {
      if (index % 2 === 0) {
        doc.setFillColor(248, 250, 252);
        doc.rect(15, rowY, 180, 8, 'F');
      }
      doc.setFont('helvetica', 'normal');
      doc.setFontSize(8.5);
      doc.setTextColor(71, 85, 105);
      doc.text(space.name, 18, rowY + 5.5);
      
      doc.setFont('helvetica', 'bold');
      doc.setTextColor(15, 23, 42);
      doc.text(`${space.reservations}`, 192, rowY + 5.5, { align: 'right' });

      doc.setDrawColor(241, 245, 249);
      doc.line(15, rowY + 8, 195, rowY + 8);
      rowY += 8;
    });
  }

  // Left card: Comparativa de Ocupación por Áreas inside a rounded card
  const spacesCardY = rowY + 12;
  const spacesCardH = 92;
  
  drawChartCard(doc, 'Comparativa de Ocupación por Áreas', 'Volumen de reservaciones registradas por cancha', 15, spacesCardY, 105, spacesCardH);
  
  const spacesSample = spacesUsage.slice(0, 4);
  let visualY = spacesCardY + 20;
  if (spacesSample.length === 0) {
    doc.setFont('helvetica', 'italic');
    doc.setFontSize(9);
    doc.setTextColor(148, 163, 184);
    doc.text('No hay reservaciones acumuladas en este periodo.', 21, spacesCardY + 30);
  } else {
    spacesSample.forEach(item => {
      drawProgressBar(doc, item.name, item.reservations, maxSpaceReservations, 21, visualY, 93, 5, [16, 185, 129]); // Inside card (x = 21, w = 93)
      visualY += 15;
    });
  }

  // Right card: Demanda de Espacios inside a aligned card
  drawChartCard(doc, 'Demanda de Espacios', 'Proporción de uso de la infraestructura física', 127, spacesCardY, 68, spacesCardH);

  const spacesUsageData = {
    labels: ocupacionPorTipoLabels,
    datasets: [{
      data: ocupacionPorTipoData,
      backgroundColor: ['#6366f1', '#10b981', '#f59e0b', '#ec4899', '#06b6d4', '#8b5cf6', '#f43f5e', '#64748b']
    }]
  };
  
  // Use high resolution square 800x800 layout to match perfectly inside card
  const spacesUsageImg = getChartImage('doughnut', spacesUsageData, {
    plugins: {
      legend: { display: true, position: 'bottom', labels: { boxWidth: 10, padding: 8, font: { weight: 'bold' } } }
    }
  }, 800, 800);
  
  doc.addImage(spacesUsageImg, 'PNG', 129, spacesCardY + 14, 64, 60);



  // ==========================================
  // PAGE 5: ANALÍTICAS DE TORNEOS
  // ==========================================
  doc.addPage();
  addHeaderAndFooter('4. Analíticas de Convocatoria de Torneos', 5);

  doc.setFont('helvetica', 'bold');
  doc.setFontSize(14);
  doc.setTextColor(15, 23, 42);
  doc.text('4. Convocatoria y Participación de Torneos', 15, 25);

  doc.setFont('helvetica', 'normal');
  doc.setFontSize(9.5);
  doc.setTextColor(71, 85, 105);
  doc.text('Análisis de jugadores inscritos por categoría y proporción de competitividad por perfil.', 15, 30);

  const tournaments = allStats.tournaments || {};
  const inscripcionesCategoria = tournaments.inscripciones_categoria || { labels: [], data: [] };
  const inscripcionesCategoriaLabels = inscripcionesCategoria.labels || [];
  const inscripcionesCategoriaData = inscripcionesCategoria.data || [];

  // Table 1: Tournament Categories
  doc.setFont('helvetica', 'bold');
  doc.setFontSize(11);
  doc.setTextColor(15, 23, 42);
  doc.text('Jugadores Inscritos por Categoría de Torneo', 15, 40);

  const catRegistrations = inscripcionesCategoriaLabels.map((lbl, idx) => ({
    name: lbl,
    total: inscripcionesCategoriaData[idx] || 0
  })).sort((a,b) => b.total - a.total);

  doc.setFillColor(15, 23, 42);
  doc.rect(15, 46, 180, 8, 'F');
  
  doc.setFont('helvetica', 'bold');
  doc.setFontSize(8.5);
  doc.setTextColor(255, 255, 255);
  doc.text('CATEGORÍA', 18, 51.5);
  doc.text('JUGADORES REGISTRADOS', 192, 51.5, { align: 'right' });

  rowY = 54;
  if (catRegistrations.length === 0) {
    doc.setFont('helvetica', 'italic');
    doc.setFontSize(9);
    doc.setTextColor(148, 163, 184);
    doc.text('No hay torneos registrados con jugadores inscritos en este periodo.', 18, 64);
  } else {
    catRegistrations.forEach((cat, index) => {
      if (index % 2 === 0) {
        doc.setFillColor(248, 250, 252);
        doc.rect(15, rowY, 180, 8, 'F');
      }
      doc.setFont('helvetica', 'normal');
      doc.setFontSize(8.5);
      doc.setTextColor(71, 85, 105);
      doc.text(cat.name, 18, rowY + 5.5);
      
      doc.setFont('helvetica', 'bold');
      doc.setTextColor(15, 23, 42);
      doc.text(`${cat.total}`, 192, rowY + 5.5, { align: 'right' });

      doc.setDrawColor(241, 245, 249);
      doc.line(15, rowY + 8, 195, rowY + 8);
      rowY += 8;
    });
  }

  // Table 2: Competitor Origins
  doc.setFont('helvetica', 'bold');
  doc.setFontSize(11);
  doc.setTextColor(15, 23, 42);
  doc.text('Proporción de Perfiles de Competidores', 15, rowY + 10);

  const origenCompetidores = tournaments.origen_competidores || { labels: [], data: [] };
  const origenCompetidoresLabels = origenCompetidores.labels || [];
  const origenCompetidoresData = origenCompetidores.data || [];

  const originStats = origenCompetidoresLabels.map((lbl, idx) => ({
    type: lbl,
    count: origenCompetidoresData[idx] || 0
  }));

  const startOriginY = rowY + 16;
  doc.setFillColor(15, 23, 42);
  doc.rect(15, startOriginY, 180, 8, 'F');
  
  doc.setFont('helvetica', 'bold');
  doc.setFontSize(8.5);
  doc.setTextColor(255, 255, 255);
  doc.text('ORIGEN COMPETIDOR', 18, startOriginY + 5.5);
  doc.text('CANTIDAD DE COMPETIDORES', 192, startOriginY + 5.5, { align: 'right' });

  rowY = startOriginY + 8;
  if (originStats.length === 0) {
    doc.setFont('helvetica', 'italic');
    doc.setFontSize(9);
    doc.setTextColor(148, 163, 184);
    doc.text('No hay registros demográficos de competidores en este periodo.', 18, rowY + 12);
  } else {
    originStats.forEach((origin, index) => {
      if (index % 2 === 0) {
        doc.setFillColor(248, 250, 252);
        doc.rect(15, rowY, 180, 8, 'F');
      }
      doc.setFont('helvetica', 'normal');
      doc.setFontSize(8.5);
      doc.setTextColor(71, 85, 105);
      doc.text(origin.type, 18, rowY + 5.5);
      
      doc.setFont('helvetica', 'bold');
      doc.setTextColor(59, 130, 246);
      doc.text(`${origin.count}`, 192, rowY + 5.5, { align: 'right' });

      doc.setDrawColor(241, 245, 249);
      doc.line(15, rowY + 8, 195, rowY + 8);
      rowY += 8;
    });
  }

  // Side-by-side Tournament Charts inside beautiful rounded card containers
  const tourInscritosData = {
    labels: inscripcionesCategoriaLabels,
    datasets: [{
      label: 'Jugadores inscritos',
      data: inscripcionesCategoriaData,
      backgroundColor: '#3b82f6'
    }]
  };
  
  const tourCardY = rowY + 12;
  const tourCardH = 56;

  // Aspect ratio is 83x34 (approx 2.44:1), matching 1600x655 perfectly
  const tourInscritosImg = getChartImage('bar', tourInscritosData, {
    plugins: { legend: { display: true, position: 'top', labels: { boxWidth: 10 } } },
    scales: {
      x: { grid: { display: false } },
      y: { beginAtZero: true }
    }
  }, 1600, 655);
  
  drawChartCard(doc, 'Inscritos por Categoría', 'Jugadores registrados por categoría de torneo', 15, tourCardY, 87, tourCardH);
  doc.addImage(tourInscritosImg, 'PNG', 17, tourCardY + 18, 83, 34);

  const tourOrigenData = {
    labels: origenCompetidoresLabels,
    datasets: [{
      data: origenCompetidoresData,
      backgroundColor: ['#6366f1', '#a855f7', '#ec4899']
    }]
  };
  
  // High quality 1600x655 layout to match perfectly with right legend in side-by-side card
  const tourOrigenImg = getChartImage('doughnut', tourOrigenData, {
    plugins: {
      legend: { display: true, position: 'right', labels: { boxWidth: 10, padding: 8, font: { weight: 'bold' } } }
    }
  }, 1600, 655);
  
  drawChartCard(doc, 'Origen de Competidores', 'Distribución demográfica de competidores', 108, tourCardY, 87, tourCardH);
  doc.addImage(tourOrigenImg, 'PNG', 110, tourCardY + 18, 83, 34);



  // ==========================================
  // PAGE 6: AUDITORÍA Y MEMBRESÍAS FANTASMA
  // ==========================================
  doc.addPage();
  addHeaderAndFooter('5. Auditoría de Membresías y Lista Negra', 6);

  doc.setFont('helvetica', 'bold');
  doc.setFontSize(14);
  doc.setTextColor(15, 23, 42);
  doc.text('5. Auditoría, Fidelización e Infracciones', 15, 25);

  doc.setFont('helvetica', 'normal');
  doc.setFontSize(9.5);
  doc.setTextColor(71, 85, 105);
  doc.text('Balance de cuentas de socios, niveles de abandono, fidelidad e infractores reincidentes.', 15, 30);

  const auditoria = allStats.auditoria || {};
  const heavy = auditoria.heavy_users || [];
  const blackList = auditoria.lista_negra || [];
  const tendenciaNoShows = auditoria.tendencia_no_shows || { labels: [], data: [] };
  const tendenciaNoShowsLabels = tendenciaNoShows.labels || [];
  const tendenciaNoShowsData = tendenciaNoShows.data || [];

  // Section 5.1: Heavy Users vs. Membresías Fantasma
  doc.setFont('helvetica', 'bold');
  doc.setFontSize(11);
  doc.setTextColor(15, 23, 42);
  doc.text('Socios con Mayor Fidelidad (Heavy Users)', 15, 40);

  const heavySample = heavy.slice(0, 5);

  doc.setFillColor(15, 23, 42);
  doc.rect(15, 46, 180, 8, 'F');
  
  doc.setFont('helvetica', 'bold');
  doc.setFontSize(8.5);
  doc.setTextColor(255, 255, 255);
  doc.text('SOCIO', 18, 51.5);
  doc.text('NÚMERO DE ACCIÓN', 90, 51.5, { align: 'center' });
  doc.text('ASISTENCIAS ACUMULADAS', 192, 51.5, { align: 'right' });

  rowY = 54;
  if (heavySample.length === 0) {
    doc.setFont('helvetica', 'italic');
    doc.setFontSize(9);
    doc.setTextColor(148, 163, 184);
    doc.text('No hay registros de Heavy Users en este periodo.', 18, 64);
  } else {
    heavySample.forEach((user, index) => {
      if (index % 2 === 0) {
        doc.setFillColor(248, 250, 252);
        doc.rect(15, rowY, 180, 8, 'F');
      }
      doc.setFont('helvetica', 'normal');
      doc.setFontSize(8.5);
      doc.setTextColor(71, 85, 105);
      doc.text(user.nombre_completo || 'Socio Desconocido', 18, rowY + 5.5);
      doc.text(user.numero_accion || 'S/N', 90, rowY + 5.5, { align: 'center' });
      
      doc.setFont('helvetica', 'bold');
      doc.setTextColor(16, 185, 129); // green total
      doc.text(`${user.total_asistencias || 0}`, 192, rowY + 5.5, { align: 'right' });

      doc.setDrawColor(241, 245, 249);
      doc.line(15, rowY + 8, 195, rowY + 8);
      rowY += 8;
    });
  }

  // Section 5.2: The blacklist (Reincidentes)
  doc.setFont('helvetica', 'bold');
  doc.setFontSize(11);
  doc.setTextColor(15, 23, 42);
  doc.text('Lista Negra: Socios Reincidentes y Sancionados', 15, rowY + 10);

  const blackListSample = blackList.slice(0, 5);

  const startBlackY = rowY + 16;
  doc.setFillColor(15, 23, 42);
  doc.rect(15, startBlackY, 180, 8, 'F');
  
  doc.setFont('helvetica', 'bold');
  doc.setFontSize(8.5);
  doc.setTextColor(255, 255, 255);
  doc.text('SANCIONADO', 18, startBlackY + 5.5);
  doc.text('ACCIÓN', 80, startBlackY + 5.5, { align: 'center' });
  doc.text('NO-SHOWS (RESERVAS)', 125, startBlackY + 5.5, { align: 'center' });
  doc.text('ESTATUS PENALIZACIÓN', 192, startBlackY + 5.5, { align: 'right' });

  rowY = startBlackY + 8;
  if (blackListSample.length === 0) {
    doc.setFont('helvetica', 'italic');
    doc.setFontSize(9);
    doc.setTextColor(148, 163, 184);
    doc.text('No hay socios registrados en la lista negra.', 18, rowY + 12);
  } else {
    blackListSample.forEach((user, index) => {
      if (index % 2 === 0) {
        doc.setFillColor(248, 250, 252);
        doc.rect(15, rowY, 180, 8, 'F');
      }
      doc.setFont('helvetica', 'normal');
      doc.setFontSize(8.5);
      doc.setTextColor(71, 85, 105);
      doc.text(user.nombre_completo || 'Socio Desconocido', 18, rowY + 5.5);
      doc.text(user.numero_accion || 'S/N', 80, rowY + 5.5, { align: 'center' });
      doc.text(`${user.contador_no_shows || 0}`, 125, rowY + 5.5, { align: 'center' });
      
      doc.setFont('helvetica', 'bold');
      const isPenalized = user.estatus_penalizacion !== 'SIN_PENALIZACION';
      if (isPenalized) {
        doc.setTextColor(239, 68, 68);
      } else {
        doc.setTextColor(100, 116, 139);
      }
      doc.text(formatEstatusPenalizacion(user.estatus_penalizacion), 192, rowY + 5.5, { align: 'right' });

      doc.setDrawColor(241, 245, 249);
      doc.line(15, rowY + 8, 195, rowY + 8);
      rowY += 8;
    });
  }

  // Line Chart at the bottom of Page 6 inside a gorgeous rounded card container
  const auditoriaNoShowsData = {
    labels: tendenciaNoShowsLabels,
    datasets: [{
      label: 'Reservas inasistidas (No-Shows)',
      data: tendenciaNoShowsData,
      borderColor: '#f43f5e',
      backgroundColor: 'rgba(244, 63, 94, 0.1)',
      fill: true,
      tension: 0.3
    }]
  };
  
  const auditCardY = rowY + 12;
  const auditCardH = 56;
  
  // Aspect ratio is 176x34 (approx 5.17:1), matching 2000x386 perfectly
  const auditoriaNoShowsImg = getChartImage('line', auditoriaNoShowsData, {
    plugins: { legend: { display: true, position: 'top', labels: { boxWidth: 10 } } },
    scales: {
      x: { grid: { display: false } },
      y: { beginAtZero: true }
    }
  }, 2000, 386);
  
  drawChartCard(doc, 'Tendencia Histórica de No-Shows', 'Historial de inasistencias acumuladas de socios penalizados en el periodo', 15, auditCardY, 180, auditCardH);
  doc.addImage(auditoriaNoShowsImg, 'PNG', 17, auditCardY + 18, 176, 34);


  // Save/Download PDF
  doc.save(`Reporte_General_Operaciones_${now.toISOString().split('T')[0]}.pdf`);
};

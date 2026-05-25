<script setup>
import { onMounted, onUnmounted, ref, watch } from 'vue';
import Chart from 'chart.js/auto';

const props = defineProps({
  type: { type: String, required: true },
  data: { type: Object, required: true },
  options: { type: Object, default: () => ({}) }
});

const canvasRef = ref(null);
let chartInstance = null;

// Colores sofisticados para usar por defecto (basados en HSL / Slate / Indigo de Tailwind)
const presetOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: true,
      position: 'bottom',
      labels: {
        color: '#475569', // text-slate-600
        font: {
          family: 'system-ui, -apple-system, sans-serif',
          size: 11,
          weight: '600'
        },
        padding: 16,
        usePointStyle: true,
        pointStyle: 'circle'
      }
    },
    tooltip: {
      backgroundColor: '#0f172a', // slate-900
      titleColor: '#ffffff',
      bodyColor: '#e2e8f0',
      padding: 12,
      cornerRadius: 12,
      borderWidth: 1,
      borderColor: '#334155', // slate-700
      titleFont: {
        family: 'system-ui, -apple-system, sans-serif',
        size: 12,
        weight: 'bold'
      },
      bodyFont: {
        family: 'system-ui, -apple-system, sans-serif',
        size: 11
      }
    }
  },
  scales: {
    x: {
      grid: {
        display: false
      },
      ticks: {
        color: '#64748b', // text-slate-500
        font: {
          family: 'system-ui, -apple-system, sans-serif',
          size: 10,
          weight: '600'
        }
      }
    },
    y: {
      grid: {
        color: '#f1f5f9', // slate-100
        drawBorder: false
      },
      ticks: {
        color: '#64748b', // text-slate-500
        font: {
          family: 'system-ui, -apple-system, sans-serif',
          size: 10,
          weight: '600'
        }
      }
    }
  }
};

const renderChart = () => {
  if (chartInstance) {
    chartInstance.destroy();
  }
  if (!canvasRef.value) return;

  // Combinar opciones por defecto con las pasadas por props
  const finalOptions = {
    ...presetOptions,
    ...props.options,
    plugins: {
      ...presetOptions.plugins,
      ...(props.options?.plugins || {}),
      legend: {
        ...presetOptions.plugins.legend,
        ...(props.options?.plugins?.legend || {})
      },
      tooltip: {
        ...presetOptions.plugins.tooltip,
        ...(props.options?.plugins?.tooltip || {})
      }
    },
    scales: {
      ...presetOptions.scales,
      ...(props.options?.scales || {}),
      x: {
        ...presetOptions.scales.x,
        ...(props.options?.scales?.x || {})
      },
      y: {
        ...presetOptions.scales.y,
        ...(props.options?.scales?.y || {})
      }
    }
  };

  chartInstance = new Chart(canvasRef.value, {
    type: props.type,
    data: props.data,
    options: finalOptions
  });
};

watch(() => props.data, renderChart, { deep: true });

onMounted(() => {
  renderChart();
});

onUnmounted(() => {
  if (chartInstance) {
    chartInstance.destroy();
  }
});
</script>

<template>
  <div class="relative w-full h-full min-h-[220px] transition-all duration-700 ease-[cubic-bezier(0.32,0.72,0,1)]">
    <canvas ref="canvasRef"></canvas>
  </div>
</template>

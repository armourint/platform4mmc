<x-card class="h-[460px] relative">
  <x-slot:header>Water Depth Predictions</x-slot:header>
  <div id="wd-series" class="hidden">@json($series)</div>
  <div class="h-[380px]" wire:ignore>
    <canvas id="wd-chart"></canvas>
  </div>
</x-card>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-annotation@3"></script>
<script>
document.addEventListener('livewire:init', () => {
  let chart;

  const render = () => {
    const dataEl = document.getElementById('wd-series');
    const series = dataEl ? JSON.parse(dataEl.textContent || '{}') : {};
    const canvas = document.getElementById('wd-chart');
    if (!canvas || !series || !series.labels) return;

    // Build datasets
    const colors = {
      target: '#6366F1', // Target — indigo
      rf:     '#F97316', // RF — orange-red
      rbf:    '#34D399', // RBF — green
      dt:     '#A78BFA', // DT — lavender
      ann:    '#F59E0B', // ANN — orange
      rnn:    '#06B6D4', // RNN — cyan
      lstm:   '#F472B6', // LSTM — pink
      gru:    '#86EFAC', // GRU — light green
    };
    const datasets = Object.entries(series)
      .filter(([k]) => k !== 'labels' && k !== 'y')
      .map(([label, data]) => ({
        label, data,
        borderColor: colors[label] || undefined,
        borderWidth: 2, pointRadius: 0, tension: 0.3
      }));

    // Horizontal threshold lines
    const line = (y, color) => ({ type:'line', yMin:y, yMax:y, borderColor:color, borderWidth:1, borderDash:[4,4] });
    const annotations = {
      y1: line(2.4, 'rgba(245, 158, 11, 0.8)'),
      y2: line(2.5, 'rgba(245, 158, 11, 0.8)'),
      y3: line(2.7, 'rgba(239, 68, 68, 0.9)'),
    };

    const yMin = series?.y?.min ?? 0;
    const yMax = series?.y?.max ?? 1;

    const cfg = {
      type: 'line',
      data: { labels: series.labels, datasets },
      options: {
        responsive: true, maintainAspectRatio: false,
        plugins: {
          legend: { position: 'bottom', labels: { boxWidth: 12 } },
          annotation: { annotations }
        },
        scales: {
          y: { min: yMin, max: yMax, grid: { color: 'rgba(148,163,184,.2)' } },
          x: { ticks: { maxRotation: 0 }, grid: { display:false } },
        }
      }
    };

    if (chart) chart.destroy();
    chart = new Chart(canvas.getContext('2d'), cfg);
    window.dispatchEvent(new CustomEvent('dashboard:part-ready', { detail: 'chart' }));
  };

  render();
  Livewire.hook('morph.updated', render);
});
</script>
@endpush

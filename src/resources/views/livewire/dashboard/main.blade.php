<div>
  <h2>Dashboard</h2>
  <div style="margin:8px 0;">
    <label>Location:</label>
    <select wire:model="location">
      <option value="">—</option>
      @foreach($locations as $loc)<option value="{{ $loc }}">{{ $loc }}</option>@endforeach
    </select>
    <label style="margin-left:12px;">Hour:</label>
    <input type="range" min="0" max="36" step="1" wire:model="hour"> +{{ $hour }}h
  </div>

  <div id="map"></div>
  <script>
    (function(){
      const map = L.map('map').setView([53.35, -6.26], 6);
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 18 }).addTo(map);
      const points = @json($points);
      points.forEach(p=>{
        if (!p.lat || !p.lng) return;
        const depth = (p.val ?? '—');
        const color = depth==='—' ? 'gray' : (depth>=2.7?'red':(depth>=2.5?'orange':(depth>=2.4?'yellow':'green')));
        const marker = L.circleMarker([p.lat, p.lng], {radius: 6, color: color}).addTo(map);
        marker.bindPopup(`<strong>${p.loc||'—'}</strong><br>Depth: ${depth}`);
      });
    })();
  </script>

  <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-top:24px;">
    <div>
      <h3>Time-series (selected location)</h3>
      <canvas id="depthChart" height="140"></canvas>
    </div>
    <div>
      <h3>Max predicted depth per location ({{ strtoupper($model) }})</h3>
      <table border="1" cellpadding="6" style="width:100%; border-collapse:collapse;">
        <tr><th style="text-align:left;">Location</th><th style="text-align:right;">Max depth (m)</th></tr>
        @foreach($maxTable as $row)
          <tr>
            <td>{{ $row['location'] }}</td>
            <td style="text-align:right;">{{ is_null($row['max']) ? '—' : number_format($row['max'], 2) }}</td>
          </tr>
        @endforeach
      </table>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <script>
    (function(){
      const series = @json($series);
      const ctx = document.getElementById('depthChart');
      if (!ctx) return;
      const labels = (series['target_depth_m']||[]).map(p => p.t);
      function vals(key){ return (series[key]||[]).map(p => p.v); }
      const ds = [
        {label:'Target', data: vals('target_depth_m')},
        {label:'RF', data: vals('rf_depth_m')},
        {label:'RBF', data: vals('rbf_depth_m')},
        {label:'RNN', data: vals('rnn_depth_m')},
        {label:'DT', data: vals('dt_depth_m')},
        {label:'ANN', data: vals('ann_depth_m')},
        {label:'LSTM', data: vals('lstm_depth_m')},
        {label:'GRU', data: vals('gru_depth_m')},
      ].filter(d => d.data.length);
      if (ds.length) {
        new Chart(ctx, {
          type: 'line',
          data: { labels, datasets: ds.map(d => ({
            label: d.label,
            data: d.data,
            fill: false,
          }))},
          options: {
            responsive: true,
            scales: { x: { type: 'time', time: { unit: 'hour' } }, y: { beginAtZero: true } },
            plugins: { legend: { position: 'bottom' } }
          }
        });
      }
    })();
  </script>
</div>

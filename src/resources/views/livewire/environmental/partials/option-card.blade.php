@php
  $fmt = fn($v, $d=2) => is_null($v) ? '—' : number_format((float)$v, $d);
@endphp

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
  {{-- LEFT 2/3: table + hotspots --}}
  <div class="md:col-span-2 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-4">
      <div class="rounded-xl border p-3">
        <div class="text-xs text-gray-500">Layers (#)</div>
        <div class="text-xl font-extrabold">{{ $snap['kpi']['layer_count'] ?? 0 }}</div>
      </div>
      <div class="rounded-xl border p-3">
        <div class="text-xs text-gray-500">Mass (kg/m²)</div>
        <div class="text-xl font-extrabold">{{ $fmt($snap['kpi']['mass_total_kg_m2'] ?? 0) }}</div>
      </div>
      <div class="rounded-xl border p-3">
        <div class="text-xs text-gray-500">Carbon factor (kgCO₂e/kg)</div>
        <div class="text-xl font-extrabold">{{ $fmt($snap['kpi']['cf_avg_kgco2e_per_kg'] ?? 0, 2) }}</div>
      </div>
      <div class="rounded-xl border p-3">
        <div class="text-xs text-gray-500">A1–A3 (kgCO₂e/m²)</div>
        <div class="text-xl font-extrabold">{{ $fmt($snap['kpi']['a1a3_total_kgco2e_m2'] ?? 0) }}</div>
      </div>
    </div>

    @if(empty($snap) || empty($snap['layers']))
      <div class="text-sm text-gray-500">Select a system to view details.</div>
    @else
      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead>
            <tr class="border-b">
              <th class="text-left py-1 pr-3">Layer No.</th>
              <th class="text-left py-1 pr-3">Functional Role</th>
              <th class="text-left py-1 pr-3">Generic Material</th>
              <th class="text-right py-1 pr-3">Mass (kg/m²)</th>
              <th class="text-right py-1 pr-3">Carbon Factor</th>
              <th class="text-left  py-1 pr-3">Carbon Factor Unit</th>
              <th class="text-right py-1 pr-3">λ (W/m·K)</th>
              <th class="text-right py-1 pr-3">R (m²K/W)</th>
              <th class="text-right py-1 pr-3">U (W/m²K)</th>
              <th class="text-right py-1 pr-0">A1–A3 (kgCO₂e/m²)</th>
            </tr>
          </thead>
          <tbody>
            @foreach($snap['layers'] as $r)
              <tr class="border-b last:border-0">
                <td class="py-1 pr-3">{{ $r['layer_no'] }}</td>
                <td class="py-1 pr-3">{{ $r['functional_role'] ?? '—' }}</td>
                <td class="py-1 pr-3">{{ $r['generic_material'] ?? '—' }}</td>
                <td class="py-1 pr-3 text-right">{{ $fmt($r['mass_kg_m2'],2) }}</td>
                <td class="py-1 pr-3 text-right">{{ $fmt($r['carbon_factor'],2) }}</td>
                <td class="py-1 pr-3">{{ $r['carbon_factor_unit'] ?? 'kgCO₂e/kg' }}</td>
                <td class="py-1 pr-3 text-right">{{ $fmt($r['thermal_conductivity_w_mk'],3) }}</td>
                <td class="py-1 pr-3 text-right">{{ $fmt($r['r_value_m2k_w'],3) }}</td>
                <td class="py-1 pr-3 text-right">{{ $fmt($r['u_value_w_m2k'],3) }}</td>
                <td class="py-1 pr-0 text-right">{{ $fmt($r['a1a3_per_m2'],2) }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      @if(!empty($snap['hotspots']))
        <div class="mt-4">
          <div class="text-sm font-semibold mb-1">Top hotspots (A1–A3)</div>
          <ul class="list-disc pl-5 text-sm text-gray-800 space-y-1">
            @foreach($snap['hotspots'] as $h)
              <li>{{ $h['label'] }} — {{ number_format($h['a1a3'], 2) }} kgCO₂e/m²</li>
            @endforeach
          </ul>
        </div>
      @endif
    @endif
  </div>

  {{-- RIGHT 1/3: per-layer mass chart --}}
  <div class="md:col-span-1 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
    <div class="text-lg font-semibold mb-3">Per-Layer Mass (gradient by contribution)</div>

    @if(!empty($snap['massSeries']['values']))
      <canvas id="{{ $chartId }}" height="260"></canvas>
      <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
      <script>
        (function () {
          const ctx  = document.getElementById(@json($chartId)).getContext('2d');
          const vals = @json($snap['massSeries']['values']);
          const lab  = Array.from({length: vals.length}, (_, i) => (i + 1).toString());
          new Chart(ctx, {
            type: 'bar',
            data: { labels: lab, datasets: [{ label: 'kg/m²', data: vals }] },
            options: {
              responsive: true,
              plugins: { legend: { display: true } },
              scales: { y: { beginAtZero: true, title: { display: true, text: 'kg/m²' } } }
            }
          });
        })();
      </script>
    @else
      <div class="text-sm text-gray-500">No mass data for chart.</div>
    @endif
  </div>
</div>

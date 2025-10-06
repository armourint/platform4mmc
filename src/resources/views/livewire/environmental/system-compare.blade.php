<div class="max-w-6xl mx-auto px-4 py-6">
  @php
    $fmt = fn($v, $d=2) => is_null($v) ? '—' : number_format((float)$v, $d);
  @endphp

  <h1 class="text-2xl font-semibold text-gray-900 mb-6">MMC Generic Model Assessment</h1>

  {{-- System Category --}}
  <div class="mb-6">
    <label class="block text-sm font-medium text-gray-700 mb-1">System Category</label>
    <select wire:model.live="categoryKey" class="w-full rounded-md border-gray-300">
      @foreach($categoryLabels as $opt)
        <option value="{{ $opt['key'] }}">{{ $opt['label'] }}</option>
      @endforeach
    </select>
  </div>

  {{-- ===== Option A ===== --}}
  <div class="mb-8">
    <div class="font-semibold text-sm text-gray-900 mb-2">Option A</div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">MMC Category</label>
        <select wire:model.live="mmcA" class="w-full rounded-md border-gray-300">
          <option value="">(All)</option>
          @foreach($mmcOptions as $m)
            <option value="{{ $m }}">{{ $m }}</option>
          @endforeach
        </select>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">System Selection</label>
        <select wire:model.live="systemCodeA" class="w-full rounded-md border-gray-300">
          @foreach($systemsA as $s)
            <option value="{{ $s['code'] }}">[{{ $s['code'] }}] — {{ $s['name'] }}</option>
          @endforeach
        </select>
      </div>
    </div>

    @include('livewire.environmental.partials.option-card', ['snap' => $snapA, 'chartId' => 'chartA'])
  </div>

  {{-- ===== Option B ===== --}}
  <div class="mb-10">
    <div class="font-semibold text-sm text-gray-900 mb-2">Option B</div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">MMC Category</label>
        <select wire:model.live="mmcB" class="w-full rounded-md border-gray-300">
          <option value="">(All)</option>
          @foreach($mmcOptions as $m)
            <option value="{{ $m }}">{{ $m }}</option>
          @endforeach
        </select>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">System Selection</label>
        <select wire:model.live="systemCodeB" class="w-full rounded-md border-gray-300">
          @foreach($systemsB as $s)
            <option value="{{ $s['code'] }}">[{{ $s['code'] }}] — {{ $s['name'] }}</option>
          @endforeach
        </select>
      </div>
    </div>

    @include('livewire.environmental.partials.option-card', ['snap' => $snapB, 'chartId' => 'chartB'])
  </div>

  {{-- ===== Comparison Report ===== --}}
  <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
    <h2 class="text-lg font-semibold mb-4">Comparison Report</h2>

    @php
      $cmp = $compare['table'] ?? [];
    @endphp

    <div class="overflow-x-auto mb-6">
      <table class="min-w-full text-sm">
        <thead>
          <tr class="border-b">
            <th class="text-left py-2 pr-4">Metric</th>
            <th class="text-right py-2 pr-4">Option A</th>
            <th class="text-right py-2 pr-4">Option B</th>
            <th class="text-right py-2 pr-0">Δ (B − A)</th>
          </tr>
        </thead>
        <tbody>
          <tr class="border-b">
            <td class="py-2 pr-4">Mass (kg/m²)</td>
            <td class="py-2 pr-4 text-right">{{ $fmt($cmp['mass']['a']) }}</td>
            <td class="py-2 pr-4 text-right">{{ $fmt($cmp['mass']['b']) }}</td>
            <td class="py-2 pr-0 text-right">{{ $fmt($cmp['mass']['d']) }}</td>
          </tr>
          <tr class="border-b">
            <td class="py-2 pr-4">A1–A3 (kgCO₂e/m²)</td>
            <td class="py-2 pr-4 text-right">{{ $fmt($cmp['a1a3']['a']) }}</td>
            <td class="py-2 pr-4 text-right">{{ $fmt($cmp['a1a3']['b']) }}</td>
            <td class="py-2 pr-0 text-right">{{ $fmt($cmp['a1a3']['d']) }}</td>
          </tr>
          <tr class="border-b">
            <td class="py-2 pr-4">A4 (transport) (kgCO₂e/m²)</td>
            <td class="py-2 pr-4 text-right">—</td>
            <td class="py-2 pr-4 text-right">—</td>
            <td class="py-2 pr-0 text-right">—</td>
          </tr>
          <tr class="border-b">
            <td class="py-2 pr-4">A1–A4 (kgCO₂e/m²)</td>
            <td class="py-2 pr-4 text-right">{{ $fmt($cmp['a1a4']['a']) }}</td>
            <td class="py-2 pr-4 text-right">{{ $fmt($cmp['a1a4']['b']) }}</td>
            <td class="py-2 pr-0 text-right">{{ $fmt($cmp['a1a4']['d']) }}</td>
          </tr>
          <tr>
            <td class="py-2 pr-4">Avg CF (kgCO₂e/kg)</td>
            <td class="py-2 pr-4 text-right">{{ $fmt($cmp['cf_avg']['a']) }}</td>
            <td class="py-2 pr-4 text-right">{{ $fmt($cmp['cf_avg']['b']) }}</td>
            <td class="py-2 pr-0 text-right">{{ $fmt($cmp['cf_avg']['d']) }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    {{-- Charts row --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      {{-- Carbon vs Mass (scatter + line) --}}
      <div class="rounded-xl border p-4">
        <div class="text-sm font-semibold mb-2">Carbon vs Mass — trade-off (arrow A→B)</div>
        <canvas id="cmpScatter" height="260"></canvas>
      </div>

      {{-- Improvement summary (horizontal bars) --}}
      <div class="rounded-xl border p-4">
        <div class="text-sm font-semibold mb-2">Improvement summary</div>
        <canvas id="cmpBars" height="260"></canvas>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
      (function () {
        // SCATTER A↔B
        const sctx = document.getElementById('cmpScatter').getContext('2d');
        const A = @json($compare['scatter']['a']);
        const B = @json($compare['scatter']['b']);

        new Chart(sctx, {
          type: 'scatter',
          data: {
            datasets: [
              { label: 'A', data: [A] },
              { label: 'B', data: [B] },
              { label: 'A→B', data: [A, B], showLine: true, pointRadius: 0 }
            ]
          },
          options: {
            responsive: true,
            parsing: false,
            scales: {
              x: { title: { display: true, text: 'Mass (kg/m²)' } },
              y: { title: { display: true, text: 'A1–A3 (kgCO₂e/m²)' } }
            }
          }
        });

        // IMPROVEMENT bars: change B vs A (%)
        const bctx = document.getElementById('cmpBars').getContext('2d');
        const massPct = @json($compare['improve']['mass_pct']);
        const a1a3Pct = @json($compare['improve']['a1a3_pct']);

        new Chart(bctx, {
          type: 'bar',
          data: {
            labels: ['Mass %', 'GWP (A1–A3) %'],
            datasets: [{ data: [massPct ?? 0, a1a3Pct ?? 0] }]
          },
          options: {
            indexAxis: 'y',
            responsive: true,
            scales: {
              x: { title: { display: true, text: 'Change B vs A (%) — left = reduction, right = increase' } }
            },
            plugins: {
              tooltip: { callbacks: { label: ctx => `${ctx.parsed.x.toFixed(1)}%` } }
            }
          }
        });
      })();
    </script>
  </div>
</div>

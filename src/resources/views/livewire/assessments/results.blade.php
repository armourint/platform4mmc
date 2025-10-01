<div class="max-w-6xl mx-auto px-4 py-6">
  @php
    $type = $this->assessment->type ?? 'viability';
    $isViability = $type === 'viability';
    $isEnvironmental = $type === 'environmental';
    $fmt = fn($v, $d=2) => is_null($v) ? '—' : number_format((float)$v, $d);
  @endphp

  <div class="mb-6">
    <h1 class="text-2xl font-semibold text-gray-900">
      Assessment Results <span class="ml-2 text-sm font-normal text-gray-500">({{ strtoupper($type) }})</span>
    </h1>

    @if($isViability && !empty($summary))
      <div class="mt-2 flex items-center gap-2 text-sm">
        <span class="inline-flex items-center px-2 py-0.5 rounded-full
          {{ ($summary['status'] ?? 'OK') === 'OK' ? 'bg-green-100 text-green-800' :
              (($summary['status'] ?? '') === 'Attention' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
          {{ $summary['status'] ?? 'OK' }}
        </span>
        <span class="text-gray-500">
          {{ $summary['include_count'] ?? 0 }} viable • {{ $summary['exclude_count'] ?? 0 }} not viable
        </span>
      </div>
    @endif
  </div>

  {{-- ========================= VIABILITY ========================= --}}
  @if($isViability)
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
        <div class="flex items-center gap-2 mb-4">
          <span class="text-xl">🌿</span>
          <h2 class="text-lg font-semibold text-gray-900">Viable methods</h2>
        </div>

        @if(empty($included))
          <div class="text-sm text-gray-500">No methods meet the current requirements.</div>
        @else
          <div class="flex flex-wrap gap-2">
            @foreach ($included as $sys)
              <span class="inline-flex items-center rounded-full bg-green-100 text-green-800 px-3 py-1 text-sm">
                {{ $sys['name'] ?? $sys['code'] ?? 'Method' }}
              </span>
            @endforeach
          </div>
        @endif
      </div>

      <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
        <div class="flex items-center gap-2 mb-4">
          <span class="text-xl">⚠️</span>
          <h2 class="text-lg font-semibold text-gray-900">Not viable &amp; reasons</h2>
        </div>

        @if(empty($excluded))
          <div class="text-sm text-gray-500">Great news — none of the methods were excluded for the current inputs.</div>
        @else
          <div class="space-y-4">
            @foreach ($excluded as $sys)
              <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-red-100 text-red-700 text-sm font-semibold">
                      {{ strtoupper(substr(($sys['name'] ?? $sys['code'] ?? 'X'),0,3)) }}
                    </span>
                    <div class="text-base font-semibold text-gray-900">{{ $sys['name'] ?? $sys['code'] ?? 'Method' }}</div>
                  </div>
                  <span class="inline-flex items-center rounded-full bg-gray-200 text-gray-700 px-2 py-0.5 text-xs">
                    {{ isset($sys['reasons']) ? count($sys['reasons']) : 0 }} reason(s)
                  </span>
                </div>

                @if(!empty($sys['reasons']))
                  <ul class="mt-3 list-disc pl-5 text-sm text-gray-800 space-y-1">
                    @foreach ($sys['reasons'] as $reason)
                      <li>{{ $reason }}</li>
                    @endforeach
                  </ul>
                @else
                  <p class="mt-3 text-sm text-gray-500">Excluded (no reason provided).</p>
                @endif
              </div>
            @endforeach
          </div>
        @endif
      </div>
    </div>
  @endif

  {{-- ============================== ENVIRONMENTAL ============================== --}}
  @if($isEnvironmental)
    {{-- KPI row --}}
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-6">
      <div class="rounded-xl border p-4 bg-white">
        <div class="text-xs text-gray-500">Layer count</div>
        <div class="text-2xl font-extrabold">{{ $kpi['layer_count'] }}</div>
      </div>
      <div class="rounded-xl border p-4 bg-white">
        <div class="text-xs text-gray-500">Total mass</div>
        <div class="text-2xl font-extrabold">{{ $fmt($kpi['mass_total_kg_m2'], 2) }} <span class="text-sm font-normal">kg/m²</span></div>
      </div>
      <div class="rounded-xl border p-4 bg-white">
        <div class="text-xs text-gray-500">A1–A3</div>
        <div class="text-2xl font-extrabold">{{ $fmt($kpi['a1a3_total_kgco2e_m2'], 2) }} <span class="text-sm font-normal">kgCO₂e/m²</span></div>
      </div>
      <div class="rounded-xl border p-4 bg-white">
        <div class="text-xs text-gray-500">Overall</div>
        <div class="text-2xl font-extrabold">{{ $fmt($kpi['overall_total_kgco2e_m2'], 2) }} <span class="text-sm font-normal">kgCO₂e/m²</span></div>
      </div>
    </div>

    {{-- Two separate cards: LEFT 2/3 (table+hotspots), RIGHT 1/3 (chart) --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      {{-- LEFT CARD --}}
      <div class="md:col-span-2 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
        <h2 class="text-lg font-semibold mb-3">Layer breakdown & hotspots</h2>

        @if(empty($envLayers))
          <div class="text-sm text-gray-500">No layer dataset linked. Totals saved from inputs.</div>
        @else
          <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
              <thead>
                <tr class="border-b">
                  <th class="text-left py-1 pr-3">#</th>
                  <th class="text-left py-1 pr-3">Material / Role</th>
                  <th class="text-right py-1 pr-3">Mass (kg/m²)</th>
                  <th class="text-right py-1 pr-3">CF</th>
                  <th class="text-left  py-1 pr-3">Unit</th>
                  <th class="text-right py-1 pr-3">λ (W/m·K)</th>
                  <th class="text-right py-1 pr-3">R (m²K/W)</th>
                  <th class="text-right py-1 pr-3">U (W/m²K)</th>
                  <th class="text-right py-1 pr-0">A1–A3 (kgCO₂e/m²)</th>
                </tr>
              </thead>
              <tbody>
                @foreach($envLayers as $r)
                  <tr class="border-b last:border-0">
                    <td class="py-1 pr-3">{{ $r['layer_no'] }}</td>
                    <td class="py-1 pr-3">
                      {{ $r['generic_material'] ?? $r['functional_role'] ?? 'Layer' }}
                      @if(($r['generic_material'] ?? null) && ($r['functional_role'] ?? null))
                        <div class="text-xs text-gray-500">{{ $r['functional_role'] }}</div>
                      @endif
                    </td>
                    <td class="py-1 pr-3 text-right">{{ $fmt($r['mass_kg_m2'], 2) }}</td>
                    <td class="py-1 pr-3 text-right">{{ $fmt($r['carbon_factor'], 2) }}</td>
                    <td class="py-1 pr-3">{{ $r['carbon_factor_unit'] ?? 'kgCO₂e/kg' }}</td>
                    <td class="py-1 pr-3 text-right">{{ $fmt($r['thermal_conductivity_w_mk'], 3) }}</td>
                    <td class="py-1 pr-3 text-right">{{ $fmt($r['r_value_m2k_w'], 3) }}</td>
                    <td class="py-1 pr-3 text-right">{{ $fmt($r['u_value_w_m2k'], 3) }}</td>
                    <td class="py-1 pr-0 text-right">{{ $fmt($r['a1a3_per_m2'], 2) }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>

          @if(!empty($hotspots))
            <div class="mt-4">
              <div class="text-sm font-semibold mb-1">Top hotspots (A1–A3)</div>
              <ul class="list-disc pl-5 text-sm text-gray-800 space-y-1">
                @foreach($hotspots as $h)
                  <li>{{ $h['label'] ?? 'Layer' }} — {{ number_format((float)($h['a1a3'] ?? 0), 2) }} kgCO₂e/m²</li>
                @endforeach
              </ul>
            </div>
          @endif
        @endif
      </div>

      {{-- RIGHT CARD --}}
      <div class="md:col-span-1 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
        <h2 class="text-lg font-semibold mb-3">Per-layer Mass</h2>
        @if(!empty($massSeries['values']))
          <div class="w-full">
            <canvas id="massChart" height="280"></canvas>
          </div>
          <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
          <script>
            (function () {
              const ctx  = document.getElementById('massChart').getContext('2d');
              const vals = @json($massSeries['values']);
              const lab  = Array.from({length: vals.length}, (_, i) => (i + 1).toString());

              new Chart(ctx, {
                type: 'bar',
                data: { labels: lab, datasets: [{ label: 'kg/m²', data: vals }] },
                options: {
                  responsive: true,
                  plugins: { legend: { display: true }, tooltip: { enabled: true } },
                  scales: { y: { beginAtZero: true, title: { display: true, text: 'kg/m²' } } }
                }
              });
            })();
          </script>
        @else
          <div class="text-sm text-gray-500">No mass data available for chart.</div>
        @endif
      </div>
    </div>
  @endif

  <div class="mt-8">
    <a href="{{ url()->previous() }}"
       class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-md border bg-white hover:bg-gray-50 text-gray-700">
      ← Back
    </a>
  </div>
</div>

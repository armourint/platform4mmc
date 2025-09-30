<div class="max-w-6xl mx-auto px-4 py-6">
  @php
    $type = $this->assessment->type ?? 'viability';
    $isViability = $type === 'viability';
    $isEnvironmental = $type === 'environmental';
  @endphp

  {{-- Header --}}
  <div class="mb-6">
    <h1 class="text-2xl font-semibold text-gray-900">
      Assessment Results
      <span class="ml-2 text-sm font-normal text-gray-500">({{ strtoupper($type) }})</span>
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

  {{-- =========================
       VIABILITY PRESENTATION
       ========================= --}}
  @if($isViability)
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      {{-- Left: Viable methods --}}
      <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
        <div class="flex items-center gap-2 mb-4">
          <span class="text-xl">🌿</span>
          <h2 class="text-lg font-semibold text-gray-900">Viable methods</h2>
        </div>

        @if($included->isEmpty())
          <div class="text-sm text-gray-500">No methods meet the current requirements.</div>
        @else
          <div class="flex flex-wrap gap-2">
            @foreach ($included as $sys)
              <span class="inline-flex items-center rounded-full bg-green-100 text-green-800 px-3 py-1 text-sm">
                {{ $sys['name'] }}
              </span>
            @endforeach
          </div>
        @endif
      </div>

      {{-- Right: Not viable & reasons --}}
      <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
        <div class="flex items-center gap-2 mb-4">
          <span class="text-xl">⚠️</span>
          <h2 class="text-lg font-semibold text-gray-900">Not viable &amp; reasons</h2>
        </div>

        @if($excluded->isEmpty())
          <div class="text-sm text-gray-500">Great news — none of the methods were excluded for the current inputs.</div>
        @else
          <div class="space-y-4">
            @foreach ($excluded as $sys)
              <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-red-100 text-red-700 text-sm font-semibold">
                      {{ strtoupper(substr($sys['name'], 0, 3)) }}
                    </span>
                    <div class="text-base font-semibold text-gray-900">{{ $sys['name'] }}</div>
                  </div>
                  <span class="inline-flex items-center rounded-full bg-gray-200 text-gray-700 px-2 py-0.5 text-xs">
                    {{ count($sys['reasons']) }} reason(s)
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

  {{-- ==============================
       ENVIRONMENTAL PRESENTATION
       ============================== --}}
  @if($isEnvironmental)
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      {{-- Left: totals --}}
      <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
        <h2 class="text-lg font-semibold mb-3">Totals</h2>

        <p class="text-sm text-gray-600 mb-3">
          Total embodied carbon:
          <span class="font-semibold text-gray-900">
            {{ number_format((float)($envTotals['sum'] ?? 0), 2) }} kgCO₂e
          </span>
        </p>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
          <div class="rounded-xl border p-3">
            <div class="text-xs text-gray-500">A1–A3</div>
            <div class="text-2xl font-extrabold">
              {{ number_format((float)($envTotals['a1_a3'] ?? 0), 2) }}
            </div>
          </div>
          <div class="rounded-xl border p-3">
            <div class="text-xs text-gray-500">A4–A5</div>
            <div class="text-2xl font-extrabold">
              {{ number_format((float)($envTotals['a4_a5'] ?? 0), 2) }}
            </div>
          </div>
          <div class="rounded-xl border p-3">
            <div class="text-xs text-gray-500">C1–C4</div>
            <div class="text-2xl font-extrabold">
              {{ number_format((float)($envTotals['c1_c4'] ?? 0), 2) }}
            </div>
          </div>
        </div>

        @if(!empty($summary['note']))
          <p class="mt-3 text-xs text-gray-500">{{ $summary['note'] }}</p>
        @endif
      </div>

      {{-- Right: layer breakdown --}}
      <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
        <h2 class="text-lg font-semibold mb-3">Layer breakdown & hotspots</h2>

        @if(empty($envLayers))
          <div class="text-sm text-gray-500">No layer dataset linked. Totals saved from inputs.</div>
        @else
          <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
              <thead>
                <tr class="border-b">
                  <th class="text-left py-1 pr-4">#</th>
                  <th class="text-left py-1 pr-4">Material</th>
                  <th class="text-right py-1 pr-4">Mass (kg/m²)</th>
                  <th class="text-right py-1 pr-0">A1–A3 (kgCO₂e/m²)</th>
                </tr>
              </thead>
              <tbody>
                @foreach($envLayers as $r)
                  <tr class="border-b last:border-0">
                    <td class="py-1 pr-4">{{ $r['layer_no'] }}</td>
                    <td class="py-1 pr-4">{{ $r['generic_material'] ?? $r['functional_role'] ?? 'Layer' }}</td>
                    <td class="py-1 pr-4 text-right">
                      {{ is_null($r['mass_kg_m2']) ? '—' : number_format($r['mass_kg_m2'], 2) }}
                    </td>
                    <td class="py-1 pr-0 text-right">
                      {{ is_null($r['a1a3_per_m2']) ? '—' : number_format($r['a1a3_per_m2'], 2) }}
                    </td>
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
    </div>
  @endif

  {{-- Back link --}}
  <div class="mt-8">
    <a href="{{ url()->previous() }}"
       class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-md border bg-white hover:bg-gray-50 text-gray-700">
      ← Back
    </a>
  </div>
</div>
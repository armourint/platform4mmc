<div class="p-6 space-y-6">
  <div class="flex flex-wrap items-center justify-between gap-3">
    <h1 class="text-xl font-semibold">Assessment Results</h1>
    <div class="text-sm text-gray-500">
      @if($systemCode) System: <span class="font-medium">{{ $systemCode }}</span> @else No system selected @endif
    </div>
  </div>

  {{-- KPI cards --}}
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <div class="border rounded p-4 bg-white">
      <div class="text-xs text-gray-500">Viability</div>
      @php
        $vStatus = $viability['status'] ?? 'unknown';
        $color = match($vStatus) {
          'pass' => 'text-emerald-700 bg-emerald-50 ring-emerald-100',
          'attention' => 'text-amber-700 bg-amber-50 ring-amber-100',
          default => 'text-gray-700 bg-gray-50 ring-gray-100',
        };
      @endphp
      <div class="mt-1 inline-flex items-center gap-2 px-2 py-0.5 rounded ring {{ $color }}">
        <span class="h-2 w-2 rounded-full bg-current"></span>
        <span class="text-sm capitalize">{{ $vStatus }}</span>
      </div>
      <div class="mt-3 text-sm text-gray-600">
        Includes: <span class="font-medium">{{ $viability['include_count'] ?? 0 }}</span> ·
        Excludes: <span class="font-medium">{{ $viability['exclude_count'] ?? 0 }}</span>
      </div>
    </div>

    <div class="border rounded p-4 bg-white">
      <div class="text-xs text-gray-500">Embodied carbon (A1–A3)</div>
      <div class="mt-1 text-2xl font-semibold">
        @if(!is_null($env['a1a3_total']))
          {{ number_format($env['a1a3_total'], 2) }} <span class="text-sm font-normal text-gray-500">kgCO₂e / m²</span>
        @else
          <span class="text-gray-500 text-base">N/A</span>
        @endif
      </div>
    </div>

    <div class="border rounded p-4 bg-white">
      <div class="text-xs text-gray-500">Transport (A4)</div>
      <div class="mt-1 text-2xl font-semibold">
        @if(!is_null($env['a4_total']))
          {{ number_format($env['a4_total'], 2) }} <span class="text-sm font-normal text-gray-500">kgCO₂e / m²</span>
        @else
          <span class="text-gray-500 text-base">N/A</span>
        @endif
      </div>
    </div>
  </div>

  {{-- Viability details --}}
  <div class="border rounded bg-white">
    <div class="p-4 border-b font-medium">Viability checks</div>
    <div class="p-4">
      @if(($viability['failed'] ?? []) && count($viability['failed']))
        <ul class="list-disc pl-5 space-y-1 text-sm">
          @foreach($viability['failed'] as $f)
            <li>{{ $f['message'] }}</li>
          @endforeach
        </ul>
      @else
        <div class="text-sm text-gray-600">
          {{ $viability['notes'] ?? 'No blocking rules detected for the selected system.' }}
        </div>
      @endif
    </div>
  </div>

  {{-- Environmental layers + hotspots --}}
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="border rounded bg-white lg:col-span-2">
      <div class="p-4 border-b font-medium">Layer breakdown (per m²)</div>
      <div class="p-4 overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-50">
            <tr>
              <th class="text-left px-3 py-2">Layer</th>
              <th class="text-left px-3 py-2">Thickness (mm)</th>
              <th class="text-left px-3 py-2">Density (kg/m³)</th>
              <th class="text-left px-3 py-2">Mass (kg/m²)</th>
              <th class="text-left px-3 py-2">A1–A3 (kgCO₂e/m²)</th>
            </tr>
          </thead>
          <tbody>
          @forelse(($env['layers'] ?? []) as $row)
            <tr class="border-t">
              <td class="px-3 py-2">{{ $row['material'] }}</td>
              <td class="px-3 py-2">{{ $row['thickness_mm'] ?? '—' }}</td>
              <td class="px-3 py-2">{{ $row['density_kg_m3'] ?? '—' }}</td>
              <td class="px-3 py-2">
                @if(!is_null($row['mass_kg_m2'])) {{ number_format($row['mass_kg_m2'], 3) }} @else — @endif
              </td>
              <td class="px-3 py-2">
                @if(!is_null($row['a1a3_kgco2e_m2'])) {{ number_format($row['a1a3_kgco2e_m2'], 3) }} @else — @endif
              </td>
            </tr>
          @empty
            <tr><td colspan="5" class="px-3 py-6 text-gray-500">No layer data available.</td></tr>
          @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <div class="border rounded bg-white">
      <div class="p-4 border-b font-medium">Hotspots</div>
      <div class="p-4">
        @php $hs = $env['hotspots'] ?? []; @endphp
        @if($hs && count($hs))
          <div class="space-y-2">
            @foreach($hs as $h)
              @php
                $label = $h['label'] ?? 'Layer';
                $val   = (float) ($h['value'] ?? 0);
                $pct = min(100, round(($val > 0 ? $val : 0) / max($hs[0]['value'] ?? 1, 1) * 100));
              @endphp
              <div class="text-xs text-gray-600">{{ $label }}</div>
              <div class="h-2 w-full bg-gray-100 rounded">
                <div class="h-2 bg-blue-500 rounded" style="width: {{ $pct }}%"></div>
              </div>
              <div class="text-xs text-gray-500">{{ number_format($val, 3) }} (relative)</div>
            @endforeach
          </div>
        @else
          <div class="text-sm text-gray-600">No identifiable hotspots.</div>
        @endif
      </div>
    </div>
  </div>

  {{-- Actions --}}
  <div class="flex flex-wrap items-center gap-3">
    <button onclick="window.print()" class="px-3 py-1.5 rounded border bg-white hover:bg-gray-50 text-sm">
      Print / Save as PDF
    </button>
    <a href="{{ route('assessments.index') }}" class="px-3 py-1.5 rounded bg-blue-600 text-white text-sm">
      Back to Assessments
    </a>
  </div>
</div>

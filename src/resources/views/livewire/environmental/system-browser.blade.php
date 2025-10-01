<div class="max-w-7xl mx-auto px-4 py-6">
  <h1 class="text-2xl font-semibold text-gray-900 mb-4">MMC Generic Model Assessment</h1>

  {{-- Top selectors --}}
  <div class="bg-white rounded-2xl border border-gray-200 p-4 shadow-sm mb-6">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">System Category</label>
        <select wire:model.live="selectedCategory" class="w-full rounded-md border-gray-300">
          @foreach($categories as $cat)
            <option value="{{ $cat }}">{{ $cat }}</option>
          @endforeach
        </select>
      </div>
      <div class="md:col-span-2 text-xs text-gray-500 flex items-end">
        <div>Choose two systems to compare.</div>
      </div>
    </div>
  </div>

  {{-- Options A & B --}}
  <div class="space-y-8">

    {{-- Option A --}}
    <div class="bg-white rounded-2xl border border-gray-200 p-5 shadow-sm">
      <div class="flex items-center justify-between mb-4">
        <div class="text-sm font-semibold text-gray-900">Option A</div>
        <div class="w-80">
          <label class="block text-xs font-medium text-gray-600 mb-1">System selection</label>
          <select wire:model.live="codeA" class="w-full rounded-md border-gray-300">
            @foreach(($systemsByCategory[$selectedCategory] ?? []) as $sys)
              <option value="{{ $sys['code'] }}">{{ $sys['name'] }} ({{ $sys['code'] }})</option>
            @endforeach
          </select>
        </div>
      </div>

      <x-kpi-row :pack="$optionA" />

      <x-layer-table :pack="$optionA" chart-id="chartA" />

    </div>

    {{-- Option B --}}
    <div class="bg-white rounded-2xl border border-gray-200 p-5 shadow-sm">
      <div class="flex items-center justify-between mb-4">
        <div class="text-sm font-semibold text-gray-900">Option B</div>
        <div class="w-80">
          <label class="block text-xs font-medium text-gray-600 mb-1">System selection</label>
          <select wire:model.live="codeB" class="w-full rounded-md border-gray-300">
            @foreach(($systemsByCategory[$selectedCategory] ?? []) as $sys)
              <option value="{{ $sys['code'] }}">{{ $sys['name'] }} ({{ $sys['code'] }})</option>
            @endforeach
          </select>
        </div>
      </div>

      <x-kpi-row :pack="$optionB" />

      <x-layer-table :pack="$optionB" chart-id="chartB" />

    </div>
  </div>
</div>

{{-- Inline anonymous component: KPI row --}}
@php
  // format helper
  $fmt = fn($v, $dec=2) => is_numeric($v) ? number_format((float)$v, $dec) : '—';
@endphp

@once
  @push('styles')
    <style>
      .kpi { border-radius: 0.75rem; border: 1px solid #e5e7eb; padding: 0.75rem }
      .kpi .label { font-size: 0.7rem; color: #6b7280 }
      .kpi .value { font-size: 1.25rem; font-weight: 800 }
    </style>
  @endpush
@endonce

@php
  // Anonymous components implemented inline for convenience
@endphp
@component('components::anonymous', ['name' => 'kpi'])
@endcomponent

{{-- KPI row as Blade inline component --}}
@php
  // register simple include macros
@endphp

{{-- KPI row --}}
@once
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endonce

{{-- KPI row macro --}}
@php
  /** @var callable $fmt */
@endphp
@if(false){{-- silence Blade parser --}}@endif

@php
  // render helpers
@endphp

{{-- KPI ROW partial --}}
@php
  // Nothing here; the real rendering is in x-kpi-row below via @php section
@endphp

{{-- x-kpi-row --}}
@php
  // Provide the "x-kpi-row" and "x-layer-table" as inline Blade "components"
@endphp

{{-- x-kpi-row --}}
@php
  $__env->startComponent('livewire.environmental.partials.kpi-row', ['pack' => $optionA]); $__env->render(); 
@endphp

{{-- We can't actually register dynamic inline components easily without files,
     so instead we expand them directly below with small @php includes. --}}

{{-- --- KPI ROW TEMPLATE (expanded) --- --}}
@php
  $renderKpiRow = function(array $pack) use ($fmt) {
    $k = $pack['kpi'] ?? [];
@endphp
    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-4">
      <div class="kpi"><div class="label">Layers (count)</div><div class="value">{{ $k['layer_count'] ?? '—' }}</div></div>
      <div class="kpi"><div class="label">Mass (kg/m²)</div><div class="value">{{ $fmt($k['mass_total']) }}</div></div>
      <div class="kpi"><div class="label">A1–A3 (kgCO₂e/m²)</div><div class="value">{{ $fmt($k['a1a3_total']) }}</div></div>
      <div class="kpi"><div class="label">Result U (W/m²·K)</div><div class="value">{{ $fmt($k['u_value']) }}</div></div>
      <div class="kpi"><div class="label">Life expectancy (years)</div><div class="value">{{ $fmt($k['life_years'],0) }}</div></div>
    </div>
@php
  };
@endphp

{{-- --- LAYER TABLE TEMPLATE (expanded) --- --}}
@php
  $renderLayerTable = function(array $pack, string $chartId) use ($fmt) {
    $rows = $pack['layers'] ?? [];
    $chartRows = $pack['chartRows'] ?? [];
@endphp
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="border-b">
          <tr>
            <th class="text-left py-1 pr-3">Layer No.</th>
            <th class="text-left py-1 pr-3">Functional Role</th>
            <th class="text-left py-1 pr-3">Generic Material</th>
            <th class="text-right py-1 pr-3">Mass (kg/m²)</th>
            <th class="text-right py-1 pr-3">Carbon Factor</th>
            <th class="text-left py-1 pr-3">CF Unit</th>
            <th class="text-right py-1 pr-3">Total GWP (kgCO₂e/m²)</th>
            <th class="text-right py-1 pr-3">Thermal Conductivity (W/m·K)</th>
            <th class="text-right py-1 pr-3">R-Value (m²K/W)</th>
            <th class="text-right py-1 pr-3">U-Value (W/m²·K)</th>
            <th class="text-right py-1">Life Exp. (years)</th>
          </tr>
        </thead>
        <tbody>
          @foreach($rows as $r)
            <tr class="border-b last:border-0">
              <td class="py-1 pr-3">{{ $r['layer_no'] }}</td>
              <td class="py-1 pr-3">{{ $r['functional_role'] }}</td>
              <td class="py-1 pr-3">{{ $r['generic_material'] }}</td>
              <td class="py-1 pr-3 text-right">{{ is_null($r['mass_kg_m2']) ? '—' : $fmt($r['mass_kg_m2']) }}</td>
              <td class="py-1 pr-3 text-right">{{ is_null($r['carbon_factor']) ? '—' : $fmt($r['carbon_factor']) }}</td>
              <td class="py-1 pr-3">{{ $r['carbon_factor_unit'] ?? '—' }}</td>
              <td class="py-1 pr-3 text-right">{{ is_null($r['a1a3_per_m2']) ? '—' : $fmt($r['a1a3_per_m2']) }}</td>
              <td class="py-1 pr-3 text-right">{{ is_null($r['thermal_conductivity_w_mk']) ? '—' : $fmt($r['thermal_conductivity_w_mk']) }}</td>
              <td class="py-1 pr-3 text-right">{{ is_null($r['r_value_m2k_w']) ? '—' : $fmt($r['r_value_m2k_w']) }}</td>
              <td class="py-1 pr-3 text-right">{{ is_null($r['u_value_w_m2k']) ? '—' : $fmt($r['u_value_w_m2k']) }}</td>
              <td class="py-1 text-right">{{ is_null($r['life_expectancy_years']) ? '—' : $fmt($r['life_expectancy_years'], 0) }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    {{-- Chart --}}
    <div class="mt-4">
      <h3 class="text-sm font-semibold mb-2">Per-layer Mass (gradient by contribution)</h3>
      <canvas id="{{ $chartId }}" height="120"></canvas>
    </div>

    <script>
      (function() {
        const el = document.getElementById(@json($chartId));
        if (!el || el.dataset.rendered === '1') return;

        const rows = @json($chartRows);
        if (!rows || !rows.length) return;

        const labels = rows.map(r => r.label);
        const masses = rows.map(r => r.mass);
        const a1a3   = rows.map(r => r.a1a3);
        const maxA1A3 = Math.max(0.00001, ...a1a3);

        const bg = a1a3.map(v => {
          const t = Math.max(0, Math.min(1, v / maxA1A3));
          const L = 85 - Math.round(t * 40);
          return `hsl(210 70% ${L}%)`;
        });
        const border = a1a3.map(() => 'hsl(210 70% 35%)');

        new Chart(el.getContext('2d'), {
          type: 'bar',
          data: {
            labels,
            datasets: [{
              label: 'Mass (kg/m²)',
              data: masses,
              backgroundColor: bg,
              borderColor: border,
              borderWidth: 1
            }]
          },
          options: {
            responsive: true,
            plugins: {
              legend: { display: false },
              tooltip: {
                callbacks: {
                  afterBody: items => {
                    const i = items[0].dataIndex;
                    return `A1–A3: ${a1a3[i].toFixed(2)} kgCO₂e/m²`;
                  }
                }
              }
            },
            scales: {
              y: { beginAtZero: true, title: { display: true, text: 'kg/m²' } }
            }
          }
        });

        el.dataset.rendered = '1';
      })();
    </script>
@php
  }; // end $renderLayerTable
@endphp

{{-- Render the two blocks --}}
@php $renderKpiRow($optionA); @endphp
@php $renderLayerTable($optionA, 'chartA'); @endphp

@php $renderKpiRow($optionB); @endphp
@php $renderLayerTable($optionB, 'chartB'); @endphp

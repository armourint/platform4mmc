{{-- resources/views/livewire/assessments/results.blade.php --}}
<div class="rounded-xl border bg-white p-5 md:p-6">
  <div class="mb-4 flex items-center justify-between">
    @php
      $systemCode = $systemCode ?? ($viability['system_code'] ?? null);
      $project = $assessment->project ?? null;
      $perSystem = $viability['per_system'] ?? [];
      $includes = $viability['includes_count'] ?? [];
      $excludes = $viability['excludes_count'] ?? [];
      $sys = $systemCode && isset($perSystem[$systemCode]) ? $perSystem[$systemCode] : null;
      $ok = $sys ? ($sys['ok'] ?? true) : null;
    @endphp

    <div>
      <div class="text-sm text-slate-500">System</div>
      <div class="text-xl font-semibold">{{ $systemCode ?: '—' }}</div>
    </div>

    <div class="flex items-center gap-2">
      @if($project)
        {{-- If you have a project "show" route, swap this link --}}
        <a href="{{ route('projects.index') }}"
           class="inline-flex items-center rounded-lg border px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
          Back to Projects
        </a>
      @else
        <a href="{{ route('projects.index') }}"
           class="inline-flex items-center rounded-lg border px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
          Back to Projects
        </a>
      @endif

      <button type="button"
              onclick="window.print()"
              class="inline-flex items-center rounded-lg bg-slate-900 px-3 py-2 text-sm font-medium text-white hover:bg-slate-800">
        Print / Save as PDF
      </button>
    </div>
  </div>

  {{-- Viability summary --}}
  <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">
    <div class="rounded-xl border p-4">
      <div class="mb-1 text-sm text-slate-500">Viability</div>
      @if($ok === true)
        <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-1 text-sm text-emerald-700">
          Viable
        </span>
      @elseif($ok === false)
        <span class="inline-flex items-center rounded-full bg-rose-100 px-2 py-1 text-sm text-rose-700">
          Not viable
        </span>
      @else
        <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-1 text-sm text-slate-600">
          Unknown
        </span>
      @endif
    </div>

    <div class="rounded-xl border p-4">
      <div class="mb-1 text-sm text-slate-500">Include rules</div>
      <div class="text-xl font-semibold">{{ $systemCode ? ($includes[$systemCode] ?? 0) : 0 }}</div>
    </div>

    <div class="rounded-xl border p-4">
      <div class="mb-1 text-sm text-slate-500">Exclude rules</div>
      <div class="text-xl font-semibold">{{ $systemCode ? ($excludes[$systemCode] ?? 0) : 0 }}</div>
    </div>
  </div>

  {{-- Viability reasons --}}
  <div class="mb-8">
    <div class="mb-2 font-semibold">Viability checks</div>
    @if($ok === false && !empty($sys['failed']))
      <ul class="list-disc space-y-1 pl-5">
        @foreach($sys['failed'] as $fail)
          <li>{{ $fail['reason'] ?? 'Excluded by rule.' }}</li>
        @endforeach
      </ul>
    @elseif($ok === true)
      <div class="text-slate-600">No blocking rules detected for the selected system.</div>
    @else
      <div class="text-slate-600">No rule evaluations available.</div>
    @endif
  </div>

  {{-- Environmental summary --}}
  <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-2">
    <div class="rounded-xl border p-4">
      <div class="mb-1 text-sm text-slate-500">Embodied carbon A1–A3 (per m²)</div>
      <div class="text-xl font-semibold">
        @if(isset($env['a1a3_total']) && $env['a1a3_total'] !== null)
          {{ number_format($env['a1a3_total'], 2) }} kgCO₂e/m²
        @else
          N/A
        @endif
      </div>
    </div>
    <div class="rounded-xl border p-4">
      <div class="mb-1 text-sm text-slate-500">Transport A4 (per m²)</div>
      <div class="text-xl font-semibold">
        @if(isset($env['a4_total']) && $env['a4_total'] !== null)
          {{ number_format($env['a4_total'], 2) }} kgCO₂e/m²
        @else
          N/A
        @endif
      </div>
    </div>
  </div>

  {{-- Layer breakdown --}}
  <div class="mb-8">
    <div class="mb-2 font-semibold">Layer breakdown (per m²)</div>
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead>
          <tr class="text-left text-slate-500">
            <th class="py-2 pr-4">Layer</th>
            <th class="py-2 pr-4">Thickness (mm)</th>
            <th class="py-2 pr-4">Density (kg/m³)</th>
            <th class="py-2 pr-4">Mass (kg/m²)</th>
            <th class="py-2 pr-4">A1–A3 (kgCO₂e/m²)</th>
          </tr>
        </thead>
        <tbody class="divide-y">
          @forelse($env['layers'] ?? [] as $layer)
            <tr>
              <td class="py-2 pr-4">{{ $layer['layer'] ?? '—' }}</td>
              <td class="py-2 pr-4">{{ $layer['thickness_mm'] ?? '—' }}</td>
              <td class="py-2 pr-4">{{ $layer['density_kg_m3'] ?? '—' }}</td>
              <td class="py-2 pr-4">
                {{ isset($layer['mass_m2']) ? number_format($layer['mass_m2'], 2) : '—' }}
              </td>
              <td class="py-2 pr-4">
                {{ isset($layer['a1a3_m2']) ? number_format($layer['a1a3_m2'], 2) : '—' }}
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="py-4 text-slate-500">No layer data available.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  {{-- Hotspots --}}
  <div>
    <div class="mb-2 font-semibold">Hotspots</div>
    @php
      $hot = $env['hotspots'] ?? [];
      $max = 0.0;
      foreach ($hot as $h) { $max = max($max, (float)($h['a1a3_m2'] ?? 0)); }
    @endphp
    @if(!empty($hot) && $max > 0)
      <div class="space-y-2">
        @foreach($hot as $h)
          @php
            $val = (float)($h['a1a3_m2'] ?? 0);
            $w = $max > 0 ? max(2, intval(($val / $max) * 100)) : 2;
          @endphp
          <div>
            <div class="mb-1 flex justify-between text-sm">
              <span>{{ $h['layer'] ?? '—' }}</span>
              <span>{{ number_format($val, 2) }} (relative)</span>
            </div>
            <div class="h-2 rounded bg-slate-200">
              <div class="h-2 rounded bg-slate-500" style="width: {{ $w }}%"></div>
            </div>
          </div>
        @endforeach
      </div>
    @else
      <div class="text-slate-600">No identifiable hotspots.</div>
    @endif
  </div>
</div>

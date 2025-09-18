<div class="mx-auto max-w-4xl space-y-6">
  <div class="rounded-xl border bg-white p-6">
    <div class="flex items-start justify-between">
      <div>
        <h1 class="text-2xl font-semibold capitalize">{{ $assessment->type }} Assessment Results</h1>
        <div class="mt-1 text-sm text-gray-600">Project: <strong>{{ $assessment->project->name }}</strong></div>
      </div>
      <div class="text-sm text-gray-500">Dataset: {{ $assessment->datasetVersion?->version_label ?? '—' }}</div>
    </div>
  </div>

  @php $outputs = $assessment->outputs ?? []; @endphp

  @if($assessment->type === 'viability')
    <div class="rounded-xl border bg-white p-6">
      <div class="text-lg font-semibold">Viability Score</div>
      <div class="mt-1 text-3xl">{{ $outputs['score'] ?? '—' }}/{{ count($outputs['per_system'] ?? []) }}</div>
      <div class="mt-6 grid gap-6 md:grid-cols-2">
        <div>
          <div class="mb-2 font-medium">Viable Systems</div>
          <ul class="list-disc pl-5 text-sm text-gray-700">
            @forelse($outputs['viable_systems'] ?? [] as $s)
              <li>{{ $s }}</li>
            @empty
              <li>None</li>
            @endforelse
          </ul>
        </div>
        <div>
          <div class="mb-2 font-medium">Non-viable Systems (reasons)</div>
          <ul class="list-disc pl-5 text-sm text-gray-700">
            @forelse($outputs['non_viable_systems'] ?? [] as $nv)
              <li><strong>{{ $nv['system'] }}</strong>: {{ $nv['reason'] ?? 'Not viable' }}</li>
            @empty
              <li>—</li>
            @endforelse
          </ul>
        </div>
      </div>
    </div>
  @endif

  @if($assessment->type === 'environmental')
    <div class="grid gap-6 md:grid-cols-3">
      <div class="rounded-xl border bg-white p-6">
        <div class="text-sm text-gray-500">Total Embodied Carbon</div>
        <div class="mt-1 text-2xl font-semibold">{{ number_format($outputs['total'] ?? 0, 3) }} kgCO₂e</div>
      </div>
      <div class="rounded-xl border bg-white p-6">
        <div class="text-sm text-gray-500">Embodied Carbon Intensity</div>
        <div class="mt-1 text-2xl font-semibold">
          @if(($outputs['per_m2'] ?? null) !== null)
            {{ number_format($outputs['per_m2'], 3) }} kgCO₂e/m²
          @else
            —
          @endif
        </div>
      </div>
      <div class="rounded-xl border bg-white p-6">
        <div class="text-sm text-gray-500">Dataset</div>
        <div class="mt-1 text-2xl font-semibold">{{ $outputs['dataset_label'] ?? '—' }}</div>
      </div>
    </div>

    <div class="rounded-xl border bg-white p-6">
      <div class="text-lg font-semibold">Breakdown by Stage</div>
      <dl class="mt-4 grid gap-4 md:grid-cols-3">
        <div><dt class="text-sm text-gray-500">A1–A3</dt><dd class="text-xl">{{ number_format($outputs['breakdown']['A1-A3'] ?? 0, 3) }}</dd></div>
        <div><dt class="text-sm text-gray-500">A4–A5</dt><dd class="text-xl">{{ number_format($outputs['breakdown']['A4-A5'] ?? 0, 3) }}</dd></div>
        <div><dt class="text-sm text-gray-500">C1–C4</dt><dd class="text-xl">{{ number_format($outputs['breakdown']['C1-C4'] ?? 0, 3) }}</dd></div>
      </dl>
    </div>
  @endif

  <div>
    <a href="{{ route('assessments.hub', $assessment->project) }}" class="rounded border px-3 py-2 text-sm">Back to Assessments Hub</a>
  </div>
</div>

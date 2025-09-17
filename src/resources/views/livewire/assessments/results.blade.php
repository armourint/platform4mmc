<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold">Results — {{ $assessment->project->name }}</h2>
    </x-slot>

    <div class="p-6 space-y-6">
        @if($assessment->type === 'viability')
            <div class="rounded-lg border bg-white p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-medium">Viability Score</h3>
                    <div class="text-2xl font-semibold">
                        {{ $assessment->score['viability_score'] ?? '—' }}/4
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <div class="font-medium mb-2">Viable systems</div>
                        @if(empty($viable))
                            <div class="text-sm text-gray-500">None</div>
                        @else
                            <ul class="list-disc pl-5">
                                @foreach($viable as $name)
                                    <li>{{ $name }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>

                    <div>
                        <div class="font-medium mb-2">Not viable (with reasons)</div>
                        @if(empty($notViable))
                            <div class="text-sm text-gray-500">None</div>
                        @else
                            <ul class="list-disc pl-5">
                                @foreach($notViable as $row)
                                    <li><span class="font-medium">{{ $row['name'] }}:</span> {{ $row['reason'] ?? '—' }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            </div>
        @else
            <div class="rounded-lg border bg-white p-6">
                <h3 class="font-medium mb-4">Environmental KPIs</h3>
                <div class="grid gap-4 md:grid-cols-3">
                    <div class="rounded-md border p-4">
                        <div class="text-sm text-gray-500">Total (kgCO₂e)</div>
                        <div class="text-2xl font-semibold">{{ $env['total_kgco2e'] ?? '—' }}</div>
                    </div>
                    <div class="rounded-md border p-4">
                        <div class="text-sm text-gray-500">Per m² (kgCO₂e/m²)</div>
                        <div class="text-2xl font-semibold">{{ $env['kgco2e_per_m2'] ?? '—' }}</div>
                    </div>
                    <div class="rounded-md border p-4">
                        <div class="text-sm text-gray-500">Breakdown</div>
                        <div class="text-sm">
                            A1–A3: {{ $env['breakdown']['A1-A3'] ?? '—' }}<br>
                            A4–A5: {{ $env['breakdown']['A4-A5'] ?? '—' }}<br>
                            C1–C4: {{ $env['breakdown']['C1-C4'] ?? '—' }}
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div>
            <a href="{{ route('assessments.hub', $assessment->project) }}" class="text-indigo-600 hover:underline">Back to Assessments</a>
        </div>
    </div>
</x-app-layout>

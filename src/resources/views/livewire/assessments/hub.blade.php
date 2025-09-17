<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold">{{ $project->name }} — Assessments</h2>
    </x-slot>

    <div class="p-6 grid gap-4 md:grid-cols-2">
        {{-- Viability --}}
        <div class="rounded-lg border bg-white p-6">
            <div class="flex items-center justify-between mb-2">
                <div class="font-medium">Viability</div>
                @if(($assessmentsByType['viability'] ?? collect())->isNotEmpty())
                    <a href="{{ route('assessments.results', $assessmentsByType['viability']->last()) }}" class="text-sm text-indigo-600 hover:underline">Last result</a>
                @endif
            </div>
            <p class="text-sm text-gray-600 mb-4">Check which MMC systems are viable for your site constraints.</p>
            <a href="{{ route('assessments.viability', $project) }}" class="px-3 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">Start / Continue</a>
        </div>

        {{-- Environmental --}}
        <div class="rounded-lg border bg-white p-6">
            <div class="flex items-center justify-between mb-2">
                <div class="font-medium">Environmental</div>
                @if(($assessmentsByType['environmental'] ?? collect())->isNotEmpty())
                    <a href="{{ route('assessments.results', $assessmentsByType['environmental']->last()) }}" class="text-sm text-indigo-600 hover:underline">Last result</a>
                @endif
            </div>
            <p class="text-sm text-gray-600 mb-4">Enter A1–A3 / A4–A5 / C1–C4 etc. to view KPIs.</p>
            <a href="{{ route('assessments.environmental', $project) }}" class="px-3 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">Start / Continue</a>
        </div>
    </div>
</x-app-layout>

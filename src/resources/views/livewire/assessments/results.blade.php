<div class="p-6">
    <div class="rounded-lg border bg-white p-6">
        @if(empty($results))
            <div class="text-gray-600">
                <p class="mb-2">Results are not yet calculated for this assessment.</p>
                <p class="text-sm text-gray-500">Once the scoring logic is implemented, this page will visualize outcomes, flags, and recommended MMC systems.</p>
            </div>
        @else
            {{-- Render your results structure here --}}
            <pre class="text-sm">{{ json_encode($results, JSON_PRETTY_PRINT) }}</pre>
        @endif
    </div>

    <div class="mt-6 flex gap-4">
        <a href="{{ route('assessments.hub', $assessment->project) }}" class="text-gray-600 hover:text-gray-800 hover:underline">
            ← Back to Assessment Hub
        </a>
        <a href="{{ route('projects.index') }}" class="text-gray-600 hover:text-gray-800 hover:underline">
            Projects
        </a>
    </div>
</div>

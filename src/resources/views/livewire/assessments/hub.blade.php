<div class="p-6">
    <div class="grid gap-6 md:grid-cols-2">
        <div class="rounded-lg border bg-white p-6">
            <h3 class="text-lg font-semibold mb-3">Run a New Assessment</h3>
            <p class="text-sm text-gray-600 mb-4">
                Choose which Decision Support Tool to run for <span class="font-medium">{{ $project->name }}</span>.
            </p>
            <div class="flex flex-col gap-2">
                <a href="{{ route('assessments.viability', $project) }}"
                   class="inline-flex items-center justify-center px-4 py-2 rounded-md bg-indigo-600 text-white hover:bg-indigo-700">
                    Start Viability Assessment
                </a>
                <a href="{{ route('assessments.environmental', $project) }}"
                   class="inline-flex items-center justify-center px-4 py-2 rounded-md bg-emerald-600 text-white hover:bg-emerald-700">
                    Start Environmental Assessment
                </a>
            </div>
        </div>

        <div class="rounded-lg border bg-white p-6">
            <h3 class="text-lg font-semibold mb-3">Recent Assessments</h3>

            @if($assessments->isEmpty())
                <div class="rounded-md border border-dashed p-6 text-gray-500 text-center">
                    No assessments yet for this project.
                </div>
            @else
                <ul class="divide-y">
                    @foreach($assessments as $a)
                        <li class="py-3 flex items-center justify-between">
                            <div>
                                <div class="font-medium">{{ ucfirst($a->type) }} assessment</div>
                                <div class="text-sm text-gray-500">{{ $a->created_at->format('M j, Y H:i') }}</div>
                            </div>
                            <a class="text-indigo-600 hover:underline"
                               href="{{ route('assessments.results', $a) }}">
                                View results
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    <div class="mt-6">
        <a href="{{ route('projects.index') }}" class="text-gray-600 hover:text-gray-800 hover:underline">
            ← Back to Projects
        </a>
    </div>
</div>

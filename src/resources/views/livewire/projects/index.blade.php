<div class="p-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-medium">Your Projects</h3>
        <a href="{{ route('projects.create') }}"
           class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
            Create Project
        </a>
    </div>

    @if($projects->isEmpty())
        <div class="rounded-lg border border-dashed p-8 text-center text-gray-500">
            No projects yet. Click “Create Project” to add one.
        </div>
    @else
        <div class="grid gap-4 md:grid-cols-2">
            @foreach($projects as $p)
                <div class="rounded-lg border bg-white p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="font-semibold">{{ $p->name }}</div>
                            <div class="text-sm text-gray-500">{{ $p->client ?? '—' }} · {{ $p->location ?? '—' }}</div>
                        </div>
                        <a href="{{ route('assessments.hub', $p) }}" class="text-indigo-600 hover:underline">Open</a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

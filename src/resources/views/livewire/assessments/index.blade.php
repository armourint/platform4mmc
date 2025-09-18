<div class="p-6">
    <p class="text-sm text-gray-600 mb-4">
        Pick a project to view or run Viability / Environmental assessments.
    </p>

    @if($projects->isEmpty())
        <div class="rounded-lg border border-dashed p-8 text-center text-gray-500">
            No projects yet. <a href="{{ route('projects.create') }}" class="text-indigo-600 hover:underline">Create one</a>.
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

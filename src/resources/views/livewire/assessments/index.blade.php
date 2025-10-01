<div class="p-6 space-y-6">
  <div class="flex flex-wrap items-center justify-between gap-3">
    <h1 class="text-xl font-semibold">Assessments</h1>

    <div class="flex flex-wrap items-center gap-2">
      <input
        class="border rounded px-2 py-1 text-sm"
        placeholder="Search name or system code…"
        wire:model.live.debounce.300ms="search"
      />

      <select class="border rounded px-2 py-1 text-sm" wire:model.live="status">
        <option value="">All statuses</option>
        <option value="draft">Draft</option>
        <option value="in_progress">In progress</option>
        <option value="complete">Complete</option>
      </select>

      <select class="border rounded px-2 py-1 text-sm" wire:model.live="project">
        <option value="">All projects</option>
        @foreach($projects as $id => $name)
          <option value="{{ $id }}">{{ $name }}</option>
        @endforeach
      </select>
    </div>
  </div>

  <div class="border rounded bg-white overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead class="bg-gray-50">
        <tr>
          <th class="text-left px-3 py-2">Created</th>
          <th class="text-left px-3 py-2">Name</th>
          <th class="text-left px-3 py-2">Project</th>
          <th class="px-3 py-2 text-left">Type</th> 
          <th class="text-left px-3 py-2">System</th>
          <th class="text-left px-3 py-2">Status</th>
          <th class="text-left px-3 py-2">Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($assessments as $a)
          <tr class="border-t">
            <td class="px-3 py-2 text-gray-600">{{ $a->created_at?->format('Y-m-d H:i') }}</td>
            <td class="px-3 py-2">{{ $a->name ?? 'Assessment #'.$a->id }}</td>
            <td class="px-3 py-2">
              @if($a->project)
                <a href="{{ route('assessments.hub', $a->project_id) }}" class="text-blue-700 hover:underline">
                  {{ $a->project->name }}
                </a>
              @else
                <span class="text-gray-400">—</span>
              @endif
            </td>
            <td class="px-3 py-2">
              <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                {{ $a->type === 'viability'
                    ? 'bg-green-100 text-green-800'
                    : 'bg-blue-100 text-blue-800' }}">
                {{ ucfirst($a->type) }}
              </span>
            </td>
            <td class="px-3 py-2">{{ $a->system_code ?? '—' }}</td>
            <td class="px-3 py-2">
              @php
                $status = $a->status ?? 'draft';
                $badge = [
                  'draft'       => 'bg-gray-200 text-gray-800',
                  'in_progress' => 'bg-amber-200 text-amber-900',
                  'complete'    => 'bg-emerald-200 text-emerald-900',
                ][$status] ?? 'bg-gray-200 text-gray-800';
              @endphp
              <span class="px-2 py-0.5 rounded text-xs {{ $badge }}">{{ str_replace('_',' ', $status) }}</span>
            </td>
            <td class="px-3 py-2 space-x-2">
              <a href="{{ route('assessments.results', $a) }}" class="text-blue-700 hover:underline">Results</a>
              <a href="{{ route('assessments.hub', $a->project_id) }}" class="text-gray-700 hover:underline">Open</a>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="6" class="px-3 py-6 text-gray-500">
              No assessments yet.
              <a href="{{ route('projects.index') }}" class="text-blue-700 hover:underline">Create one from a project</a>.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>

    <div class="p-3">
      {{ $assessments->links() }}
    </div>
  </div>
</div>

@php
  // Mark the current dataset for this module in the dropdown
  $currentId = \App\Models\DatasetVersion::currentId('viability');
@endphp

<div class="p-4 space-y-3">
  <h1 class="text-xl font-semibold">Viability Rules</h1>

  <div class="flex flex-wrap gap-2">
    <select class="border rounded px-2 py-1" wire:model.live="datasetVersionId">
      <option value="">Current (default)</option>
      @foreach($versions as $v)
        <option value="{{ $v->id }}">
          {{ $v->version_label }}
          @if($v->id === $currentId) (current) @endif
        </option>
      @endforeach
    </select>

    <input class="border rounded px-2 py-1" placeholder="Filter system_code…" wire:model.live="systemCode">

    <select class="border rounded px-2 py-1" wire:model.live="ruleType">
      <option value="">All</option><option>include</option><option>exclude</option>
    </select>

    <input class="border rounded px-2 py-1" placeholder="Search reason/module…"
           wire:model.live.debounce.300ms="search">
  </div>

  <div class="overflow-x-auto border rounded">
    <table class="min-w-full text-sm">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-3 py-2 text-left">system_code</th>
          <th class="px-3 py-2 text-left">rule_type</th>
          <th class="px-3 py-2 text-left">module</th>
          <th class="px-3 py-2 text-left">conditions</th>
          <th class="px-3 py-2 text-left">reason</th>
          <th class="px-3 py-2 text-left">priority</th>
        </tr>
      </thead>
      <tbody>
        @forelse($rules as $R)
          <tr class="border-t align-top hover:bg-gray-50">
            <td class="px-3 py-2 font-medium">{{ $R->system_code }}</td>
            <td class="px-3 py-2">
              <span class="px-2 py-0.5 rounded text-white {{ $R->rule_type==='exclude'?'bg-red-500':'bg-emerald-500' }}">
                {{ $R->rule_type }}
              </span>
            </td>
            <td class="px-3 py-2">{{ $R->module }}</td>
            <td class="px-3 py-2">
              @php $c = $R->conditions_json ?? []; @endphp
              @forelse($c as $k=>$v)
                <div><span class="text-gray-500">{{ $k }}:</span>
                  {{ is_bool($v) ? ($v ? 'true':'false') : (is_array($v)? json_encode($v): $v) }}
                </div>
              @empty
                <span class="text-gray-400">—</span>
              @endforelse
            </td>
            <td class="px-3 py-2">{{ $R->reason }}</td>
            <td class="px-3 py-2">{{ $R->priority }}</td>
          </tr>
        @empty
          <tr><td class="px-3 py-6 text-gray-500" colspan="6">No rules.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div>{{ $rules->links() }}</div>
</div>

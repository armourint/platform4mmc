@php
  // Mark the current dataset for this module in the dropdown
  $currentId = \App\Models\DatasetVersion::currentId('environmental');
@endphp

<div class="p-4 space-y-3">
  <h1 class="text-xl font-semibold">Environmental Layers</h1>

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

    <select class="border rounded px-2 py-1" wire:model.live="systemCategory">
      <option value="">All categories</option>
      <option>Wall</option><option>Cladding</option><option>Slab</option>
    </select>

    <input type="text" class="border rounded px-2 py-1" placeholder="Search assembly/name…"
           wire:model.live.debounce.300ms="search"/>
  </div>

  <div class="overflow-x-auto border rounded">
    <table class="min-w-full text-sm">
      <thead class="bg-gray-50">
        <tr>
          @php $cols=[
            'system_code'=>'System','system_name'=>'Name','assembly_id'=>'Assembly',
            'layer_no'=>'Layer','functional_role'=>'Role','generic_material'=>'Material',
            'thickness_m'=>'Thick (m)','mass_kg_m2'=>'Mass (kg/m²)','a1a3_per_m2'=>'A1–A3 (kgCO₂e/m²)'
          ]; @endphp
          @foreach($cols as $k=>$label)
            <th class="text-left font-medium px-3 py-2 cursor-pointer select-none" wire:click="sortBy('{{ $k }}')">
              {{ $label }}
              @if($sort===$k) <span class="text-gray-400">{{ $dir==='asc'?'▲':'▼' }}</span>@endif
            </th>
          @endforeach
        </tr>
      </thead>
      <tbody>
        @forelse($layers as $L)
          <tr class="border-t hover:bg-gray-50">
            <td class="px-3 py-2">{{ $L->system_code }}</td>
            <td class="px-3 py-2">{{ $L->system_name }}</td>
            <td class="px-3 py-2">{{ $L->assembly_id }}</td>
            <td class="px-3 py-2">{{ $L->layer_no }}</td>
            <td class="px-3 py-2">{{ $L->functional_role }}</td>
            <td class="px-3 py-2">{{ $L->generic_material }}</td>
            <td class="px-3 py-2">{{ rtrim(rtrim(number_format($L->thickness_m,3,'.',''), '0'), '.') }}</td>
            <td class="px-3 py-2">{{ number_format($L->mass_kg_m2,2) }}</td>
            <td class="px-3 py-2">{{ number_format($L->a1a3_per_m2,2) }}</td>
          </tr>
        @empty
          <tr><td class="px-3 py-6 text-gray-500" colspan="9">No data.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div>{{ $layers->links() }}</div>
</div>

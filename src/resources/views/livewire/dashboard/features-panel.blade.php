<x-card>
  <x-slot:header>Features</x-slot:header>
  <div class="overflow-hidden rounded-xl border border-slate-200">
    <table class="w-full table-fixed text-xs leading-tight">
      <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
        <tr>
          <th class="px-3 py-2 text-left">Feature</th>
          <th class="px-3 py-2 text-right">Max Value</th>
          <th class="px-3 py-2 text-left">Units</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
        @foreach($rows as $r)
          <tr>
            <td class="px-3 py-1.5">{{ $r['feature'] }}</td>
            <td class="px-3 py-1.5 text-right tabular-nums">{{ $r['max'] }}</td>
            <td class="px-3 py-1">{{ $r['units'] }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</x-card>

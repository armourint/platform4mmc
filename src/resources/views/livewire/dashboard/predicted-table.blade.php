<x-card>
  <x-slot:header>Predicted Water Depth</x-slot:header>
  <div class="overflow-hidden rounded-xl border border-slate-200">
    <table class="w-full text-sm">
      <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
        <tr>
          <th class="px-4 py-2">ML/AI Methods</th>
          <th class="px-4 py-2 text-right">Max Depth (m)</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
        @foreach($max as $label => $value)
          <tr class="hover:bg-slate-50/50">
            <td class="px-4 py-1.5">{{ $label }}</td>
            <td class="px-4 py-1.5 text-right tabular-nums">{{ number_format($value ?? 0, 4) }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</x-card>

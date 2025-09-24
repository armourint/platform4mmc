<div class="p-4 space-y-3">
  <h1 class="text-xl font-semibold">Manufacturers</h1>

  <div class="flex flex-wrap gap-2">
    <input class="border rounded px-2 py-1" placeholder="Search name/address/subcat…" wire:model.live.debounce.300ms="search">
    <select class="border rounded px-2 py-1" wire:model.live="active">
      <option value="">All</option>
      <option value="1">Active</option>
      <option value="0">Inactive</option>
    </select>
  </div>

  <div class="overflow-x-auto border rounded">
    <table class="min-w-full text-sm">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-3 py-2 text-left">Name</th>
          <th class="px-3 py-2 text-left">Category</th>
          <th class="px-3 py-2 text-left">Subcategory</th>
          <th class="px-3 py-2 text-left">County</th>
          <th class="px-3 py-2 text-left">Country</th>
          <th class="px-3 py-2 text-left">Active</th>
        </tr>
      </thead>
      <tbody>
        @forelse($rows as $M)
          <tr class="border-t hover:bg-gray-50">
            <td class="px-3 py-2 font-medium">{{ $M->name }}</td>
            <td class="px-3 py-2">{{ $M->product_category }}</td>
            <td class="px-3 py-2">{{ $M->product_subcategory }}</td>
            <td class="px-3 py-2">{{ $M->county_name ?: $M->county_code }}</td>
            <td class="px-3 py-2">{{ $M->country }}</td>
            <td class="px-3 py-2">{{ $M->is_active ? 'Yes' : 'No' }}</td>
          </tr>
        @empty
          <tr><td class="px-3 py-6 text-gray-500" colspan="6">No manufacturers.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div>{{ $rows->links() }}</div>
</div>

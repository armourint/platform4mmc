<div class="max-w-xl mx-auto">
  <div class="flex items-center justify-between mb-4">
    <h2 class="text-xl font-semibold">Article Categories</h2>
    <a href="{{ route('admin.knowledge.categories.create') }}" class="px-3 py-2 text-sm bg-blue-600 text-white rounded hover:bg-blue-700">
      + New Category
    </a>
  </div>

  <div class="mb-3">
    <input type="text" wire:model.defer="search" placeholder="Search categories..." class="border rounded px-3 py-1 text-sm w-full" />
  </div>

  <div class="bg-white rounded-xl border">
    <table class="min-w-full text-sm">
      <thead class="bg-gray-50 text-gray-700">
        <tr>
          <th class="text-left py-2 px-3">Name</th>
          <th class="text-left py-2 px-3">Type</th>
          <th class="py-2 px-3">Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($categories as $cat)
          <tr class="border-b">
            <td class="py-2 px-3">{{ $cat->name }}</td>
            <td class="py-2 px-3">{{ $cat->type ?? '—' }}</td>
            <td class="py-2 px-3 text-center">
              <a href="{{ route('admin.knowledge.categories.edit', $cat->id) }}" class="text-blue-600 hover:underline mr-3">Edit</a>
              <button wire:click="deleteCategory({{ $cat->id }})" 
                      onclick="return confirm('Delete this category?')" 
                      class="text-red-600 hover:underline">Delete</button>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="3" class="py-4 px-3 text-center text-gray-600">No categories found.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
    <div class="p-2">
      {{ $categories->links() }}
    </div>
  </div>
</div>

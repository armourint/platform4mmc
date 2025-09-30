<div class="max-w-lg mx-auto bg-white p-6 rounded shadow">
  <h2 class="text-xl font-semibold mb-4">Edit Category</h2>

  @if (session('success'))
    <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
  @endif

  <form wire:submit.prevent="save" class="space-y-4">
    <div>
      <label class="block text-sm font-medium mb-1">Name</label>
      <input type="text" wire:model.defer="name" class="w-full border rounded px-3 py-1.5" />
      @error('name') <div class="text-red-600 text-sm">{{ $message }}</div> @enderror
    </div>

    <div>
      <label class="block text-sm font-medium mb-1">
        Type <span class="text-xs text-gray-500">(e.g. phase, topic, mmc_type)</span>
      </label>
      <input type="text" wire:model.defer="type" class="w-full border rounded px-3 py-1.5" />
      @error('type') <div class="text-red-600 text-sm">{{ $message }}</div> @enderror
    </div>

    <div class="flex items-center justify-between">
      <a href="{{ route('admin.knowledge.categories.index') }}" class="text-sm text-gray-600 hover:text-gray-800">
        &larr; Back
      </a>
      <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm rounded hover:bg-blue-700">
        Save Changes
      </button>
    </div>
  </form>
</div>

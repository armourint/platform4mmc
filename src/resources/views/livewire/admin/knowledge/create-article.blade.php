<div class="max-w-4xl mx-auto bg-white p-6 rounded shadow">
  <h2 class="text-xl font-semibold mb-4">New Article</h2>

  @if (session('success'))
    <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
  @endif

  <form wire:submit.prevent="save" class="space-y-6">
    <div>
      <label class="block text-sm font-medium mb-1">Title</label>
      <input type="text" wire:model.defer="title" class="w-full border rounded px-3 py-2" />
      @error('title') <div class="text-red-600 text-sm">{{ $message }}</div> @enderror
    </div>

    <div>
      <label class="block text-sm font-medium mb-1">Body (Markdown or HTML)</label>
      <textarea wire:model.defer="body" rows="8" class="w-full border rounded px-3 py-2"></textarea>
      @error('body') <div class="text-red-600 text-sm">{{ $message }}</div> @enderror
      <p class="text-xs text-gray-500 mt-1">Rich editor out of scope for MVP.</p>
    </div>

    <div>
      <label class="block text-sm font-medium mb-1">Categories</label>
      <select wire:model.defer="categoryIds" multiple class="w-full border rounded px-3 py-2">
        @foreach ($categories->groupBy('type') as $type => $group)
          <optgroup label="{{ ucfirst($type) ?: 'Other' }}">
            @foreach ($group as $cat)
              <option value="{{ $cat->id }}">{{ $cat->name }}</option>
            @endforeach
          </optgroup>
        @endforeach
      </select>
      @error('categoryIds') <div class="text-red-600 text-sm">{{ $message }}</div> @enderror
    </div>

    <div>
      <label class="block text-sm font-medium mb-1">Status</label>
      <select wire:model.defer="status" class="border rounded px-3 py-2">
        <option value="draft">Draft</option>
        <option value="published">Published</option>
      </select>
      @error('status') <div class="text-red-600 text-sm">{{ $message }}</div> @enderror
    </div>

    <div class="flex items-center justify-between">
      <a href="{{ route('admin.knowledge.articles.index') }}" class="text-sm text-gray-600 hover:text-gray-800">
        &larr; Back
      </a>
      <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm rounded hover:bg-blue-700">
        Save Article
      </button>
    </div>
  </form>
</div>

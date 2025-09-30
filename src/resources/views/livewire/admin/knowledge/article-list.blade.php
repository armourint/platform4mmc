<div class="max-w-6xl mx-auto">
  <div class="flex items-center justify-between mb-4">
    <h2 class="text-xl font-semibold">Knowledge Articles</h2>

    <a href="{{ route('admin.knowledge.articles.create') }}"
       class="px-4 py-2 text-sm bg-blue-600 text-white rounded hover:bg-blue-700">
      + New Article
    </a>
  </div>

  <div class="mb-3">
    <input type="text"
           wire:model.defer="q"
           placeholder="Search articles…"
           class="border rounded px-3 py-2 w-full max-w-md text-sm" />
  </div>

  <div class="bg-white rounded-xl border overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead class="bg-gray-50 text-gray-700">
        <tr>
          <th class="text-left py-2 px-3">Title</th>
          <th class="text-left py-2 px-3">Status</th>
          <th class="text-left py-2 px-3">Published</th>
          <th class="py-2 px-3">Actions</th>
        </tr>
      </thead>
      <tbody>
      @forelse($articles as $article)
        <tr class="border-b">
          <td class="py-2 px-3">
            <div class="font-medium">{{ $article->title }}</div>
            <div class="text-xs text-gray-500">
              {{ $article->categories?->pluck('name')->join(', ') }}
            </div>
          </td>
          <td class="py-2 px-3">
            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs
              {{ $article->status === 'published' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700' }}">
              {{ ucfirst($article->status) }}
            </span>
          </td>
          <td class="py-2 px-3 text-gray-600">
            {{ optional($article->published_at)->format('Y-m-d') ?? '—' }}
          </td>
          <td class="py-2 px-3 whitespace-nowrap">
            <a href="{{ route('admin.knowledge.articles.edit', $article->id) }}"
               class="text-blue-600 hover:underline mr-3">Edit</a>
            <button wire:click="deleteArticle({{ $article->id }})"
                    onclick="return confirm('Delete this article?')"
                    class="text-red-600 hover:underline">Delete</button>
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="4" class="py-6 text-center text-gray-600">No articles found.</td>
        </tr>
      @endforelse
      </tbody>
    </table>

    <div class="p-2">
      {{ $articles->links() }}
    </div>
  </div>
</div>

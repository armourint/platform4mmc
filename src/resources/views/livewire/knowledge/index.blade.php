<div class="mx-auto max-w-6xl space-y-6">
  <!-- Page Title and Total Count -->
  <div class="flex items-center justify-between">
    <h1 class="text-2xl font-semibold">Knowledge Hub</h1>
    <div class="text-sm text-gray-600">Total articles: {{ $total }}</div>
  </div>

  <!-- Search and Category Filter Form -->
  <form wire:submit.prevent="search" class="rounded-xl border bg-white p-4">
    <div class="grid gap-3 md:grid-cols-3">
      <!-- Search field -->
      <div class="md:col-span-2">
        <label class="block text-sm font-medium">Search</label>
        <input type="text" wire:model.defer="q" 
               class="mt-1 w-full rounded border px-3 py-2"
               placeholder="Keywords in title or body..." />
      </div>
      <!-- Category filter field -->
      <div>
        <label class="block text-sm font-medium">Category</label>
        <select wire:model.defer="categoryId" class="mt-1 w-full rounded border px-3 py-2">
          <option value="">All Categories</option>
          @foreach ($categories->groupBy('type') as $type => $group)
            <optgroup label="{{ ucfirst($type) ?: 'Other' }}">
              @foreach ($group as $cat)
                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
              @endforeach
            </optgroup>
          @endforeach
        </select>
      </div>
    </div>
    <div class="mt-3">
      <button class="rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">Apply</button>
    </div>
  </form>

  <!-- Results List -->
  <div class="rounded-xl border bg-white">
    @if ($articles->isEmpty())
      <div class="p-6 text-sm text-gray-600">No articles found.</div>
    @else
      <ul class="divide-y">
        @foreach ($articles as $article)
          <li class="p-4">
            <div class="flex items-start justify-between gap-4">
              <div class="flex-1">
                <!-- Article Title -->
                <div class="text-base font-medium">{{ $article->title }}</div>
                <!-- Article excerpt/snippet -->
                <div class="mt-1 text-sm text-gray-600">
                  {{ \Illuminate\Support\Str::limit(strip_tags($article->body), 160) }}
                </div>
                <!-- Meta info: categories and date -->
                <div class="mt-2 flex flex-wrap gap-2 text-xs text-gray-500">
                  @foreach ($article->categories as $cat)
                    <span class="rounded border px-2 py-0.5">
                      {{ $cat->name }} @if($cat->type) ({{ ucfirst($cat->type) }}) @endif
                    </span>
                  @endforeach
                  @if ($article->published_at)
                    <span class="rounded border px-2 py-0.5">
                      {{ $article->published_at->toDateString() }}
                    </span>
                  @endif
                </div>
              </div>
              <!-- View Article Link -->
              <div class="shrink-0 self-center">
                <a href="{{ route('knowledge.show', $article->slug) }}" 
                   class="text-sm text-blue-700 underline">Read More</a>
              </div>
            </div>
          </li>
        @endforeach
      </ul>
      <!-- Pagination controls -->
      <div class="flex items-center justify-between border-t p-3 text-sm">
        <div>Showing {{ $articles->firstItem() }}–{{ $articles->lastItem() }} of {{ $articles->total() }}</div>
        <div class="flex gap-2">
          @if($articles->onFirstPage())
            <span class="px-3 py-1 text-gray-400">Prev</span>
          @else
            <button wire:click="previousPage" class="px-3 py-1 border rounded">Prev</button>
          @endif

          @if($articles->hasMorePages())
            <button wire:click="nextPage" class="px-3 py-1 border rounded">Next</button>
          @else
            <span class="px-3 py-1 text-gray-400">Next</span>
          @endif
        </div>
      </div>
    @endif
  </div>
</div>

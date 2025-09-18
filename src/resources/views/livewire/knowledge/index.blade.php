<div class="mx-auto max-w-6xl space-y-6">
  <div class="flex items-center justify-between">
    <h1 class="text-2xl font-semibold">Knowledge Bank</h1>
    <div class="text-sm text-gray-600">Total items: {{ $total ?? 0 }}</div>
  </div>

  {{-- Search & filters --}}
  <form wire:submit.prevent="search" class="rounded-xl border bg-white p-4">
    <div class="grid gap-3 md:grid-cols-3">
      <div class="md:col-span-2">
        <label class="block text-sm font-medium">Search</label>
        <input type="text" wire:model.defer="q" class="mt-1 w-full rounded border px-3 py-2" placeholder="Keywords, title, body…">
      </div>
      <div>
        <label class="block text-sm font-medium">Type</label>
        <select wire:model.defer="type" class="mt-1 w-full rounded border px-3 py-2">
          <option value="">All</option>
          @foreach(($types ?? []) as $t)
            <option value="{{ $t }}" @selected($type === $t)>{{ ucfirst(str_replace('_',' ', $t)) }}</option>
          @endforeach
        </select>
      </div>
    </div>
    <div class="mt-3">
      <button class="rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">Apply</button>
    </div>
  </form>

  {{-- Results list --}}
  <div class="rounded-xl border bg-white">
    @if(($items ?? collect())->isEmpty())
      <div class="p-6 text-sm text-gray-600">No results found.</div>
    @else
      <ul class="divide-y">
        @foreach($items as $item)
          <li class="p-4">
            <div class="flex items-start justify-between gap-4">
              <div>
                <div class="text-base font-medium">{{ $item->title }}</div>
                <div class="mt-1 text-sm text-gray-600">
                  {{ \Illuminate\Support\Str::limit($item->excerpt ?? strip_tags($item->body ?? ''), 160) }}
                </div>
                <div class="mt-2 flex flex-wrap gap-2 text-xs text-gray-500">
                  @if(!empty($item->type))
                    <span class="rounded border px-2 py-0.5">{{ ucfirst(str_replace('_',' ', $item->type)) }}</span>
                  @endif
                  @if(!empty($item->source))
                    <span class="rounded border px-2 py-0.5">Source: {{ $item->source }}</span>
                  @endif
                  @if(!empty($item->published_at))
                    <span class="rounded border px-2 py-0.5">{{ \Illuminate\Support\Carbon::parse($item->published_at)->toDateString() }}</span>
                  @endif
                </div>
              </div>
              <div class="shrink-0">
                @if(!empty($item->url))
                  <a href="{{ $item->url }}" target="_blank" rel="noopener" class="text-sm text-blue-700 underline">Open</a>
                @else
                  <a href="{{ route('knowledge.index') }}?view={{ $item->id }}" class="text-sm text-blue-700 underline">View</a>
                @endif
              </div>
            </div>
          </li>
        @endforeach
      </ul>

      {{-- Pagination --}}
      <div class="flex items-center justify-between border-t p-3 text-sm">
        <div>Showing {{ $items->firstItem() }}–{{ $items->lastItem() }} of {{ $items->total() }}</div>
        <div class="flex gap-2">
          @if($items->onFirstPage())
            <span class="cursor-not-allowed rounded border px-3 py-1 text-gray-400">Prev</span>
          @else
            <button wire:click="gotoPage({{ $items->currentPage() - 1 }})" class="rounded border px-3 py-1">Prev</button>
          @endif

          @if($items->hasMorePages())
            <button wire:click="gotoPage({{ $items->currentPage() + 1 }})" class="rounded border px-3 py-1">Next</button>
          @else
            <span class="cursor-not-allowed rounded border px-3 py-1 text-gray-400">Next</span>
          @endif
        </div>
      </div>
    @endif
  </div>
</div>

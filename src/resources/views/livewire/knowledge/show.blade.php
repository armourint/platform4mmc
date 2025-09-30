<div class="mx-auto max-w-4xl py-6">
  <!-- Breadcrumb / Back link -->
  <a href="{{ route('knowledge.index') }}" class="text-sm text-blue-600 hover:text-blue-800">&larr; Back to Knowledge Hub</a>

  <!-- Article Title -->
  <h1 class="mt-2 text-3xl font-bold">{{ $article->title }}</h1>

  <!-- Meta: categories and date -->
  <div class="mt-2 flex flex-wrap gap-2 text-sm text-gray-600">
    @foreach ($article->categories as $cat)
      <span class="px-2 py-0.5 rounded border">
        {{ $cat->name }} @if($cat->type) ({{ ucfirst($cat->type) }}) @endif
      </span>
    @endforeach
    @if ($article->published_at)
      <span class="px-2 py-0.5 rounded border">
        Published {{ $article->published_at->toFormattedDateString() }}
      </span>
    @endif
  </div>

  <!-- Article Body -->
  <div class="prose prose-sm sm:prose lg:prose-lg mt-6">
    {!! \Illuminate\Support\Str::markdown($article->body) !!}
    <!-- If body is already HTML, you could just do: {!! $article->body !!} -->
  </div>
</div>

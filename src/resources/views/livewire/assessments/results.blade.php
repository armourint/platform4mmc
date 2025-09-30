<div class="max-w-6xl mx-auto px-4 py-6">
  {{-- Header --}}
  <div class="mb-6">
    <h1 class="text-2xl font-semibold text-gray-900">Assessment Results</h1>
    @if(!empty($summary))
      <div class="mt-2 flex items-center gap-2 text-sm">
        <span class="inline-flex items-center px-2 py-0.5 rounded-full
                      {{ ($summary['status'] ?? 'OK') === 'OK' ? 'bg-green-100 text-green-800' :
                          (($summary['status'] ?? '') === 'Attention' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
          {{ $summary['status'] ?? 'OK' }}
        </span>
        <span class="text-gray-500">
          {{ $summary['include_count'] ?? 0 }} viable • {{ $summary['exclude_count'] ?? 0 }} not viable
        </span>
      </div>
    @endif
  </div>

  {{-- Two-column layout --}}
  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

    {{-- Left: Viable methods --}}
    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
      <div class="flex items-center gap-2 mb-4">
        <span class="text-xl">🌿</span>
        <h2 class="text-lg font-semibold text-gray-900">Viable methods</h2>
      </div>

      @if($included->isEmpty())
        <div class="text-sm text-gray-500">No methods meet the current requirements.</div>
      @else
        <div class="flex flex-wrap gap-2">
          @foreach ($included as $sys)
            <span class="inline-flex items-center rounded-full bg-green-100 text-green-800 px-3 py-1 text-sm">
              {{ $sys['name'] }}
            </span>
          @endforeach
        </div>
      @endif
    </div>

    {{-- Right: Not viable & reasons --}}
    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
      <div class="flex items-center gap-2 mb-4">
        <span class="text-xl">⚠️</span>
        <h2 class="text-lg font-semibold text-gray-900">Not viable &amp; reasons</h2>
      </div>

      @if($excluded->isEmpty())
        <div class="text-sm text-gray-500">Great news — none of the methods were excluded for the current inputs.</div>
      @else
        <div class="space-y-4">
          @foreach ($excluded as $sys)
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-red-100 text-red-700 text-sm font-semibold">
                    {{ strtoupper(substr($sys['name'], 0, 3)) }}
                  </span>
                  <div class="text-base font-semibold text-gray-900">{{ $sys['name'] }}</div>
                </div>
                <span class="inline-flex items-center rounded-full bg-gray-200 text-gray-700 px-2 py-0.5 text-xs">
                  {{ count($sys['reasons']) }} reason(s)
                </span>
              </div>

              @if(!empty($sys['reasons']))
                <ul class="mt-3 list-disc pl-5 text-sm text-gray-800 space-y-1">
                  @foreach ($sys['reasons'] as $reason)
                    <li>{{ $reason }}</li>
                  @endforeach
                </ul>
              @else
                <p class="mt-3 text-sm text-gray-500">Excluded (no reason provided).</p>
              @endif
            </div>
          @endforeach
        </div>
      @endif

    </div>
  </div>

  {{-- Optional: Back links --}}
  <div class="mt-8">
    <a href="{{ url()->previous() }}"
       class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-md border bg-white hover:bg-gray-50 text-gray-700">
      ← Back
    </a>
  </div>
</div>

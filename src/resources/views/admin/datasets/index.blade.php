@extends('layouts.app')

@section('title','Admin – Datasets')

@section('content')
<div class="mx-auto max-w-6xl space-y-6">
  <h1 class="text-2xl font-semibold">Datasets</h1>

  <form method="get" class="flex items-center gap-3">
    <label class="text-sm">Module</label>
    <select name="module" class="rounded border px-3 py-2" onchange="this.form.submit()">
      <option value="viability" @selected($module==='viability')>Viability</option>
      <option value="environmental" @selected($module==='environmental')>Environmental</option>
    </select>
  </form>

  <div class="rounded-xl border bg-white p-6">
    <h2 class="mb-3 text-lg font-semibold">Create Draft</h2>
    <form method="post" action="{{ route('admin.datasets.create') }}" class="grid gap-3 md:grid-cols-3">
      @csrf
      <input type="hidden" name="module" value="{{ $module }}">
      <div>
        <label class="block text-sm font-medium">Version Label</label>
        <input name="version_label" class="mt-1 w-full rounded border px-3 py-2" placeholder="v2025.09">
      </div>
      <div class="md:col-span-2">
        <label class="block text-sm font-medium">Notes</label>
        <input name="notes" class="mt-1 w-full rounded border px-3 py-2" placeholder="Optional notes">
      </div>
      <div class="md:col-span-3">
        <button class="rounded bg-blue-600 px-4 py-2 text-white">Create Draft</button>
      </div>
    </form>
  </div>

  <div class="rounded-xl border bg-white p-6">
    <h2 class="mb-3 text-lg font-semibold">Existing Datasets</h2>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="text-left text-gray-600">
            <th class="px-3 py-2">ID</th>
            <th class="px-3 py-2">Version</th>
            <th class="px-3 py-2">Status</th>
            <th class="px-3 py-2">Created</th>
            <th class="px-3 py-2">Actions</th>
          </tr>
        </thead>
        <tbody>
        @forelse($datasets as $ds)
          <tr class="border-t">
            <td class="px-3 py-2">{{ $ds->id }}</td>
            <td class="px-3 py-2 font-medium">{{ $ds->version_label }}</td>
            <td class="px-3 py-2">{{ ucfirst($ds->status) }}</td>
            <td class="px-3 py-2">{{ $ds->created_at->format('Y-m-d') }}</td>
            <td class="px-3 py-2">
              <div class="flex flex-wrap items-center gap-2">
                @if($ds->status !== 'published')
                <form method="post" action="{{ route('admin.datasets.publish',$ds) }}">
                  @csrf
                  <button class="rounded border px-3 py-1">Publish</button>
                </form>
                @endif
                @if($ds->status !== 'archived')
                <form method="post" action="{{ route('admin.datasets.archive',$ds) }}">
                  @csrf
                  <button class="rounded border px-3 py-1">Archive</button>
                </form>
                @endif
              </div>
            </td>
          </tr>

          {{-- Only for the viability module show the rules import UI --}}
          @if($module === 'viability')
            <tr class="bg-gray-50">
              <td colspan="5" class="px-3 py-3">
                <div class="text-xs text-gray-600 mb-2">Import Rules (JSON array)</div>
                <form method="post" action="{{ route('admin.datasets.importRules',$ds) }}">
                  @csrf
                  <textarea name="rules_json" class="h-40 w-full rounded border p-2 font-mono text-xs" placeholder='[
  {"system_code":"LGS","priority":10,"rule_type":"exclude","conditions":{"residential_type":{"in":["high"]}},"reason":"LGS excluded for high-rise"},
  {"system_code":"TIMBER","priority":20,"rule_type":"exclude","conditions":{"storage_type":{"eq":"off-site"}},"reason":"Timber requires on-site storage"}
]'></textarea>
                  <div class="mt-2">
                    <button class="rounded bg-blue-600 px-3 py-1 text-white">Import Rules</button>
                  </div>
                </form>
              </td>
            </tr>
          @endif

        @empty
          <tr><td colspan="5" class="px-3 py-4 text-center text-gray-500">No datasets yet</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>

    @if($module === 'environmental')
      <div class="mt-4 rounded border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
        Environmental datasets don’t use rules. Create & publish a dataset here to version environmental baselines/labels.
      </div>
    @endif
  </div>

  @if(session('ok'))
    <div class="rounded border border-green-200 bg-green-50 p-3 text-sm text-green-700">
      {{ session('ok') }}
    </div>
  @endif
  @if($errors->any())
    <div class="rounded border border-red-200 bg-red-50 p-3 text-sm text-red-700">
      {{ $errors->first() }}
    </div>
  @endif
</div>
@endsection

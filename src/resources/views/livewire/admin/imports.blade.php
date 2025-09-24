<div class="space-y-6">
  <h1 class="text-xl font-semibold">Imports</h1>

  @if (session('ok'))
    <div class="p-3 rounded bg-emerald-50 text-emerald-800 text-sm">{{ session('ok') }}</div>
  @endif
  @error('file') <div class="p-3 rounded bg-red-50 text-red-700 text-sm">{{ $message }}</div> @enderror

  {{-- Upload --}}
  <div class="border rounded bg-white">
    <div class="p-4 border-b font-medium">Upload & Queue Import</div>
    <div class="p-4 space-y-3">
      <div class="flex flex-wrap gap-3">
        <div>
          <label class="block text-xs text-gray-600 mb-1">Module</label>
          <select class="border rounded px-2 py-1" wire:model="module">
            <option value="">Select…</option>
            @foreach($modules as $k=>$label)
              <option value="{{ $k }}">{{ $label }}</option>
            @endforeach
          </select>
          @error('module') <div class="text-xs text-red-600 mt-1">{{ $message }}</div> @enderror
        </div>

        <div class="flex-1 min-w-[240px]">
          <label class="block text-xs text-gray-600 mb-1">File (.xlsx, .csv, .json)</label>
          <input type="file" wire:model="file" class="block w-full text-sm">
        </div>

        <div>
          <label class="block text-xs text-gray-600 mb-1">Version label (optional)</label>
          <input type="text" wire:model="versionLabel" placeholder="e.g. v2025.09"
                 class="border rounded px-2 py-1">
        </div>

        <div class="grow min-w-[240px]">
          <label class="block text-xs text-gray-600 mb-1">Notes (optional)</label>
          <input type="text" wire:model="notes" class="border rounded px-2 py-1 w-full">
        </div>

        @if($module === 'environmental')
          <div>
            <label class="block text-xs text-gray-600 mb-1">Sheet (optional)</label>
            <input type="text" wire:model="sheet" placeholder="e.g. Wall Systems"
                   class="border rounded px-2 py-1">
          </div>
        @endif

        <div class="self-end">
          <button wire:click="submit" wire:loading.attr="disabled"
                  class="px-3 py-1.5 rounded bg-blue-600 text-white text-sm">
            Queue Import
          </button>
        </div>
      </div>

      <div class="flex flex-wrap items-center gap-4 pt-2 text-sm">
        @if($supportsReset)
        <label class="inline-flex items-center gap-2">
          <input type="checkbox" wire:model="resetBefore" class="h-4 w-4">
          <span>Reset before import</span>
        </label>
        @endif

        <label class="inline-flex items-center gap-2">
          <input type="checkbox" wire:model="verbose" class="h-4 w-4">
          <span>Verbose (-vvv)</span>
        </label>

        <div wire:loading wire:target="file" class="text-xs text-gray-600">Uploading…</div>
      </div>
    </div>
  </div>

  {{-- History --}}
  <div class="border rounded bg-white overflow-x-auto"
       @if($shouldPoll) wire:poll.2s.visible @endif>
    <div class="p-4 border-b font-medium">Recent Imports</div>
    <div class="p-4">
      <div class="flex flex-wrap gap-2 mb-3">
        <select class="border rounded px-2 py-1" wire:model.live="status">
          <option value="">All statuses</option>
          <option>queued</option><option>processing</option>
          <option>completed</option><option>failed</option>
        </select>
        <input class="border rounded px-2 py-1" placeholder="Search name/module…"
               wire:model.live.debounce.300ms="search">
      </div>

      <table class="min-w-full text-sm" wire:key="imports-table">
        <thead class="bg-gray-50">
          <tr>
            <th class="text-left px-3 py-2">When</th>
            <th class="text-left px-3 py-2">Module</th>
            <th class="text-left px-3 py-2">Dataset</th>
            <th class="text-left px-3 py-2">File</th>
            <th class="text-left px-3 py-2">Status</th>
            <th class="text-left px-3 py-2">Error</th>
            <th class="text-left px-3 py-2">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($imports as $imp)
            @php
              $currentForModule = $moduleCurrents[$imp->module] ?? null;
              $isCurrent = $currentForModule && $currentForModule === $imp->dataset_version_id;
            @endphp
            <tr class="border-t align-top">
              <td class="px-3 py-2 text-gray-600">{{ $imp->created_at->format('Y-m-d H:i') }}</td>
              <td class="px-3 py-2">{{ config("mmc_imports.{$imp->module}.label", $imp->module) }}</td>
              <td class="px-3 py-2">
                @if($imp->dataset_version_id)
                  #{{ $imp->dataset_version_id }}
                  @if($imp->datasetVersion)
                    — {{ $imp->datasetVersion->version_label }}
                    @if($isCurrent)
                      <span class="ml-1 text-xs px-1.5 py-0.5 rounded bg-blue-100 text-blue-700">current</span>
                    @endif
                  @endif
                @endif
              </td>
              <td class="px-3 py-2">
                <div class="truncate max-w-[280px]" title="{{ $imp->original_name }}">{{ $imp->original_name }}</div>
              </td>
              <td class="px-3 py-2">
                @php
                  $colors = ['queued'=>'bg-gray-300','processing'=>'bg-amber-400','completed'=>'bg-emerald-500','failed'=>'bg-red-500'];
                @endphp
                <span class="px-2 py-0.5 rounded text-white {{ $colors[$imp->status] ?? 'bg-gray-400' }}">
                  {{ $imp->status }}
                </span>
              </td>
              <td class="px-3 py-2 text-red-600">
                @if($imp->error) <span title="{{ $imp->error }}">{{ \Illuminate\Support\Str::limit($imp->error, 60) }}</span> @endif
              </td>
              <td class="px-3 py-2 space-x-2">
                <a href="{{ route('admin.imports.download', $imp->id) }}" class="text-blue-700 hover:underline">Download</a>

                @if($imp->dataset_version_id)
                  @if($isCurrent)
                    <span class="text-gray-400">Current</span>
                  @else
                    <button wire:click="makeCurrent({{ $imp->dataset_version_id }})" class="text-gray-700 hover:underline">
                      Make current
                    </button>
                  @endif
                @endif

                <button wire:click="retry({{ $imp->id }})" class="text-gray-700 hover:underline">Retry</button>
              </td>
            </tr>
          @empty
            <tr><td class="px-3 py-6 text-gray-500" colspan="7">No imports yet.</td></tr>
          @endforelse
        </tbody>
      </table>

      <div class="mt-3">{{ $imports->links() }}</div>
    </div>
  </div>
</div>

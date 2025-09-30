<div class="max-w-3xl mx-auto px-4 py-6">
  <div class="flex items-center justify-between mb-4">
    <h1 class="text-2xl font-semibold">Environmental Assessment</h1>

    {{-- TEMP: Populate sample data button --}}
    <button type="button"
            wire:click="populateSample"
            class="inline-flex items-center gap-2 px-3 py-2 rounded-md border text-sm bg-white hover:bg-gray-50">
      🧪 Populate sample data
    </button>
  </div>

  {{-- Inline notice for Livewire actions --}}
  @if ($notice)
    <div class="mb-4 rounded-md bg-emerald-50 p-3 text-sm text-emerald-800">
      {{ $notice }}
    </div>
  @endif

  <form wire:submit.prevent="save" class="space-y-6">
    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
      <h2 class="text-lg font-semibold mb-3">Embodied carbon</h2>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700">A1–A3 (kgCO₂e)</label>
          <input type="number" step="any" wire:model.lazy="a1_a3" class="mt-1 w-full rounded-md border px-3 py-2">
          @error('a1_a3') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">A4–A5 (kgCO₂e)</label>
          <input type="number" step="any" wire:model.lazy="a4_a5" class="mt-1 w-full rounded-md border px-3 py-2">
          @error('a4_a5') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">C1–C4 (kgCO₂e)</label>
          <input type="number" step="any" wire:model.lazy="c1_c4" class="mt-1 w-full rounded-md border px-3 py-2">
          @error('c1_c4') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
        </div>
      </div>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
      <h2 class="text-lg font-semibold mb-3">Envelope & rating</h2>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700">U-value (W/m²·K)</label>
          <input type="number" step="any" wire:model.lazy="u_value" class="mt-1 w-full rounded-md border px-3 py-2">
          @error('u_value') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">BER rating</label>
          <select wire:model="ber_rating" class="mt-1 w-full rounded-md border px-3 py-2">
            <option value="">—</option>
            @foreach (['A1','A2','A3','B1','B2','B3','C1','C2','C3','D1','D2','E1','E2','F','G'] as $opt)
              <option value="{{ $opt }}">{{ $opt }}</option>
            @endforeach
          </select>
          @error('ber_rating') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
        </div>
      </div>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
      <h2 class="text-lg font-semibold mb-3">End-of-life</h2>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700">Reuse potential (%)</label>
          <input type="number" step="1" min="0" max="100" wire:model.lazy="reuse_potential" class="mt-1 w-full rounded-md border px-3 py-2">
          @error('reuse_potential') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Material recyclability (%)</label>
          <input type="number" step="1" min="0" max="100" wire:model.lazy="material_recyclability" class="mt-1 w-full rounded-md border px-3 py-2">
          @error('material_recyclability') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
        </div>
      </div>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
      <h2 class="text-lg font-semibold mb-3">Layer breakdown (optional)</h2>
      <p class="text-sm text-gray-500 mb-2">Select a method to attach a per-m² layer snapshot from the published dataset.</p>
      <select wire:model="selected_system_code" class="w-full md:w-80 rounded-md border px-3 py-2">
        <option value="">— No layer snapshot —</option>
        @foreach($systemCodes as $code)
          <option value="{{ $code }}">{{ $code }}</option>
        @endforeach
      </select>
    </div>

    <div class="pt-2 flex items-center gap-3">
      <button type="submit"
              class="inline-flex items-center px-4 py-2 rounded-md bg-blue-600 text-white hover:bg-blue-700">
        Save assessment
      </button>
      <button type="button"
              wire:click="populateSample"
              class="inline-flex items-center px-4 py-2 rounded-md border bg-white text-gray-700 hover:bg-gray-50">
        🧪 Populate sample data
      </button>
    </div>
  </form>
</div>

<div class="w-full">
  <h1 class="mb-2 text-2xl font-semibold">Environmental Assessment</h1>
  <p class="mb-6 text-sm text-gray-600">Assess environmental metrics for <strong>{{ $project->name }}</strong></p>

  <form wire:submit.prevent="calculate" class="space-y-6">
    <div class="rounded-xl border bg-white p-6">
      <h3 class="mb-4 text-lg font-semibold">Carbon Footprint Assessment</h3>
      <div class="grid gap-4 md:grid-cols-3">
        <div>
          <label class="block text-sm font-medium">Product Stage (A1–A3) kgCO₂e *</label>
          <input type="number" step="0.001" min="0" wire:model.defer="a1_a3" class="mt-1 w-full rounded border px-3 py-2">
          @error('a1_a3') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
        </div>
        <div>
          <label class="block text-sm font-medium">Construction Stage (A4–A5) kgCO₂e *</label>
          <input type="number" step="0.001" min="0" wire:model.defer="a4_a5" class="mt-1 w-full rounded border px-3 py-2">
          @error('a4_a5') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
        </div>
        <div>
          <label class="block text-sm font-medium">End of Life Stage (C1–C4) kgCO₂e *</label>
          <input type="number" step="0.001" min="0" wire:model.defer="c1_c4" class="mt-1 w-full rounded border px-3 py-2">
          @error('c1_c4') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
        </div>
      </div>
    </div>

    <div class="rounded-xl border bg-white p-6">
      <h3 class="mb-4 text-lg font-semibold">Energy Efficiency Information</h3>
      <div class="grid gap-4 md:grid-cols-2">
        <div>
          <label class="block text-sm font-medium">U-Value (W/m²K)</label>
          <input type="number" step="0.001" min="0" wire:model.defer="u_value" class="mt-1 w-full rounded border px-3 py-2">
          @error('u_value') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
        </div>
        <div>
          <label class="block text-sm font-medium">BER Rating</label>
          <input type="text" wire:model.defer="ber_rating" class="mt-1 w-full rounded border px-3 py-2" placeholder="e.g., A1">
          @error('ber_rating') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
        </div>
      </div>
    </div>

    <div class="rounded-xl border bg-white p-6">
      <h3 class="mb-4 text-lg font-semibold">End of Life Recyclability</h3>
      <div class="grid gap-4 md:grid-cols-3">
        <div>
          <label class="block text-sm font-medium">Reuse Potential (%)</label>
          <input type="number" step="0.1" min="0" max="100" wire:model.defer="reuse_pct" class="mt-1 w-full rounded border px-3 py-2">
          @error('reuse_pct') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
        </div>
        <div>
          <label class="block text-sm font-medium">Material Recyclability (%)</label>
          <input type="number" step="0.1" min="0" max="100" wire:model.defer="recycle_pct" class="mt-1 w-full rounded border px-3 py-2">
          @error('recycle_pct') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
        </div>
        <div>
          <label class="block text-sm font-medium">Floor Area (m²)</label>
          <input type="number" step="0.01" min="0.01" wire:model.defer="area_m2" class="mt-1 w-full rounded border px-3 py-2">
          @error('area_m2') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
        </div>
      </div>
    </div>

    <div class="pt-2">
      <button type="submit" class="rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">
        Save Environmental Assessment
      </button>
    </div>
  </form>
</div>

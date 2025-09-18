<div class="w-full">
  <h1 class="mb-2 text-2xl font-semibold">Viability Assessment</h1>
  <p class="mb-6 text-sm text-gray-600">
    Assess technical feasibility for <strong>{{ $project->name }}</strong>
  </p>

  <form wire:submit.prevent="evaluate" class="space-y-6">

    {{-- Building Information --}}
    <div class="rounded-xl border bg-white p-6">
      <h3 class="mb-4 text-lg font-semibold">Building Information</h3>
      <div class="grid gap-4 md:grid-cols-3">
        <div>
          <label class="block text-sm font-medium">Number of Stories</label>
          <input type="number" min="1" wire:model.defer="stories" class="mt-1 w-full rounded border px-3 py-2">
          @error('stories') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
        </div>
        <div>
          <label class="block text-sm font-medium">Building Height (m)</label>
          <input type="number" step="0.01" min="0" wire:model.defer="height_m" class="mt-1 w-full rounded border px-3 py-2">
          @error('height_m') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
        </div>
        <div>
          <label class="block text-sm font-medium">Number of Residential Units</label>
          <input type="number" min="0" wire:model.defer="res_units" class="mt-1 w-full rounded border px-3 py-2">
          @error('res_units') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
        </div>
      </div>

      <div class="mt-4">
        <label class="block text-sm font-medium">Residential Type <span class="text-red-600">*</span></label>
        <div class="mt-2 flex gap-6 text-sm">
          <label class="inline-flex items-center gap-2"><input type="radio" value="low" wire:model="residential_type"> <span>Low Rise (1–3)</span></label>
          <label class="inline-flex items-center gap-2"><input type="radio" value="medium" wire:model="residential_type"> <span>Medium Rise (4–9)</span></label>
          <label class="inline-flex items-center gap-2"><input type="radio" value="high" wire:model="residential_type"> <span>High Rise (10+)</span></label>
        </div>
        @error('residential_type') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
      </div>
    </div>

    {{-- Additional Room Types --}}
    <div class="rounded-xl border bg-white p-6">
      <h3 class="mb-4 text-lg font-semibold">Additional Room Types</h3>
      <div class="grid gap-4 md:grid-cols-2">
        <label class="inline-flex items-center gap-2">
          <input type="checkbox" wire:model="has_commercial"><span>Commercial Spaces Available</span>
        </label>
        <div>
          <label class="block text-sm font-medium">Number of Commercial Units</label>
          <input type="number" min="0" wire:model.defer="commercial_units" class="mt-1 w-full rounded border px-3 py-2" @disabled(!$has_commercial)>
          @error('commercial_units') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
        </div>
      </div>
    </div>

    {{-- Site Conditions --}}
    <div class="rounded-xl border bg-white p-6">
      <h3 class="mb-4 text-lg font-semibold">Site Conditions</h3>

      <label class="block text-sm font-medium">Storage Type <span class="text-red-600">*</span></label>
      <div class="mt-2 flex gap-6 text-sm">
        <label class="inline-flex items-center gap-2"><input type="radio" value="on-site" wire:model="storage_type"><span>On-site Storage</span></label>
        <label class="inline-flex items-center gap-2"><input type="radio" value="off-site" wire:model="storage_type"><span>Off-site Storage</span></label>
      </div>
      @error('storage_type') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror

      <div class="mt-4">
        <label class="block text-sm font-medium">Storage Space (m²)</label>
        <input type="number" step="0.01" min="0" wire:model.defer="storage_space_m2" class="mt-1 w-full rounded border px-3 py-2">
        @error('storage_space_m2') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
      </div>
    </div>

    {{-- Heavy Machinery --}}
    <div class="rounded-xl border bg-white p-6">
      <h3 class="mb-4 text-lg font-semibold">Heavy Machinery Availability</h3>
      <div class="grid gap-4 md:grid-cols-3">
        <label class="inline-flex items-center gap-2"><input type="checkbox" value="tower_crane" wire:model="machinery"><span>Tower Crane Available</span></label>
        <label class="inline-flex items-center gap-2"><input type="checkbox" value="telescopic_crane" wire:model="machinery"><span>Telescopic Crane Available</span></label>
        <label class="inline-flex items-center gap-2"><input type="checkbox" value="telehandler" wire:model="machinery"><span>Telehandler Available</span></label>
      </div>

      @if(in_array('tower_crane', $machinery ?? [], true))
        <div class="mt-4">
          <label class="block text-sm font-medium">Tower Crane Capacity (tonnes)</label>
          <input type="number" step="0.1" min="0" wire:model.defer="tower_crane_capacity_t" class="mt-1 w-full rounded border px-3 py-2">
          @error('tower_crane_capacity_t') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
        </div>
      @endif
    </div>

    <div class="pt-2">
      <button type="submit" class="rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">
        Save Viability Assessment
      </button>
    </div>
  </form>
</div>

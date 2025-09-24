<div class="max-w-5xl mx-auto p-6 space-y-8">
  <h1 class="text-2xl font-semibold">Viability Assessment</h1>

  <form wire:submit.prevent="evaluate" class="space-y-8">

    {{-- Building Information --}}
    <section class="rounded-lg border bg-white p-5 shadow-sm space-y-4">
      <header>
        <h2 class="text-lg font-medium">Building Information</h2>
        <p class="text-sm text-gray-500">Basic building specifications and dimensions</p>
      </header>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        {{-- Number of Stories --}}
        <div>
          <label class="block text-sm font-medium">
            Number of Stories
            <span class="text-gray-400 cursor-help"
                  title="Total number of floors in the building including ground floor">ⓘ</span>
          </label>
          <input
            type="number"
            step="1"
            min="0"
            inputmode="numeric"
            wire:model.live.debounce.0ms="stories"
            wire:input.debounce.0ms="syncHeightFromStories"
            class="mt-1 w-full rounded border px-3 py-2"
            placeholder="e.g. 3">
          @error('stories') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Building Height (m) --}}
        <div>
          <label class="block text-sm font-medium">
            Building Height (m)
            <span class="text-gray-400 cursor-help"
                  title="Total height of the building in meters. Auto-calculated as 3m per story.">ⓘ</span>
          </label>
          <input
            type="number"
            step="1"
            min="0"
            inputmode="numeric"
            wire:model.live="height_m"
            class="mt-1 w-full rounded border px-3 py-2"
            placeholder="Auto: 3 × stories">
          <p class="mt-1 text-xs text-gray-500">Auto: 3m × stories (changing stories will overwrite manual edits)</p>
          @error('height_m') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Residential Type (radio) --}}
        <div class="sm:col-span-2">
          <label class="block text-sm font-medium">
            Residential Type
            <span class="text-gray-400 cursor-help"
                  title="Low-rise: 1-3 stories, Medium-rise: 4-9 stories, High-rise: 10+ stories">ⓘ</span>
          </label>
          <div class="mt-2 grid grid-cols-1 sm:grid-cols-3 gap-2">
            <label class="flex items-center gap-2 rounded border px-3 py-2">
              <input type="radio" name="residential_type" value="low" wire:model.live="residential_type">
              <span class="text-sm">Low Rise (1–3 stories)</span>
            </label>
            <label class="flex items-center gap-2 rounded border px-3 py-2">
              <input type="radio" name="residential_type" value="medium" wire:model.live="residential_type">
              <span class="text-sm">Medium Rise (4–9 stories)</span>
            </label>
            <label class="flex items-center gap-2 rounded border px-3 py-2">
              <input type="radio" name="residential_type" value="high" wire:model.live="residential_type">
              <span class="text-sm">High Rise (10+ stories)</span>
            </label>
          </div>
          @error('residential_type') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Number of Residential Units --}}
        <div>
          <label class="block text-sm font-medium">
            Number of Residential Units
            <span class="text-gray-400 cursor-help"
                  title="Total number of individual housing units (apartments/flats) in the building">ⓘ</span>
          </label>
          <input
            type="number"
            step="1"
            min="0"
            inputmode="numeric"
            wire:model.live="res_units"
            class="mt-1 w-full rounded border px-3 py-2"
            placeholder="e.g. 24">
          @error('res_units') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
      </div>
    </section>

    {{-- Additional Room Types --}}
    <section class="rounded-lg border bg-white p-5 shadow-sm space-y-4">
      <header>
        <h2 class="text-lg font-medium">Additional Room Types</h2>
        <p class="text-sm text-gray-500">Commercial or non-residential spaces within the building</p>
      </header>

      <div class="space-y-3">
        <label class="inline-flex items-center gap-2">
          <input type="checkbox" wire:model.live="has_commercial" class="h-4 w-4">
          <span class="text-sm">Commercial Spaces Available</span>
        </label>

        @if($has_commercial)
          <div>
            <label class="block text-sm font-medium">
              Number of Commercial Units
              <span class="text-gray-400 cursor-help"
                    title="Number of commercial units such as shops, offices, or retail spaces">ⓘ</span>
            </label>
            <input
              type="number"
              step="1"
              min="0"
              inputmode="numeric"
              wire:model.live="commercial_units"
              class="mt-1 w-full rounded border px-3 py-2"
              placeholder="e.g. 2">
            @error('commercial_units') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
          </div>
        @endif
      </div>
    </section>

    {{-- Site Conditions --}}
    <section class="rounded-lg border bg-white p-5 shadow-sm space-y-4">
      <header>
        <h2 class="text-lg font-medium">Site Conditions</h2>
        <p class="text-sm text-gray-500">Storage space and logistics considerations</p>
      </header>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        {{-- Storage Type (radio) --}}
        <div>
          <label class="block text-sm font-medium">Storage Type</label>
          <div class="mt-2 space-y-2">
            <label class="flex items-center gap-2">
              <input type="radio" name="storage_type" value="on-site" wire:model.live="storage_type">
              <span class="text-sm">On-site Storage</span>
            </label>
            <label class="flex items-center gap-2">
              <input type="radio" name="storage_type" value="off-site" wire:model.live="storage_type">
              <span class="text-sm">Off-site Storage</span>
            </label>
          </div>
          @error('storage_type') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Storage Space --}}
        <div>
          <label class="block text-sm font-medium">
            Storage Space (sq m)
            <span class="text-gray-400 cursor-help"
                  title="Available storage space in square meters for materials and equipment">ⓘ</span>
          </label>
          <input
            type="number"
            step="1"
            min="0"
            inputmode="numeric"
            wire:model.live="storage_space_m2"
            class="mt-1 w-full rounded border px-3 py-2"
            placeholder="e.g. 20">
          @error('storage_space_m2') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
      </div>
    </section>

    {{-- Heavy Machinery --}}
    <section class="rounded-lg border bg-white p-5 shadow-sm space-y-4">
      <header>
        <h2 class="text-lg font-medium">Heavy Machinery Availability</h2>
        <p class="text-sm text-gray-500">Available equipment and lifting capacity</p>
      </header>

      <div class="space-y-6">
        {{-- Tower Crane --}}
        <div>
          <label class="flex items-center gap-2 text-sm font-medium">
            <input type="checkbox" value="tower_crane" wire:model.live="machinery" class="h-4 w-4">
            <span>Tower Crane Available</span>
          </label>

          @if(in_array('tower_crane', $machinery ?? []))
            <div class="mt-2">
              <label class="text-sm font-medium">Capacity (tonnes)</label>
              <input
                type="number"
                min="0"
                step="1"
                inputmode="numeric"
                wire:model.live="tower_crane_capacity_t"
                class="mt-1 w-full rounded border px-3 py-2"
                placeholder="Enter capacity in tonnes">
              @error('tower_crane_capacity_t') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
          @endif
        </div>

        {{-- Telescopic Crane --}}
        <div>
          <label class="flex items-center gap-2 text-sm font-medium">
            <input type="checkbox" value="telescopic_crane" wire:model.live="machinery" class="h-4 w-4">
            <span>Telescopic Crane Available</span>
          </label>

          @if(in_array('telescopic_crane', $machinery ?? []))
            <div class="mt-2">
              <label class="text-sm font-medium">Capacity (tonnes)</label>
              <input
                type="number"
                min="0"
                step="1"
                inputmode="numeric"
                wire:model.live="telescopic_crane_capacity_t"
                class="mt-1 w-full rounded border px-3 py-2"
                placeholder="Enter capacity in tonnes">
              @error('telescopic_crane_capacity_t') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
          @endif
        </div>

        {{-- Telehandler --}}
        <div>
          <label class="flex items-center gap-2 text-sm font-medium">
            <input type="checkbox" value="telehandler" wire:model.live="machinery" class="h-4 w-4">
            <span>Telehandler Available</span>
          </label>

          @if(in_array('telehandler', $machinery ?? []))
            <div class="mt-2">
              <label class="text-sm font-medium">Capacity (tonnes)</label>
              <input
                type="number"
                min="0"
                step="1"
                inputmode="numeric"
                wire:model.live="telehandler_capacity_t"
                class="mt-1 w-full rounded border px-3 py-2"
                placeholder="Enter capacity in tonnes">
              @error('telehandler_capacity_t') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
          @endif
        </div>
      </div>
    </section>

    <div class="flex items-center gap-3">
      <button type="submit"
              class="inline-flex items-center rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">
        Compute Viability
      </button>
      <a href="{{ route('projects.index') }}" class="text-sm text-gray-600 hover:underline">Cancel</a>
    </div>

  </form>
</div>

<div class="max-w-4xl mx-auto">
  <h1 class="text-2xl font-semibold mb-6">Viability Assessment</h1>

  @error('dataset')
    <div class="mb-4 p-3 rounded bg-red-100 text-red-800">{{ $message }}</div>
  @enderror

  <form wire:submit.prevent="save" class="space-y-6 bg-white p-6 rounded-xl border">
    {{-- Residential Type (single-select) --}}
    <div>
      <label class="block text-sm font-medium mb-1">Residential Type</label>
      <div class="flex gap-3 flex-wrap">
        @foreach (['Low Rise','Medium Rise','High Rise'] as $label)
          <label class="inline-flex items-center gap-2">
            <input type="radio"
                   name="residentialType"
                   wire:model="residentialType"
                   value="{{ $label }}"
                   class="rounded border-gray-300">
            <span class="text-sm">{{ $label }}</span>
          </label>
        @endforeach
      </div>
      @error('residentialType') <div class="text-red-600 text-sm mt-1">{{ $message }}</div> @enderror
    </div>

    {{-- Storage Location (multi-select) --}}
    <div>
      <label class="block text-sm font-medium mb-1">Storage Location (select one or both)</label>
      <div class="flex gap-4 flex-wrap">
        @foreach (['On Site Storage','Off Site Storage'] as $label)
          <label class="inline-flex items-center gap-2">
            <input type="checkbox"
                   name="storageLocations[]"
                   wire:model="storageLocations"
                   value="{{ $label }}"
                   class="rounded border-gray-300">
            <span class="text-sm">{{ $label }}</span>
          </label>
        @endforeach
      </div>
      @error('storageLocations') <div class="text-red-600 text-sm mt-1">{{ $message }}</div> @enderror
    </div>

    {{-- Crane Type (multi-select) --}}
    <div>
      <label class="block text-sm font-medium mb-1">Crane Type (select one or more)</label>
      <div class="flex gap-4 flex-wrap">
        @foreach (['Tower Crane','Telescopic Crane','Telehandler Crane'] as $label)
          <label class="inline-flex items-center gap-2">
            <input type="checkbox"
                   name="craneTypes[]"
                   wire:model="craneTypes"
                   value="{{ $label }}"
                   class="rounded border-gray-300">
            <span class="text-sm">{{ $label }}</span>
          </label>
        @endforeach
      </div>
      @error('craneTypes') <div class="text-red-600 text-sm mt-1">{{ $message }}</div> @enderror
    </div>

    {{-- Truck Type (multi-select) --}}
    <div>
      <label class="block text-sm font-medium mb-1">Truck Type (select one or both)</label>
      <div class="flex gap-4 flex-wrap">
        @foreach (['Flatbed Truck','Flatbed A Frame'] as $label)
          <label class="inline-flex items-center gap-2">
            <input type="checkbox"
                   name="truckTypes[]"
                   wire:model="truckTypes"
                   value="{{ $label }}"
                   class="rounded border-gray-300">
            <span class="text-sm">{{ $label }}</span>
          </label>
        @endforeach
      </div>
      @error('truckTypes') <div class="text-red-600 text-sm mt-1">{{ $message }}</div> @enderror
    </div>

    {{-- Banded radios --}}
    <div class="grid sm:grid-cols-3 gap-4">
      <div>
        <label class="block text-sm font-medium mb-1">Max Panel Height</label>
        <div class="flex gap-3">
          <label class="inline-flex items-center gap-2">
            <input type="radio"
                   name="panelHeightBand"
                   wire:model="panelHeightBand"
                   value="<=3.0m"
                   class="rounded border-gray-300">
            <span class="text-sm">&le; 3.0 m</span>
          </label>
          <label class="inline-flex items-center gap-2">
            <input type="radio"
                   name="panelHeightBand"
                   wire:model="panelHeightBand"
                   value=">3.0m"
                   class="rounded border-gray-300">
            <span class="text-sm">&gt; 3.0 m</span>
          </label>
        </div>
        @error('panelHeightBand') <div class="text-red-600 text-sm mt-1">{{ $message }}</div> @enderror
      </div>

      <div>
        <label class="block text-sm font-medium mb-1">Max Frame Length</label>
        <div class="flex gap-3">
          <label class="inline-flex items-center gap-2">
            <input type="radio"
                   name="frameLengthBand"
                   wire:model="frameLengthBand"
                   value="<=12.0m"
                   class="rounded border-gray-300">
            <span class="text-sm">&le; 12.0 m</span>
          </label>
          <label class="inline-flex items-center gap-2">
            <input type="radio"
                   name="frameLengthBand"
                   wire:model="frameLengthBand"
                   value=">12.0m"
                   class="rounded border-gray-300">
            <span class="text-sm">&gt; 12.0 m</span>
          </label>
        </div>
        @error('frameLengthBand') <div class="text-red-600 text-sm mt-1">{{ $message }}</div> @enderror
      </div>

      <div>
        <label class="block text-sm font-medium mb-1">Max Frame Width</label>
        <div class="flex gap-3">
          <label class="inline-flex items-center gap-2">
            <input type="radio"
                   name="frameWidthBand"
                   wire:model="frameWidthBand"
                   value="<=3.2m"
                   class="rounded border-gray-300">
            <span class="text-sm">&le; 3.2 m</span>
          </label>
          <label class="inline-flex items-center gap-2">
            <input type="radio"
                   name="frameWidthBand"
                   wire:model="frameWidthBand"
                   value=">3.2m"
                   class="rounded border-gray-300">
            <span class="text-sm">&gt; 3.2 m</span>
          </label>
        </div>
        @error('frameWidthBand') <div class="text-red-600 text-sm mt-1">{{ $message }}</div> @enderror
      </div>
    </div>

    <div class="flex items-center justify-end gap-3">
      <a href="{{ route('projects.index') }}" class="text-sm text-gray-600 hover:text-gray-800">Cancel</a>
      <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm rounded hover:bg-blue-700">
        Run Assessment
      </button>
    </div>
  </form>
</div>

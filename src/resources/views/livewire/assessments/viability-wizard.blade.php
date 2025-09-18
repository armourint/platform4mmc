<div class="p-6 max-w-3xl">
    <div class="rounded-lg border bg-white p-6">
        <h3 class="text-lg font-semibold mb-4">Inputs</h3>

        <form wire:submit.prevent="save" class="grid gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">Number of Storeys</label>
                <input type="number" min="1" max="50" wire:model.defer="storey_count"
                       class="w-full rounded-md border-gray-300">
                @error('storey_count') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Site Constraints</label>
                <textarea wire:model.defer="site_constraints" rows="3"
                          class="w-full rounded-md border-gray-300"></textarea>
                @error('site_constraints') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
            </div>

            <div class="flex items-center gap-2">
                <input id="nzeb" type="checkbox" wire:model.defer="target_nzeb" class="rounded border-gray-300">
                <label for="nzeb" class="text-sm">Target nZEB performance</label>
            </div>
            @error('target_nzeb') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror

            <div class="mt-4 flex gap-2">
                <button class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                    Save &amp; View Results
                </button>
                <a href="{{ route('assessments.hub', $project) }}" class="px-4 py-2 rounded-md border">
                    Cancel
                </a>
            </div>
        </form>
    </div>

    <div class="mt-6">
        <a href="{{ route('assessments.hub', $project) }}" class="text-gray-600 hover:text-gray-800 hover:underline">
            ← Back to Assessment Hub
        </a>
    </div>
</div>

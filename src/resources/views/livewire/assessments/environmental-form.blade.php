<div class="p-6 max-w-3xl">
    <div class="rounded-lg border bg-white p-6">
        <h3 class="text-lg font-semibold mb-4">Inputs</h3>

        <form wire:submit.prevent="save" class="grid gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">Embodied Carbon Target (kgCO₂e/m²)</label>
                <input type="number" step="0.01" min="0" wire:model.defer="embodied_carbon_target"
                       class="w-full rounded-md border-gray-300">
                @error('embodied_carbon_target') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">LCA Scope</label>
                <input type="text" wire:model.defer="lca_scope"
                       class="w-full rounded-md border-gray-300" placeholder="e.g., A1–A3, A–C">
                @error('lca_scope') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
            </div>

            <div class="mt-4 flex gap-2">
                <button class="px-4 py-2 bg-emerald-600 text-white rounded-md hover:bg-emerald-700">
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

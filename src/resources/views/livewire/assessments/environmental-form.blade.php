<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold">Environmental — {{ $project->name }}</h2>
    </x-slot>

    <div class="p-6 max-w-4xl">
        <form wire:submit.prevent="calculate" class="grid gap-6 md:grid-cols-2">
            <div class="rounded-lg border p-6 bg-white">
                <h3 class="font-medium mb-4">Inputs</h3>

                <label class="block mb-3">
                    <span class="block text-sm font-medium mb-1">A1–A3 (kgCO₂e)</span>
                    <input type="number" step="0.001" wire:model="a1_a3" class="w-full rounded-md border-gray-300">
                    @error('a1_a3') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
                </label>

                <label class="block mb-3">
                    <span class="block text-sm font-medium mb-1">A4–A5 (kgCO₂e)</span>
                    <input type="number" step="0.001" wire:model="a4_a5" class="w-full rounded-md border-gray-300">
                    @error('a4_a5') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
                </label>

                <label class="block mb-3">
                    <span class="block text-sm font-medium mb-1">C1–C4 (kgCO₂e)</span>
                    <input type="number" step="0.001" wire:model="c1_c4" class="w-full rounded-md border-gray-300">
                    @error('c1_c4') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
                </label>

                <div class="grid grid-cols-2 gap-4">
                    <label class="block">
                        <span class="block text-sm font-medium mb-1">Reuse %</span>
                        <input type="number" step="1" wire:model="reuse_pct" class="w-full rounded-md border-gray-300">
                        @error('reuse_pct') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
                    </label>
                    <label class="block">
                        <span class="block text-sm font-medium mb-1">Recycle %</span>
                        <input type="number" step="1" wire:model="recycle_pct" class="w-full rounded-md border-gray-300">
                        @error('recycle_pct') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
                    </label>
                </div>

                <label class="block mt-3">
                    <span class="block text-sm font-medium mb-1">Floor Area (m²)</span>
                    <input type="number" step="0.01" wire:model="area_m2" class="w-full rounded-md border-gray-300">
                    @error('area_m2') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
                </label>

                <button class="mt-6 px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                    Calculate
                </button>
            </div>

            <div class="rounded-lg border p-6 bg-white">
                <h3 class="font-medium mb-4">What you'll get</h3>
                <ul class="list-disc pl-5 text-sm text-gray-600 space-y-2">
                    <li>Total embodied carbon (kgCO₂e)</li>
                    <li>kgCO₂e per m² (if floor area provided)</li>
                    <li>Breakdown by A1–A3 / A4–A5 / C1–C4</li>
                </ul>
            </div>
        </form>
    </div>
</x-app-layout>

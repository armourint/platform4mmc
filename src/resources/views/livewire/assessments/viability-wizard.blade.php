<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold">Viability — {{ $project->name }}</h2>
    </x-slot>

    <div class="p-6 max-w-4xl">
        <form wire:submit.prevent="evaluate" class="grid gap-6 md:grid-cols-2">
            <div class="rounded-lg border p-6 bg-white">
                <h3 class="font-medium mb-4">Inputs</h3>

                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1">Residential Type</label>
                    <select wire:model="residential_type" class="w-full rounded-md border-gray-300">
                        <option value="low">Low-rise</option>
                        <option value="medium">Medium-rise</option>
                        <option value="high">High-rise</option>
                    </select>
                    @error('residential_type') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1">Storage Type</label>
                    <select wire:model="storage_type" class="w-full rounded-md border-gray-300">
                        <option value="on-site">On-site</option>
                        <option value="off-site">Off-site</option>
                    </select>
                    @error('storage_type') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1">Machinery</label>
                    <div class="space-y-1">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" value="tower_crane" wire:model="machinery"> Tower crane
                        </label>
                        <label class="flex items-center gap-2">
                            <input type="checkbox" value="telehandler" wire:model="machinery"> Telehandler
                        </label>
                        <label class="flex items-center gap-2">
                            <input type="checkbox" value="flatbed_truck" wire:model="machinery"> Flatbed truck
                        </label>
                    </div>
                    @error('machinery') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
                </div>

                <button class="mt-2 px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                    Evaluate
                </button>
            </div>

            <div class="rounded-lg border p-6 bg-white">
                <h3 class="font-medium mb-4">What you'll get</h3>
                <ul class="list-disc pl-5 text-sm text-gray-600 space-y-2">
                    <li>Overall viability score (0–4)</li>
                    <li>List of viable MMC systems</li>
                    <li>Non-viable systems with reasons</li>
                </ul>
            </div>
        </form>
    </div>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold">Environmental Assessment</h2>
    </x-slot>

    <div class="p-6 max-w-5xl">
        <div class="grid gap-6 md:grid-cols-2">
            <div class="rounded-lg border p-6 bg-white">
                <h3 class="font-medium mb-4">Inputs (placeholder)</h3>
                <div class="grid gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">A1–A3 (kgCO₂e)</label>
                        <input type="number" class="w-full rounded-md border-gray-300" placeholder="0">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">A4–A5 (kgCO₂e)</label>
                        <input type="number" class="w-full rounded-md border-gray-300" placeholder="0">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">C1–C4 (kgCO₂e)</label>
                        <input type="number" class="w-full rounded-md border-gray-300" placeholder="0">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Reuse %</label>
                            <input type="number" class="w-full rounded-md border-gray-300" placeholder="0">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Recycle %</label>
                            <input type="number" class="w-full rounded-md border-gray-300" placeholder="0">
                        </div>
                    </div>
                </div>

                <button class="mt-6 px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                    Calculate (TODO)
                </button>
            </div>

            <div class="rounded-lg border p-6 bg-white">
                <h3 class="font-medium mb-4">Results (placeholder)</h3>
                <div class="grid gap-4">
                    <div class="rounded-md border p-4">Total Embodied Carbon: <span class="font-semibold">—</span></div>
                    <div class="rounded-md border p-4">Per m²: <span class="font-semibold">—</span></div>
                    <div class="rounded-md border p-4">KPI Tiles / Charts: <span class="font-semibold">TODO</span></div>
                </div>
            </div>
        </div>

        <div class="mt-6 rounded-lg border border-dashed p-6 text-gray-500">
            TODO UI — link outputs to Knowledge Bank resources
        </div>
    </div>
</x-app-layout>

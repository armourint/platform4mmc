<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold">Viability Assessment</h2>
    </x-slot>

    <div class="p-6 max-w-5xl">
        <div class="mb-4 text-sm text-gray-600">
            Step-by-step wizard coming next. For now, a placeholder:
        </div>

        <div class="grid gap-6 md:grid-cols-2">
            <div class="rounded-lg border p-6 bg-white">
                <h3 class="font-medium mb-4">Inputs (placeholder)</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Residential Type</label>
                        <select class="w-full rounded-md border-gray-300">
                            <option>Low-rise</option>
                            <option>Medium-rise</option>
                            <option>High-rise</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Storage Type</label>
                        <select class="w-full rounded-md border-gray-300">
                            <option>On-site</option>
                            <option>Off-site</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Machinery</label>
                        <div class="space-y-1">
                            <label class="flex items-center gap-2"><input type="checkbox"> Tower Crane</label>
                            <label class="flex items-center gap-2"><input type="checkbox"> Telehandler</label>
                            <label class="flex items-center gap-2"><input type="checkbox"> Flatbed Truck</label>
                        </div>
                    </div>
                </div>

                <button class="mt-6 px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                    Evaluate (TODO)
                </button>
            </div>

            <div class="rounded-lg border p-6 bg-white">
                <h3 class="font-medium mb-4">Results (placeholder)</h3>
                <div class="rounded-lg border border-dashed p-8 text-center text-gray-500">
                    TODO UI — viable/non-viable methods with reasons
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold">Create Project</h2>
    </x-slot>

    <div class="p-6 max-w-3xl">
        <div class="rounded-lg border p-6 bg-white">
            <form>
                <div class="grid gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Project Name</label>
                        <input class="w-full rounded-md border-gray-300" placeholder="e.g. Ballymore Homes Phase 1">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Client</label>
                        <input class="w-full rounded-md border-gray-300" placeholder="Client name">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Location</label>
                        <input class="w-full rounded-md border-gray-300" placeholder="Town / County">
                    </div>
                </div>

                <div class="mt-6 flex gap-2">
                    <button type="button" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                        Save (TODO)
                    </button>
                    <a href="{{ route('projects.index') }}" class="px-4 py-2 rounded-md border">Cancel</a>
                </div>
            </form>
        </div>

        <div class="mt-6 rounded-lg border border-dashed p-6 text-gray-500">
            TODO UI — hook this form to store project
        </div>
    </div>
</x-app-layout>

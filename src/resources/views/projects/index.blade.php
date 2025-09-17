<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold">Projects</h2>
    </x-slot>

    <div class="p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium">Your Projects</h3>
            <a href="{{ route('projects.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                Create Project
            </a>
        </div>

        <div class="rounded-lg border border-dashed p-8 text-center text-gray-500">
            TODO UI — list projects here (table or cards)
        </div>
    </div>
</x-app-layout>

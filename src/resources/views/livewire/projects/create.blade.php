<div class="p-6 max-w-3xl">
    <div class="rounded-lg border p-6 bg-white">
        <form wire:submit.prevent="save" class="grid gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">Project Name</label>
                <input wire:model.defer="name" class="w-full rounded-md border-gray-300">
                @error('name') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Client</label>
                <input wire:model.defer="client" class="w-full rounded-md border-gray-300">
                @error('client') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Location</label>
                <input wire:model.defer="location" class="w-full rounded-md border-gray-300">
                @error('location') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
            </div>

            <div class="mt-4 flex gap-2">
                <button class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">Save</button>
                <a href="{{ route('projects.index') }}" class="px-4 py-2 rounded-md border">Cancel</a>
            </div>
        </form>
    </div>
</div>

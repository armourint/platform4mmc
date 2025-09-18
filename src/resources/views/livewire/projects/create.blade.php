<div class="mx-auto max-w-3xl">
  <h1 class="mb-6 text-2xl font-semibold">Project Information</h1>

  <form wire:submit.prevent="save" class="space-y-4 rounded-xl border bg-white p-6">
    <div>
      <label class="block text-sm font-medium">Project Name <span class="text-red-600">*</span></label>
      <input type="text" wire:model.defer="name" class="mt-1 w-full rounded border px-3 py-2">
      @error('name') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
    </div>

    <div>
      <label class="block text-sm font-medium">Location</label>
      <input type="text" wire:model.defer="location" class="mt-1 w-full rounded border px-3 py-2">
      @error('location') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
    </div>

    <div>
      <label class="block text-sm font-medium">Client</label>
      <input type="text" wire:model.defer="client" class="mt-1 w-full rounded border px-3 py-2">
      @error('client') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
    </div>

    <div>
      <label class="block text-sm font-medium">Your Role in the Project</label>
      <input type="text" wire:model.defer="user_role" class="mt-1 w-full rounded border px-3 py-2" placeholder="e.g., Project Manager, Architect">
      @error('user_role') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
    </div>

    <div class="pt-2">
      <button type="submit" class="rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">Save Project</button>
    </div>
  </form>
</div>

<div class="max-w-5xl mx-auto p-6 space-y-8">
    <h1 class="text-2xl font-semibold">Environmental Assessment</h1>
    <p class="text-sm text-gray-600">Assess environmental metrics for <span class="font-medium">{{ $project->name }}</span></p>

    <form wire:submit.prevent="save" class="space-y-8">
        {{-- Carbon Footprint --}}
        <section class="rounded-lg border bg-white p-5 shadow-sm space-y-4">
            <header>
                <h2 class="text-lg font-medium flex items-center gap-2">
                    <span class="text-emerald-600">🌿</span>
                    Carbon Footprint Assessment
                </h2>
                <p class="text-sm text-gray-500">Track carbon emissions across different lifecycle stages</p>
            </header>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium">
                        Product Stage (A1–A3) kgCO₂e
                    </label>
                    <input type="number" step="0.1" min="0" inputmode="decimal"
                           wire:model.live="a1_a3"
                           class="mt-1 w-full rounded border px-3 py-2"
                           placeholder="e.g. 200">
                    @error('a1_a3') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    <p class="mt-1 text-xs text-gray-500">Raw material supply, transport, and manufacturing</p>
                </div>

                <div>
                    <label class="block text-sm font-medium">
                        Construction Stage (A4–A5) kgCO₂e
                    </label>
                    <input type="number" step="0.1" min="0" inputmode="decimal"
                           wire:model.live="a4_a5"
                           class="mt-1 w-full rounded border px-3 py-2"
                           placeholder="e.g. 150">
                    @error('a4_a5') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    <p class="mt-1 text-xs text-gray-500">Transport to site and construction/installation processes</p>
                </div>

                <div>
                    <label class="block text-sm font-medium">
                        End of Life Stage (C1–C4) kgCO₂e
                    </label>
                    <input type="number" step="0.1" min="0" inputmode="decimal"
                           wire:model.live="c1_c4"
                           class="mt-1 w-full rounded border px-3 py-2"
                           placeholder="e.g. 80">
                    @error('c1_c4') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    <p class="mt-1 text-xs text-gray-500">Deconstruction, transport, waste processing, and disposal</p>
                </div>
            </div>
        </section>

        {{-- Energy Efficiency --}}
        <section class="rounded-lg border bg-white p-5 shadow-sm space-y-4">
            <header>
                <h2 class="text-lg font-medium flex items-center gap-2">
                    <span class="text-amber-500">⚡</span>
                    Energy Efficiency Information
                </h2>
                <p class="text-sm text-gray-500">Building energy performance metrics</p>
            </header>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium">U-Value (W/m²K)</label>
                    <input type="number" step="0.01" min="0" inputmode="decimal"
                           wire:model.live="u_value"
                           class="mt-1 w-full rounded border px-3 py-2"
                           placeholder="e.g. 0.15">
                    @error('u_value') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    <p class="mt-1 text-xs text-gray-500">Thermal transmittance – lower values indicate better insulation</p>
                </div>

                <div>
                    <label class="block text-sm font-medium">BER Rating</label>
                    <select wire:model.live="ber_rating"
                            class="mt-1 w-full rounded border px-3 py-2 bg-white">
                        <option value="" disabled selected>— Select —</option>
                        @foreach ($berOptions as $opt)
                            <option value="{{ $opt }}">{{ $opt }}</option>
                        @endforeach
                    </select>
                    @error('ber_rating') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    <p class="mt-1 text-xs text-gray-500">Building Energy Rating from A1 (most efficient) to G (least efficient)</p>
                </div>
            </div>
        </section>

        {{-- End of Life --}}
        <section class="rounded-lg border bg-white p-5 shadow-sm space-y-4">
            <header>
                <h2 class="text-lg font-medium flex items-center gap-2">
                    <span class="text-blue-600">♻️</span>
                    End of Life Recyclability
                </h2>
                <p class="text-sm text-gray-500">Material reuse and recyclability potential</p>
            </header>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium">Reuse Potential (%)</label>
                    <input type="number" step="0.1" min="0" max="100" inputmode="decimal"
                           wire:model.live="reuse_potential"
                           class="mt-1 w-full rounded border px-3 py-2"
                           placeholder="e.g. 30">
                    @error('reuse_potential') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    <p class="mt-1 text-xs text-gray-500">Percentage of materials that can be directly reused</p>
                </div>

                <div>
                    <label class="block text-sm font-medium">Material Recyclability (%)</label>
                    <input type="number" step="0.1" min="0" max="100" inputmode="decimal"
                           wire:model.live="material_recyclability"
                           class="mt-1 w-full rounded border px-3 py-2"
                           placeholder="e.g. 60">
                    @error('material_recyclability') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    <p class="mt-1 text-xs text-gray-500">Percentage of materials that can be recycled into new products</p>
                </div>
            </div>
        </section>

        <div class="flex items-center gap-3">
            <button type="submit"
                    class="inline-flex items-center rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">
                Save Environmental Assessment
            </button>

            @if ($saved_at)
                <span class="text-sm text-green-700">Saved {{ \Carbon\Carbon::parse($saved_at)->diffForHumans() }}.</span>
            @endif
        </div>
    </form>

    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('env-saved', () => {
                // Tiny visual ping without bringing in a JS lib.
                const el = document.createElement('div');
                el.textContent = 'Saved';
                el.className = 'fixed bottom-4 right-4 bg-emerald-600 text-white text-sm px-3 py-2 rounded shadow';
                document.body.appendChild(el);
                setTimeout(() => el.remove(), 1500);
            });
        });
    </script>
</div>

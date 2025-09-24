<div class="p-4 space-y-4">
  

  <div class="flex items-center justify-between gap-4">
    <h1 class="text-xl font-semibold">Manufacturers Map</h1>

    <div class="flex flex-wrap items-center gap-2">
      <label class="inline-flex items-center gap-2 text-sm">
        <input type="checkbox" class="h-4 w-4" wire:model.live="onlyActive">
        <span>Only active</span>
      </label>

      <input class="border rounded px-2 py-1 text-sm" placeholder="County code…"
             wire:model.live.debounce.300ms="county" style="min-width: 10rem;">

      <input class="border rounded px-2 py-1 text-sm" placeholder="Category…"
             wire:model.live.debounce.300ms="category" style="min-width: 12rem;">
    </div>
  </div>

  <div class="rounded border overflow-hidden">
    <div id="manu-map" class="h-[68vh] min-h-[420px]" wire:ignore></div>
  </div>

  <p class="text-xs text-gray-500">Tip: filter by county code or by a product category keyword.</p>
</div>

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="anonymous">
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin="anonymous"></script>
<script>
  (() => {
    let map, layer;

    function ensureMap() {
      if (map) return;
      const el = document.getElementById('manu-map');
      map = L.map(el, { scrollWheelZoom: true }).setView([53.4, -8.2], 6);
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);
      layer = L.layerGroup().addTo(map);
    }

    function draw(points) {
      ensureMap();
      layer.clearLayers();

      if (!Array.isArray(points) || points.length === 0) return;

      const bounds = L.latLngBounds();
      points.forEach(p => {
        if (typeof p.lat !== 'number' || typeof p.lng !== 'number') return;
        const popup = `
          <div class="space-y-1 text-sm">
            <div class="font-semibold">${p.name ?? ''}</div>
            <div class="text-gray-600">${(p.product_category ?? '')}${p.product_subcategory ? ' — ' + p.product_subcategory : ''}</div>
            ${p.mmc_method ? `<div class="text-gray-600">Method: ${p.mmc_method}</div>` : ''}
            ${p.address ? `<div>${p.address}</div>` : ''}
            <div class="text-gray-600">${p.county_name ?? p.county_code ?? ''}${p.country ? ' · ' + p.country : ''}</div>
            ${p.website ? `<div><a href="${p.website}" target="_blank" rel="noopener">Website</a></div>` : ''}
            ${p.phone ? `<div>${p.phone}</div>` : ''}
            ${p.email ? `<div>${p.email}</div>` : ''}
          </div>`;
        L.marker([p.lat, p.lng]).addTo(layer).bindPopup(popup);
        bounds.extend([p.lat, p.lng]);
      });

      if (bounds.isValid()) map.fitBounds(bounds, { padding: [24, 24] });
    }

    // Initial render with server-provided $points
    document.addEventListener('DOMContentLoaded', () => draw(@json($points)));

    // Livewire updates after filters change
    document.addEventListener('livewire:init', () => {
      Livewire.on('manufacturers-map:update', (points) => draw(points));
    });

    // If Livewire navigates, ensure map exists and redraw with last known server data
    window.addEventListener('livewire:navigated', () => {
      ensureMap();
      draw(@json($points));
    });
  })();
</script>
@endpush

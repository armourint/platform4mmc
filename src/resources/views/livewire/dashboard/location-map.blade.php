<x-card>
  <x-slot:header>Location Map</x-slot:header>

  {{-- Livewire-updated JSON payload --}}
  <div id="map-locs" class="hidden">@json($locations)</div>

  {{-- Keep DOM stable for Leaflet --}}
  <div id="map" class="h-[460px] rounded-xl" wire:ignore></div>
</x-card>

@push('styles')
  <link rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
        crossorigin="anonymous">
  <style>
    /* Safety: ensure no global responsive-img rule touches Leaflet assets */
    .leaflet-container img,
    .leaflet-container .leaflet-marker-icon,
    .leaflet-container .leaflet-marker-shadow {
      max-width: none !important;
    }
  </style>
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
        crossorigin="anonymous"></script>
<script>
document.addEventListener('livewire:init', () => {
  let map, layerGroup = L.layerGroup();

  const ensureMap = () => {
    const node = document.getElementById('map');
    if (!node) return;

    if (!map) {
      map = L.map(node, { zoomControl: false }).setView([51.897, -8.47], 12);
      L.control.zoom({ position: 'bottomright' }).addTo(map);

      // Clean Carto basemap
      L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; OpenStreetMap &copy; CARTO',
        maxZoom: 19
      }).addTo(map);

      layerGroup.addTo(map);
    }
  };

  const readLocs = () => {
    try { return JSON.parse(document.getElementById('map-locs')?.textContent || '[]'); }
    catch { return []; }
  };

  const colorForDepth = (d) => {
    if (d == null) return '#94a3b8';          // slate fallback
    return d >= 0.75 ? '#EF4444'              // red
         : d >= 0.50 ? '#F59E0B'              // amber
         : '#22C55E';                         // green
  };

  const tooltipHtml = (l) => `
    <div style="font-size:12px; line-height:1.2">
      <div><strong>Location:</strong> ${l.code}</div>
      <div><strong>Coordinates:</strong> (${(+l.lat).toFixed(6)}, ${(+l.lng).toFixed(6)})</div>
      <div><strong>(I, J):</strong> (${l.i ?? '—'}, ${l.j ?? '—'})</div>
      <div><strong>Max Depth:</strong> ${(l.max_depth ?? 0).toFixed(3)} m</div>
    </div>`;

  const render = () => {
    ensureMap(); if (!map) return;

    layerGroup.clearLayers();
    const locs = readLocs();
    if (!locs.length) return;

    const markers = [];

    locs.forEach(l => {
      const color = l.color ?? colorForDepth(l.max_depth);
      const m = L.circleMarker([l.lat, l.lng], {
        radius: 6,
        color,            // stroke
        weight: 2,
        fillColor: color, // fill
        fillOpacity: 0.9
      }).bindTooltip(tooltipHtml(l), { direction: 'top', offset: [0, -8], sticky: true, opacity: 1 });

      m.on('click', () => Livewire.dispatch('map.select-location', { locationId: l.id }));
      m.addTo(layerGroup);
      markers.push(m);
    });

    if (markers.length) {
      const g = L.featureGroup(markers);
      map.fitBounds(g.getBounds().pad(0.2));
    }
  };

  // Re-render when Livewire swaps the hidden JSON
  const locNode = document.getElementById('map-locs');
  if (locNode) {
    const obs = new MutationObserver(() => render());
    obs.observe(locNode, { childList: true, characterData: true, subtree: true });
  }

  // Initial draw + after Livewire morphs
  render();
  Livewire.hook('morph.updated', render);
});
</script>
@endpush

@extends('layouts.app')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-[320px,1fr,280px] gap-6">
  {{-- Left column --}}
  <div class="space-y-6">
    @livewire('dashboard.selector')
    @livewire('dashboard.predicted-table')

    <x-card>
      <x-slot:header>Flooding Risk Keys</x-slot:header>
      <div class="grid grid-cols-1 gap-2 text-sm">
        <div class="flex items-center justify-between"><span>Water Depth &lt; 0.5</span><span class="chip-green">Green</span></div>
        <div class="flex items-center justify-between"><span>Water Depth &lt; 0.75</span><span class="chip-amber">Amber</span></div>
        <div class="flex items-center justify-between"><span>Water Depth &gt; 0.75</span><span class="chip-red">Red</span></div>
      </div>
       
    </x-card>
  </div>

  {{-- Middle column --}}
  <div class="space-y-6">
    @livewire('dashboard.water-depth-chart')
    @livewire('dashboard.location-map')
  </div>

  {{-- Right column --}}
  <div class="space-y-6">
    {{-- If you have a Livewire features panel, keep this. Otherwise, remove the line below. --}}
    @livewire('dashboard.features-panel')

    <x-card>
      <x-slot:header>Supported By:</x-slot:header>
      <div class="grid gap-4 place-items-start">
        <img src="/images/logos/ireland.png" alt="Gov of Ireland" class="w-40 opacity-80">
        <img src="/images/logos/taighde.png" alt="Taighde Research Ireland" class="w-40 opacity-80">
        <img src="/images/logos/eu.png" alt="EU" class="w-40 opacity-80">
      </div>
    </x-card>
  </div>
</div>
@endsection

<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl">Platform4MMC</h2>
  </x-slot>

  <div class="p-6 grid gap-4 md:grid-cols-2">
    <x-card title="Projects" link="/projects" cta="Open"/>
    <x-card title="Knowledge Bank" link="/knowledge" cta="Browse"/>
    <x-card title="Viability Assessment" link="/dst/viability" cta="Start"/>
    <x-card title="Environmental Assessment" link="/dst/environmental" cta="Start"/>
  </div>
</x-app-layout>

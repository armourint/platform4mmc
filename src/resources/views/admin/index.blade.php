@extends('admin')

@section('content')
  <h1 class="text-2xl font-semibold mb-6">Admin Overview</h1>

  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
    <a href="{{ route('admin.layers') }}" class="block border rounded-lg p-4 bg-white hover:shadow">
      <div class="text-sm text-gray-500">Environmental</div>
      <div class="text-lg font-medium">Layers</div>
      <div class="mt-2 text-2xl">{{ \App\Models\EnvironmentalLayer::count() }}</div>
    </a>

    <a href="{{ route('admin.rules') }}" class="block border rounded-lg p-4 bg-white hover:shadow">
      <div class="text-sm text-gray-500">Viability</div>
      <div class="text-lg font-medium">Rules</div>
      <div class="mt-2 text-2xl">{{ \App\Models\Rule::count() }}</div>
    </a>

    <a href="{{ route('admin.manufacturers') }}" class="block border rounded-lg p-4 bg-white hover:shadow">
      <div class="text-sm text-gray-500">Suppliers</div>
      <div class="text-lg font-medium">Manufacturers</div>
      <div class="mt-2 text-2xl">{{ \App\Models\Manufacturer::count() }}</div>
    </a>

    <a href="{{ route('admin.manufacturers.map') }}" class="block border rounded-lg p-4 bg-white hover:shadow">
      <div class="text-sm text-gray-500">GIS</div>
      <div class="text-lg font-medium">Map</div>
      <div class="mt-2 text-base text-gray-600">View locations</div>
    </a>
  </div>
@endsection

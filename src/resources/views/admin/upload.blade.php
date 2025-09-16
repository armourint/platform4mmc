@extends('layouts.admin')
@section('content')
<h2>Upload & Import</h2>
<form method="POST" action="{{ route('runs.store') }}" enctype="multipart/form-data">
@csrf
<livewire:imports.upload />
<button type="submit">Import</button>
</form>
@endsection
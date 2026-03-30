@extends('layouts.app')
@section('title', isset($id) ? 'Edit Subject' : 'Create Subject')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/pages/subjects.css') }}">
@endpush

@section('content')
  <form class="card" style="max-width:600px">
    <div class="row">
      <label>Subject Name
        <input class="input" type="text" placeholder="e.g. Web Security">
      </label>
      <label>University / Dept (optional)
        <input class="input" type="text" placeholder="e.g. CS Dept">
      </label>
    </div>
    <div style="margin-top:12px;display:flex;gap:8px;justify-content:flex-end">
      <a href="{{ route('subjects.index') }}" class="btn">Cancel</a>
      <button class="btn primary" type="submit">Save</button>
    </div>
  </form>
@endsection

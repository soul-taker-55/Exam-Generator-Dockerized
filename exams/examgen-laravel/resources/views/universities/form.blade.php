@extends('layouts.app')
@section('title', isset($id) ? 'Edit University' : 'Create University')

@section('content')
  <form class="card" style="max-width:700px">
    <div class="row cols-2">
      <label>Name <input class="input" placeholder="University name"></label>
      <label>City <input class="input" placeholder="City"></label>
    </div>
    <div style="margin-top:12px;display:flex;gap:8px;justify-content:flex-end">
      <a href="{{ route('universities.index') }}" class="btn">Cancel</a>
      <button class="btn primary" type="submit">Save</button>
    </div>
  </form>
@endsection

@extends('layouts.app')
@section('title','Universities')

@section('content')
  <div class="card">
    <table class="table">
      <thead><tr><th>#</th><th>Name</th><th>City</th><th>Actions</th></tr></thead>
      <tbody>
        <tr><td>1</td><td>ABC University</td><td>Berlin</td><td><a class="link" href="{{ route('universities.edit',1) }}">Edit</a></td></tr>
      </tbody>
    </table>
  </div>
@endsection

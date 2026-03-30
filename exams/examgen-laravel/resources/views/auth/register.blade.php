@extends('layouts.guest')
@section('title','Register')

@section('content')
  <div class="auth-box">
    <h1>Create an account</h1>
    <form>
      <input class="input" type="text" placeholder="First name">
      <input class="input" type="text" placeholder="Last name" style="margin-top:8px">
      <input class="input" type="email" placeholder="Email" style="margin-top:8px">
      <input class="input" type="password" placeholder="Password" style="margin-top:8px">
      <button class="btn primary auth-btn" type="submit">Create</button>
    </form>
    <div class="auth-footer">
      Already have an account? <a class="link" href="{{ route('login') }}">Sign in</a>
    </div>
  </div>
@endsection

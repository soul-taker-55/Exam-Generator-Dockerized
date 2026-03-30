@extends('layouts.guest')
@section('title','Login')

@section('content')
  <div class="auth-box">
    <h1>Login</h1>
    <form>
      <input class="input" type="text" placeholder="Email or Username">
      <input class="input" type="password" placeholder="Password" style="margin-top:8px">
      <div class="auth-actions">
        <label><input type="checkbox"> Remember me</label>
        <a class="link" href="#">Forgot password?</a>
      </div>
      <button class="btn primary auth-btn" type="submit">Login</button>
    </form>
    <div class="auth-footer">
      Don’t have an account? <a class="link" href="{{ route('register') }}">Register</a>
    </div>
  </div>
@endsection

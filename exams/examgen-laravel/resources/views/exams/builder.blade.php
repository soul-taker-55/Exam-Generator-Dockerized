@extends('layouts.app')
@section('title','Exam Builder')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/pages/exams.css') }}">
@endpush

@section('content')
  <div class="builder">
    {{-- قائمة الأسئلة المتاحة (Bank) --}}
    <div class="bank">
      <h3>Question Bank</h3>
      <div class="bank-list">
        <div class="bank-item">
          <div>What is CSRF? <span class="muted">• Web Security • Medium</span></div>
          <button class="btn">Add</button>
        </div>
        <div class="bank-item">
          <div>OSI Layer 3 function? <span class="muted">• Networks • Easy</span></div>
          <button class="btn">Add</button>
        </div>
      </div>
    </div>

    {{-- الأسئلة المختارة (Picked) --}}
    <div class="picked">
      <h3>Selected Questions</h3>
      <div class="picked-list">
        <div class="picked-item">
          <div>1) What is CSRF?</div>
          <input class="input points" type="number" value="2">
        </div>
      </div>

      <div style="margin-top:12px;display:flex;gap:8px;justify-content:flex-end">
        <a href="{{ route('exams.index') }}" class="btn">Save Draft</a>
        <button class="btn primary">Publish</button>
      </div>
    </div>
  </div>
@endsection

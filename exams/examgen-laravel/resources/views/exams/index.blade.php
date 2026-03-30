@extends('layouts.app')
@section('title','View Exams')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/exams/index.css') }}">
@endpush

@section('content')
  <div class="exams-header">
    <h1><i class="fa-solid fa-list"></i> My Exams</h1>
    <a href="{{ route('exams.create') }}" class="btn primary">
      <i class="fa-solid fa-plus"></i> Create New Exam
    </a>
  </div>

  <div class="exams-container">
    <div class="exam-card">
      <div class="exam-info">
        <h3>Midterm Exam – Web Security</h3>
        <p>Subject: Web Security</p>
        <p>Date: 2025-09-15</p>
        <p>Total Questions: 10 | Total Marks: 70</p>
      </div>
      <div class="exam-actions">
        <a href="#" class="btn small"><i class="fa-solid fa-eye"></i> View</a>
        <a href="#" class="btn small"><i class="fa-solid fa-pen"></i> Edit</a>
        <a href="#" class="btn small danger"><i class="fa-solid fa-trash"></i> Delete</a>
      </div>
    </div>

    <div class="exam-card">
      <div class="exam-info">
        <h3>Final Exam – Databases</h3>
        <p>Subject: Databases</p>
        <p>Date: 2025-11-01</p>
        <p>Total Questions: 15 | Total Marks: 100</p>
      </div>
      <div class="exam-actions">
        <a href="#" class="btn small"><i class="fa-solid fa-eye"></i> View</a>
        <a href="#" class="btn small"><i class="fa-solid fa-pen"></i> Edit</a>
        <a href="#" class="btn small danger"><i class="fa-solid fa-trash"></i> Delete</a>
      </div>
    </div>
  </div>
@endsection

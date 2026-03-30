@extends('layouts.app')
@section('title', 'Professor Dashboard')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/dashboard.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('js/pages/dashboard.js') }}"></script>
@endpush

@section('content')
  <div class="dashboard-header">
    <h1>
      {{ $headline ?? __("The students don't know what's coming, Professor :name!", ['name' => ($professor->name ?? Auth::user()->name ?? 'Professor')]) }}
    </h1>

    <div class="quick-stats">
      <div class="stat-card">
        <i class="fa-solid fa-question"></i>
        <div>
          <h3>Questions</h3>
          <p>{{ $stats['questions'] ?? 0 }}</p>
        </div>
      </div>
      <div class="stat-card">
        <i class="fa-solid fa-file-lines"></i>
        <div>
          <h3>Exams</h3>
          <p>{{ $stats['exams'] ?? 0 }}</p>
        </div>
      </div>
      <div class="stat-card">
        <i class="fa-solid fa-book"></i>
        <div>
          <h3>Subjects</h3>
          <p>{{ $stats['subjects'] ?? 0 }}</p>
        </div>
      </div>
    </div>
  </div>

  <div class="dashboard-content">
    {{-- Recent Activity --}}
    <div class="recent-activity">
      <h2><i class="fa-solid fa-history"></i> Recent Activity</h2>
      <div class="activity-list">
        @forelse($recentActivities ?? [] as $item)
          <div class="activity-item">
            <i class="fa-solid {{ $item['icon'] ?? 'fa-circle-question' }}"></i>
            <div>
              <p>{{ $item['text'] ?? 'Activity' }}</p>
              <small>{{ \Carbon\Carbon::parse($item['date'] ?? now())->toFormattedDateString() }}</small>
            </div>
          </div>
        @empty
          <div class="activity-item">
            <i class="fa-regular fa-bell"></i>
            <div>
              <p>No recent activity</p>
              <small>—</small>
            </div>
          </div>
        @endforelse
      </div>
    </div>

    {{-- Upcoming Exams --}}
    <div class="upcoming-exams">
      <h2><i class="fa-solid fa-calendar-days"></i> Upcoming Exams</h2>
      <div class="exams-list">
        @forelse($upcomingExams ?? [] as $exam)
          <div class="exam-item {{ $exam['tag'] ?? '' }}">
            <i class="fa-solid fa-file-circle-check"></i>
            <div>
              <p>{{ $exam['title'] ?? 'Exam' }}</p>
              <small>{{ $exam['date'] ?? '' }}</small>
            </div>
            <a href="{{ $exam['url'] ?? '#' }}" class="view-btn">
              <i class="fa-solid fa-eye"></i> View
            </a>
          </div>
        @empty
          <div class="exam-item">
            <i class="fa-solid fa-info-circle"></i>
            <div>
              <p>No upcoming exams in the next 30 days</p>
            </div>
          </div>
        @endforelse
      </div>
    </div>
  </div>
@endsection

@extends('layouts.app')
@section('title','Create Exam')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/pages/exams/form.css') }}">
@endpush

@push('scripts')
  <script src="{{ asset('js/pages/exams/form.js') }}?v={{ time() }}" defer></script>
@endpush

@push('scripts')
  <script src="{{ asset('js/pages/exams/form.js') }}?v={{ time() }}" defer></script>
  <script src="{{ asset('js/pages/questions/core.js') }}?v={{ time() }}" defer></script>
@endpush


@section('content')
  <div class="container">
    <h1><i class="fa-solid fa-file-alt"></i> Create New Exam</h1>

    {{-- رسائل تجريبية --}}
    <div class="alert alert-error" id="errDate" style="display:none;">Exam date is required</div>
    <div class="alert alert-success" id="okMsg" style="display:none;">Exam generated successfully!</div>

    <form id="examForm" novalidate>
      <div class="exam-creation-container">

        {{-- اللوحة الجانبية: المادة + التاريخ + القالب + الملخص --}}
        <div class="subject-selection">
          <h2>Select Subject and Date</h2>

          <div class="form-group">
            <label for="subject_id">Subject:</label>
            <select id="subject_id" name="subject_id" required>
              <option value="">-- Select Subject --</option>
              <option value="6">Programming -2-</option>
              <option value="3">Algorithms -1-</option>
              <option value="7">Algorithms -2-</option>
              <option value="8">Programming in Logic</option>
              <option value="9">Cyber Security</option>
            </select>
          </div>

          <div class="form-group">
            <label for="exam_date">Exam Date:</label>
            <input type="date" id="exam_date" name="exam_date" required>
          </div>

          <h3>Exam Layout Options</h3>
          <div class="form-group">
            <label for="template_type">Template Style:</label>
            <select id="template_type" name="template_type">
              <option value="default">Default Template</option>
              <option value="split">Split Page (Two Columns)</option>
              <option value="split_line">Split Page with Line</option>
            </select>
          </div>

          <div class="template-preview">
            <h3>Template Preview</h3>
            <div id="preview-container">
              <img
                id="template-preview-img"
                src="{{ asset('images/template_default_preview.png') }}"
                data-default="{{ asset('images/template_default_preview.png') }}"
                data-split="{{ asset('images/template_split_preview.png') }}"
                data-split-line="{{ asset('images/template_split_line_preview.png') }}"
                alt="Template Preview" width="100%">
            </div>

            <div id="template-description" class="template-description">
              <p id="default-description">Standard layout with questions in sequence.</p>
              <p id="split-description" style="display:none;">Two column layout to minimize wasted space.</p>
              <p id="split-line-description" style="display:none;">Two columns with a dividing line.</p>
            </div>
          </div>

          <div class="exam-summary">
            <h3>Exam Summary</h3>
            <div class="summary-item"><span>Total Questions:</span> <span id="totalQuestions">0</span></div>
            <div class="summary-item"><span>Selected Questions:</span> <span id="selectedCount">0</span></div>
            <div class="summary-item"><span>Total Points:</span> <span id="totalPoints">0</span></div>
            <button type="submit" id="generateBtn" class="btn btn-generate" disabled>
              <i class="fa-solid fa-file-pdf"></i> Generate Exam PDF
            </button>
          </div>
        </div>

        {{-- منطقة الأسئلة --}}
        <div class="question-selection">
          <div class="qs-header">
            <h2>Select Questions</h2>
            <button type="button" class="btn btn-add" id="openQuestionsPanelBtn">
              <i class="fa-solid fa-plus"></i> Add a Question
            </button>
          </div>

          <div class="info-hint" id="selectSubjectHint">
            <i class="fa-solid fa-circle-info"></i> Select a subject first
          </div>

          <div class="filter-controls" id="filtersBar" style="display:none;">
            <div class="search-box">
              <input type="text" id="questionSearch" placeholder="Search questions...">
              <i class="fa-solid fa-search"></i>
            </div>
            <div class="difficulty-filter">
              <label>Filter by difficulty:</label>
              <select id="difficultyFilter">
                <option value="0">All</option>
                <option value="1">Easy</option>
                <option value="2">Medium</option>
                <option value="3">Hard</option>
              </select>
            </div>
            <div class="selection-actions">
              <button type="button" class="btn btn-secondary" id="selectAllBtn">
                <i class="fa-solid fa-check-double"></i> Select All
              </button>
              <button type="button" class="btn btn-secondary" id="deselectAllBtn">
                <i class="fa-solid fa-xmark"></i> Deselect All
              </button>
            </div>
          </div>

          <div class="question-list" id="questionList" style="display:none;"></div>

          <div class="no-questions" id="noQuestions" style="display:none;">
            <i class="fa-solid fa-inbox"></i>
            <p>No questions for the selected subject</p>
          </div>

          <div class="no-results" id="noResults" style="display:none;">
            <i class="fa-solid fa-search"></i>
            <p>No questions match your search criteria</p>
          </div>
        </div>

      </div>
    </form>
  </div>

  {{-- Toast --}}
  <div class="toast" id="toast" role="status" aria-live="polite"></div>

  {{-- ======== Modal وسط الشاشة لإدارة الأسئلة ======== --}}
  <div class="modal-center" id="qsModal" aria-hidden="true">
    <div class="modal-backdrop" id="qsModalBackdrop"></div>

    <div class="modal-dialog" role="dialog" aria-modal="true" aria-labelledby="qsModalTitle">
      <div class="modal-header">
        <h3 id="qsModalTitle"><i class="fa-solid fa-layer-group"></i> Manage Questions</h3>
        <button type="button" class="modal-close" id="qsModalClose" aria-label="Close">&times;</button>
      </div>

      <nav class="modal-tabs" id="qsTabs" role="tablist">
        <button class="tab-link active" data-route="{{ route('questions.index') }}" data-tab="index" role="tab" aria-selected="true">
          <i class="fa-solid fa-list"></i> Index
        </button>
        <button class="tab-link" data-route="{{ route('questions.import') }}" data-tab="import" role="tab" aria-selected="false">
          <i class="fa-solid fa-file-import"></i> Import
        </button>
        <button class="tab-link" data-route="{{ route('questions.create') }}" data-tab="form" role="tab" aria-selected="false">
          <i class="fa-solid fa-plus"></i> New
        </button>
      </nav>

      <section class="modal-body" id="qsBody" aria-live="polite">
        <div class="modal-loading" id="qsLoading" style="display:none;">
          <i class="fa-solid fa-spinner fa-spin"></i> Loading...
        </div>
        <div class="modal-content" id="qsContent"></div>
      </section>
    </div>
  </div>
@endsection

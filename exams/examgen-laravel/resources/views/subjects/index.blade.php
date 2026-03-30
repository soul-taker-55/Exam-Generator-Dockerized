@extends('layouts.app')
@section('title','My Subjects')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/pages/my_subjects.css') }}">
@endpush

@push('scripts')
  <script src="{{ asset('js/pages/my_subjects.js') }}"></script>
@endpush

@section('content')
  <div class="container">
    <h1><i class="fa-solid fa-book"></i> My Subjects</h1>

    <button class="add-subject-btn" id="openAddSubjectBtn">
      <i class="fa-solid fa-plus"></i> Add New Subject
    </button>

    <div class="subjects-container">
      {{-- بطاقات ستاتيكية تماماً مثل الكود القديم --}}
      <div class="subject-card" data-id="3">
        <div class="subject-info">
          <h3>
            <a href="#" style="text-decoration:none;color:inherit;">Algorithms -1-</a>
          </h3>
          <p>University of Aleppo</p>
          <p>Total Mark: 70 | Duration: 90 minutes</p>
        </div>
        <div class="subject-actions">
          <button class="action-btn edit-btn"
                  data-open-edit
                  data-id="3"
                  data-name="Algorithms -1-"
                  data-total="70"
                  data-duration="90 minutes">
            <i class="fa-solid fa-pen-to-square"></i> Edit
          </button>
          <button class="action-btn delete-btn" data-delete data-id="3">
            <i class="fa-solid fa-trash"></i> Delete
          </button>
        </div>
      </div>

      <div class="subject-card" data-id="7">
        <div class="subject-info">
          <h3>
            <a href="#" style="text-decoration:none;color:inherit;">Algorithms -2-</a>
          </h3>
          <p>University of Aleppo</p>
          <p>Total Mark: 70 | Duration: 1 hour</p>
        </div>
        <div class="subject-actions">
          <button class="action-btn edit-btn"
                  data-open-edit
                  data-id="7"
                  data-name="Algorithms -2-"
                  data-total="70"
                  data-duration="1 hour">
            <i class="fa-solid fa-pen-to-square"></i> Edit
          </button>
          <button class="action-btn delete-btn" data-delete data-id="7">
            <i class="fa-solid fa-trash"></i> Delete
          </button>
        </div>
      </div>

      <div class="subject-card" data-id="9">
        <div class="subject-info">
          <h3>
            <a href="#" style="text-decoration:none;color:inherit;">Cyber Security</a>
          </h3>
          <p>University of Aleppo</p>
          <p>Total Mark: 70 | Duration: 1 hour</p>
        </div>
        <div class="subject-actions">
          <button class="action-btn edit-btn"
                  data-open-edit
                  data-id="9"
                  data-name="Cyber Security"
                  data-total="70"
                  data-duration="1 hour">
            <i class="fa-solid fa-pen-to-square"></i> Edit
          </button>
          <button class="action-btn delete-btn" data-delete data-id="9">
            <i class="fa-solid fa-trash"></i> Delete
          </button>
        </div>
      </div>

      <div class="subject-card" data-id="6">
        <div class="subject-info">
          <h3>
            <a href="#" style="text-decoration:none;color:inherit;">Programming -2-</a>
          </h3>
          <p>University of Aleppo</p>
          <p>Total Mark: 70 | Duration: 1.5 hours</p>
        </div>
        <div class="subject-actions">
          <button class="action-btn edit-btn"
                  data-open-edit
                  data-id="6"
                  data-name="Programming -2-"
                  data-total="70"
                  data-duration="1.5 hours">
            <i class="fa-solid fa-pen-to-square"></i> Edit
          </button>
          <button class="action-btn delete-btn" data-delete data-id="6">
            <i class="fa-solid fa-trash"></i> Delete
          </button>
        </div>
      </div>

      <div class="subject-card" data-id="8">
        <div class="subject-info">
          <h3>
            <a href="#" style="text-decoration:none;color:inherit;">Programming in Logic</a>
          </h3>
          <p>University of Aleppo</p>
          <p>Total Mark: 70 | Duration: 2.5 hours</p>
        </div>
        <div class="subject-actions">
          <button class="action-btn edit-btn"
                  data-open-edit
                  data-id="8"
                  data-name="Programming in Logic"
                  data-total="70"
                  data-duration="2.5 hours">
            <i class="fa-solid fa-pen-to-square"></i> Edit
          </button>
          <button class="action-btn delete-btn" data-delete data-id="8">
            <i class="fa-solid fa-trash"></i> Delete
          </button>
        </div>
      </div>
    </div>
  </div>

  {{-- Add Subject Modal (Static) --}}
  <div id="addSubjectModal" class="modal" aria-hidden="true">
    <div class="modal-content" role="dialog" aria-modal="true" aria-labelledby="addSubjectTitle">
      <span class="close-btn" data-close-add>&times;</span>
      <h2 id="addSubjectTitle"><i class="fa-solid fa-plus"></i> Add New Subject</h2>

      <form id="addSubjectForm" action="#" method="POST">
        <div class="form-group">
          <label for="subjectName">Subject Name:</label>
          <input type="text" id="subjectName" name="name" required>
        </div>

        <div class="form-group">
          <label for="university">University:</label>
          <select id="university" name="university" required>
            <option value="">Select University</option>
            <option value="1">University of Aleppo</option>
          </select>
        </div>

        <div class="form-group">
          <label for="totalMark">Total Mark:</label>
          <input type="number" id="totalMark" name="total_mark" min="1" required>
        </div>

        <div class="form-group">
          <label for="duration">Duration:</label>
          <input type="text" id="duration" name="duration" placeholder="e.g. 90 minutes" required>
        </div>

        <button type="button" class="cancel-btn" data-close-add>
          <i class="fa-solid fa-xmark"></i> Cancel
        </button>

        <button type="submit" class="submit-btn">
          <i class="fa-solid fa-floppy-disk"></i> Save Subject
        </button>
      </form>
    </div>
  </div>

  {{-- Edit Subject Modal (Static) --}}
  <div id="editSubjectModal" class="modal" aria-hidden="true">
    <div class="modal-content" role="dialog" aria-modal="true" aria-labelledby="editSubjectTitle">
      <span class="close-btn" data-close-edit>&times;</span>
      <h2 id="editSubjectTitle"><i class="fa-solid fa-pen-to-square"></i> Edit Subject</h2>

      <form id="editSubjectForm" action="#" method="POST">
        <input type="hidden" id="editSubjectId" name="id">

        <div class="form-group">
          <label for="editSubjectName">Subject Name:</label>
          <input type="text" id="editSubjectName" name="name" required>
        </div>

        <div class="form-group">
          <label for="editTotalMark">Total Mark:</label>
          <input type="number" id="editTotalMark" name="total_mark" min="1" required>
        </div>

        <div class="form-group">
          <label for="editDuration">Duration:</label>
          <input type="text" id="editDuration" name="duration" required>
        </div>

        <button type="button" class="cancel-btn" data-close-edit>
          <i class="fa-solid fa-xmark"></i> Cancel
        </button>

        <button type="submit" class="submit-btn">
          <i class="fa-solid fa-floppy-disk"></i> Save Subject
        </button>
      </form>
    </div>
  </div>
@endsection

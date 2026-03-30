<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

// صفحات الضيف مؤقتًا
Route::view('/login', 'auth.login')->name('login');
Route::view('/register', 'auth.register')->name('register');

// صفحات التطبيق (Route::view مؤقتاً حتى نربط بالكنترولرز لاحقاً)
Route::view('/dashboard', 'dashboard.index')->name('dashboard');

// الجامعات
Route::view('/universities', 'universities.index')->name('universities.index');
Route::view('/universities/create', 'universities.form')->name('universities.create');
Route::view('/universities/{id}/edit', 'universities.form')->name('universities.edit');

// المواد
Route::view('/subjects', 'subjects.index')->name('subjects.index');
Route::view('/subjects/create', 'subjects.form')->name('subjects.create');
Route::view('/subjects/{id}/edit', 'subjects.form')->name('subjects.edit');

// الأسئلة
Route::view('/questions', 'questions.index')->name('questions.index');
Route::view('/questions/create', 'questions.form')->name('questions.create');
Route::view('/questions/{id}/edit', 'questions.form')->name('questions.edit');
Route::view('/questions/import', 'questions.import')->name('questions.import');

// الامتحانات
Route::view('/exams', 'exams.index')->name('exams.index');
Route::view('/exams/create', 'exams.form')->name('exams.create');
Route::view('/exams/{id}/builder', 'exams.builder')->name('exams.builder');

// الرصيد
Route::view('/tokens', 'tokens.index')->name('tokens.index');

// الملف الشخصي
Route::view('/profile', 'profile.index')->name('profile.index');

// لا نستدعي routes/auth.php الآن لأننا نشغّل الواجهات فقط

// Placeholder logout route (UI-only phase)
Route::match(['GET','POST'], '/logout', function () {
    // امسح أي جلسة بسيطة لاحقاً لو فعلنا Auth
    return redirect()->route('login');
})->name('logout');

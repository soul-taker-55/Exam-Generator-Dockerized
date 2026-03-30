@extends('layouts.app')
@section('title','My Profile')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/pages/profile/profile.css') }}">
@endpush

@section('content')
<div class="profile-page"><!-- سكوب الستايل -->
  <div class="container">
    <div class="profile-container">
      <h1><i class="fa-solid fa-user-gear"></i> My Profile</h1>

      <div class="alert alert-success" style="display:none;">Profile updated successfully!</div>
      <div class="alert alert-error" style="display:none;">Something went wrong</div>

      {{-- Profile info --}}
      <section class="profile-section">
        <h2><i class="fa-solid fa-user-edit"></i> Profile Information</h2>
        <form id="profileForm" novalidate>
          <div class="form-group">
            <label for="first_name">First Name</label>
            <input id="first_name" type="text" value="Mauricio" required>
          </div>
          <div class="form-group">
            <label for="last_name">Last Name</label>
            <input id="last_name" type="text" value="Sayegh">
          </div>
          <div class="form-group">
            <label for="email">Email</label>
            <input id="email" type="email" value="mauriciosayegh7@gmail.com" required>
          </div>
          <div class="form-group">
            <label for="phone">Phone Number</label>
            <input id="phone" type="tel" value="0937568644">
          </div>
          <button type="button" class="btn btn-primary" id="btnUpdateProfile">
            <i class="fa-solid fa-save"></i> Update Profile
          </button>
        </form>
      </section>

      {{-- Tokens --}}
      <section class="tokens-section">
        <h2><i class="fa-solid fa-coins"></i> Exam Tokens</h2>

        <div class="token-balance">
          <h3>Current Balance</h3>
          <div class="balance-amount">
            <span id="tokenBalance">100</span>
            <i class="fa-solid fa-coins"></i>
          </div>
          <p>Tokens are used to generate exam papers (1 token per exam)</p>
        </div>

        <div class="purchase-section">
          <h3>Purchase Additional Tokens</h3>

          <div class="token-packages">
            <label class="package">
              <input type="radio" name="token_package" value="small" checked>
              <div class="card">
                <span class="amount">10 Tokens</span>
                <span class="price">$4.99</span>
              </div>
            </label>

            <label class="package">
              <input type="radio" name="token_package" value="medium">
              <div class="card">
                <span class="amount">25 Tokens</span>
                <span class="price">$9.99</span>
              </div>
            </label>

            <label class="package">
              <input type="radio" name="token_package" value="large">
              <div class="card">
                <span class="amount">50 Tokens</span>
                <span class="price">$14.99</span>
              </div>
            </label>
          </div>

          <button type="button" class="btn btn-purchase" id="btnPurchase">
            <i class="fa-solid fa-credit-card"></i> Purchase Tokens
          </button>
        </div>

        <div class="transaction-history">
          <h3>Recent Transactions</h3>
          <table>
            <thead>
              <tr><th>Date</th><th>Tokens</th><th>Amount</th></tr>
            </thead>
            <tbody>
              <tr><td>Aug 31, 2025</td><td>+10</td><td>$4.99</td></tr>
              <tr><td>Aug 31, 2025</td><td>+50</td><td>$14.99</td></tr>
              <tr><td>Aug 31, 2025</td><td>+10</td><td>$4.99</td></tr>
              <tr><td>Aug 31, 2025</td><td>+10</td><td>$4.99</td></tr>
              <tr><td>May 4, 2025</td><td>+10</td><td>$4.99</td></tr>
            </tbody>
          </table>
        </div>
      </section>

      {{-- Universities --}}
      <section class="university-section">
        <h2><i class="fa-solid fa-university"></i> University Affiliation</h2>

        <div class="current-university-box">
          <h3>Your Current University</h3>
          <div class="university-list">
            <div class="university-item">
              <span>University of Aleppo</span>
              <a href="javascript:void(0)" class="remove-university">
                <i class="fa-solid fa-xmark"></i> Remove
              </a>
            </div>
          </div>
        </div>

        <form class="add-university-form" onsubmit="return false;">
          <div class="form-group">
            <label for="university_id">Add New University</label>
            <div class="university-select-wrapper">
              <select id="university_id" class="university-select">
                <option value="">-- Select University --</option>
                <option value="6">Al-Ala Private University</option>
                <option value="2">Al-Manara University for Medical Sciences</option>
                <option value="4">Al-Shahba Private University</option>
                <option value="5">Al-Union Private University</option>
                <option value="3">Arab International University (AIU)</option>
                <option value="9">Cordoba Private University (CPU)</option>
                <option value="8">Higher Institute for Applied Sciences and Technology (HIAST)</option>
                <option value="7">International University for Science and Technology (IUST)</option>
              </select>
              <button type="button" class="btn btn-add" id="btnAddUniversity">
                <i class="fa-solid fa-plus"></i> Add
              </button>
            </div>
          </div>
        </form>
      </section>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  // Toast صغير داخل البروفايل
  function showToast(msg){
    const el = document.createElement('div');
    el.className = 'toast-prof';
    el.textContent = msg;
    document.querySelector('.profile-page').appendChild(el);
    requestAnimationFrame(()=> el.classList.add('show'));
    setTimeout(()=> { el.classList.remove('show'); setTimeout(()=> el.remove(), 200); }, 1600);
  }

  document.addEventListener('DOMContentLoaded', ()=>{
    // تمييز الباكيج المختار
    document.querySelectorAll('.token-packages .package input').forEach(r=>{
      r.addEventListener('change', ()=>{
        document.querySelectorAll('.token-packages .package').forEach(w=> w.classList.remove('active'));
        r.closest('.package').classList.add('active');
      });
    });

    document.getElementById('btnUpdateProfile')?.addEventListener('click', ()=> showToast('Saved (static)'));
    document.getElementById('btnPurchase')?.addEventListener('click', ()=>{
      const sel = document.querySelector('.token-packages .package input:checked')?.value || 'small';
      showToast('Purchased ('+sel+') — static');
    });

    document.getElementById('btnAddUniversity')?.addEventListener('click', ()=>{
      const sel = document.getElementById('university_id');
      if(!sel.value) { showToast('Select a university'); return; }
      const list = document.querySelector('.university-list');
      const div  = document.createElement('div');
      div.className = 'university-item';
      div.innerHTML = '<span>'+ sel.options[sel.selectedIndex].text +
        '</span><a class="remove-university" href="javascript:void(0)"><i class="fa-solid fa-xmark"></i> Remove</a>';
      list.appendChild(div);
      sel.value = '';
      showToast('Added (static)');
    });

    document.querySelector('.university-list')?.addEventListener('click', (e)=>{
      const a = e.target.closest('.remove-university');
      if(a){ a.closest('.university-item').remove(); showToast('Removed (static)'); }
    });
  });
</script>
@endpush

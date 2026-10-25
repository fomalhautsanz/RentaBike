{{-- RENTAL FORM SCREEN --}}
<section class="screen" id="rental-form">
  <div class="page-header">
    <button class="back-btn" onclick="goTo('home')">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <polyline points="15 18 9 12 15 6"/>
      </svg>
    </button>
    <h2>Rental Form</h2>
  </div>

  <div class="form-wrap">
    <div class="form-card">
      <div class="form-group">
        <label class="form-label">Borrower Name</label>
        <input class="form-input" type="text" placeholder="Enter full name">
      </div>

      <div class="form-group">
        <label class="form-label">Contact Number</label>
        <input class="form-input" type="tel" placeholder="09XXXXXXXXX">
      </div>

      <div class="form-group">
        <label class="form-label">Borrow Time</label>
        <input class="form-input" type="time">
      </div>

      <div class="form-group">
        <label class="form-label">Expected Return Time</label>
        <input class="form-input" type="time">
      </div>

      <div class="form-group">
        <label class="form-label">Upload Valid ID</label>
        <div class="upload-wrap">
          <div class="upload-box">
            <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
              <rect width="14" height="18" x="5" y="3" rx="2"/>
              <path d="M9 7h6M9 11h6M9 15h4"/>
            </svg>
            <p>Tap to upload ID</p>
            <input type="file">
          </div>
        </div>
      </div>

      <button class="primary-btn" onclick="submitRental()">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <polyline points="20 6 9 17 4 12"/>
        </svg>
        Confirm Rental
      </button>
    </div>
  </div>
</section>
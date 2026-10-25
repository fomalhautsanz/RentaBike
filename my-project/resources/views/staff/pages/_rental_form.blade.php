{{-- RENTAL FORM SCREEN --}}
<style>
  /* Rental form only; gi-butang diri para dili ma-gunitan ang shared staff layout */
  .rf-bike{background:var(--green-50);border:1px solid var(--green-100);border-radius:var(--radius-md);padding:12px 14px;margin-bottom:16px}
  .rf-bike-name{font-size:15px;font-weight:700;color:var(--gray-900)}
  .rf-bike-meta{font-size:12px;color:var(--gray-600);margin-top:2px}
  .rf-packages{display:grid;grid-template-columns:repeat(3,1fr);gap:8px}
  .rf-pkg{border:1.5px solid var(--gray-200);background:var(--white);border-radius:var(--radius-md);padding:10px 4px;cursor:pointer;font-family:'DM Sans',sans-serif;text-align:center}
  .rf-pkg .rf-pkg-time{display:block;font-size:13px;font-weight:700;color:var(--gray-900)}
  .rf-pkg .rf-pkg-price{display:block;font-size:12px;color:var(--gray-600);margin-top:2px}
  .rf-pkg.active{border-color:var(--green-600);background:var(--green-50)}
  .rf-pkg.active .rf-pkg-time,.rf-pkg.active .rf-pkg-price{color:var(--green-700)}
  .rf-computed{background:var(--gray-50);border:1px solid var(--gray-200);border-radius:var(--radius-md);padding:12px 14px;font-size:15px;font-weight:600;color:var(--gray-900)}
  .rf-total{display:flex;justify-content:space-between;align-items:center;padding:12px 0;font-size:14px;color:var(--gray-600)}
  .rf-total b{font-size:20px;color:var(--green-700)}
</style>
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
      {{-- Bike nga na-scan (gi-fill sa openRentalForm) --}}
      <div class="rf-bike" id="rf-bike">
        <div class="rf-bike-name" id="rf-bike-name">—</div>
        <div class="rf-bike-meta" id="rf-bike-meta"></div>
      </div>

      <div class="form-group">
        <label class="form-label">Borrower Name</label>
        <input class="form-input" id="rf-name" type="text" maxlength="150" placeholder="Enter full name">
      </div>

      <div class="form-group">
        <label class="form-label">Contact Number</label>
        <input class="form-input" id="rf-contact" type="tel" maxlength="30" placeholder="09XXXXXXXXX">
      </div>

      <div class="form-group">
        <label class="form-label">Rental Duration</label>
        <div class="rf-packages" id="rf-packages"></div>
      </div>

      <div class="form-group">
        <label class="form-label">Borrow Time</label>
        <input class="form-input" id="rf-borrow-time" type="time" oninput="updateRentalSummary()">
      </div>

      {{-- Ang return time gi-compute sa system (borrow time + duration) --}}
      <div class="form-group">
        <label class="form-label">Expected Return Time (automatic)</label>
        <div class="rf-computed" id="rf-expected-return">—</div>
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

      <div class="rf-total"><span>Total to pay</span><b id="rf-total">₱0</b></div>

      <button class="primary-btn" id="rf-confirm" onclick="submitRental()">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <polyline points="20 6 9 17 4 12"/>
        </svg>
        Confirm Rental
      </button>
    </div>
  </div>
</section>
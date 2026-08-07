{{-- REPORT FORM SCREEN --}}
<section class="screen" id="report-form">
  <div class="page-header">
    <button class="back-btn" onclick="goTo('report')">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <polyline points="15 18 9 12 15 6"/>
      </svg>
    </button>
    <h2 id="reportFormTitle">Report Damage</h2>
  </div>

  <div class="form-wrap">
    <div class="form-card">
      <div class="form-group">
        <label class="form-label">Bike ID</label>
        <input class="form-input" type="text" placeholder="e.g. RB-001" id="reportBikeId">
      </div>

      <div class="form-group">
        <label class="form-label">Description</label>
        <textarea class="form-textarea" rows="4" placeholder="Describe the issue in detail..." id="reportDesc"></textarea>
      </div>

      <div class="form-group">
        <label class="form-label">Photo Evidence (optional)</label>
        <div class="upload-wrap">
          <div class="upload-box">
            <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
              <rect width="18" height="18" x="3" y="3" rx="2" ry="2"/>
              <circle cx="9" cy="9" r="2"/>
              <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/>
            </svg>
            <p>Tap to upload photo</p>
            <input type="file" accept="image/*">
          </div>
        </div>
      </div>

      <button class="primary-btn" onclick="submitReport()">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <polyline points="20 6 9 17 4 12"/>
        </svg>
        Submit Report
      </button>
    </div>
  </div>
</section>
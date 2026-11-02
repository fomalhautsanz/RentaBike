{{-- REPORT FORM SCREEN --}}
<section class="screen" id="report-form">
  <div class="page-header">
    <button class="back-btn" onclick="goTo('report')">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <polyline points="15 18 9 12 15 6"/>
      </svg>
    </button>
    <h2 id="reportFormTitle">{{ ['damage' => 'Report Damage', 'missing' => 'Report Missing Bike', 'other' => 'Other Issue'][old('issue_type', 'damage')] ?? 'Report Issue' }}</h2>
  </div>

  <div class="form-wrap">
    <div class="form-card">
      @if($errors->any())
        <div role="alert" style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:11px 14px;margin-bottom:16px;color:#b91c1c">
          {{ $errors->first() }}
        </div>
      @endif

      <form id="issue-report-form" method="POST" action="{{ route('staff.issues.store') }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="issue_type" id="reportType" value="{{ old('issue_type', 'damage') }}">

        <div class="form-group">
          <label class="form-label" for="reportBikeId">Bike ID</label>
          <input class="form-input" type="text" name="bike_code" placeholder="e.g. MB-001" id="reportBikeId" value="{{ old('bike_code') }}" maxlength="100" required>
        </div>

        <div class="form-group">
          <label class="form-label" for="reportDesc">Description</label>
          <textarea class="form-textarea" name="description" rows="4" placeholder="Describe the issue in detail..." id="reportDesc" maxlength="5000" required>{{ old('description') }}</textarea>
        </div>

        <div class="form-group">
          <label class="form-label" for="reportPhoto">Photo Evidence (optional)</label>
          <div class="upload-wrap">
            <div class="upload-box">
              <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <rect width="18" height="18" x="3" y="3" rx="2" ry="2"/>
                <circle cx="9" cy="9" r="2"/>
                <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/>
              </svg>
              <p>Tap to upload photo</p>
              <input type="file" name="photo" id="reportPhoto" accept="image/*">
            </div>
          </div>
        </div>

        <button type="button" class="primary-btn" onclick="submitReport()">
          <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <polyline points="20 6 9 17 4 12"/>
          </svg>
          Submit Report
        </button>
      </form>
    </div>
  </div>
</section>
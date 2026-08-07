{{-- REPORT ISSUE SCREEN --}}
<section class="screen" id="report">
  <div class="page-header">
    <button class="back-btn" onclick="goTo('home')">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <polyline points="15 18 9 12 15 6"/>
      </svg>
    </button>
    <h2>Report Issue</h2>
  </div>

  <div class="content">
    <div class="report-types">
      <div class="report-card" onclick="openReportForm('damage')">
        <div class="report-icon orange">
          <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
          </svg>
        </div>
        <div class="report-body">
          <h4>Report Damage</h4>
          <p>Flat tire, broken part, etc.</p>
        </div>
        <div class="report-chevron">
          <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
        </div>
      </div>

      <div class="report-card" onclick="openReportForm('missing')">
        <div class="report-icon red">
          <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
          </svg>
        </div>
        <div class="report-body">
          <h4>Report Missing Bike</h4>
          <p>Bike not returned / missing</p>
        </div>
        <div class="report-chevron">
          <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
        </div>
      </div>

      <div class="report-card" onclick="openReportForm('other')">
        <div class="report-icon blue">
          <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3z"/>
            <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
          </svg>
        </div>
        <div class="report-body">
          <h4>Other Issue</h4>
          <p>Something else to flag</p>
        </div>
        <div class="report-chevron">
          <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
        </div>
      </div>
    </div>
  </div>
</section>
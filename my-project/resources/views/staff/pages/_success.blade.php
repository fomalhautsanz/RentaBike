{{-- SUCCESS SCREEN --}}
<section class="screen" id="success">
  <div class="success-wrap">
    <div class="success-anim">
      <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
        <polyline points="20 6 9 17 4 12"/>
      </svg>
    </div>
    <h2>Bike Assigned!</h2>
    <p class="success-sub">Bike 102 has been successfully rented.</p>

    <div class="rental-receipt">
      <div class="receipt-header">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <rect width="14" height="18" x="5" y="3" rx="2"/>
          <path d="M9 7h6M9 11h6M9 15h4"/>
        </svg>
        <span>Rental Receipt</span>
      </div>
      <div class="receipt-row"><span>Borrower</span><b>Ashley Mendoza</b></div>
      <div class="receipt-row"><span>Bike</span><b>Bike 102</b></div>
      <div class="receipt-row"><span>Duration</span><b>2 Hours</b></div>
      <div class="receipt-row"><span>Total Fee</span><b>₱120</b></div>
    </div>

    <div class="timer-display" id="timer">02:00:00</div>
    <div class="timer-label">Time Remaining</div>

    <button class="primary-btn" onclick="goTo('home')" style="width:100%">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <rect width="7" height="9" x="3" y="3" rx="1"/>
        <rect width="7" height="5" x="14" y="3" rx="1"/>
        <rect width="7" height="9" x="14" y="12" rx="1"/>
        <rect width="7" height="5" x="3" y="16" rx="1"/>
      </svg>
      Return to Home
    </button>
  </div>
</section>
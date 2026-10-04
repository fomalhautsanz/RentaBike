{{-- QR SCANNER SCREEN --}}
<section class="screen" id="scanner">
  <div class="page-header">
    <button class="back-btn" onclick="goTo('home')" aria-label="Back to home">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
    </button>
    <h2>Scan QR Code</h2>
  </div>

  <div class="scanner-wrap">
    <div class="scanner-frame-outer">
      <div class="scanner-corner tl"></div>
      <div class="scanner-corner tr"></div>
      <div class="scanner-corner bl"></div>
      <div class="scanner-corner br"></div>
      <div class="scanner-inner">
        <div class="scan-beam"></div>
      </div>
    </div>
    <p class="scanner-hint">Position the bike QR code inside the frame to continue.</p>
    <button class="scan-simulate-btn" onclick="simulateScan()">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect width="5" height="5" x="3" y="3" rx=".5"/><rect width="5" height="5" x="16" y="3" rx=".5"/><rect width="5" height="5" x="3" y="16" rx=".5"/><path d="M21 16h-3a2 2 0 0 0-2 2v3"/></svg>
      Simulate QR Scan
    </button>
  </div>
</section>

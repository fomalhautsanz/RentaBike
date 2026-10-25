{{-- QR SCANNER SCREEN --}}
{{-- html5-qrcode: pinned version. Kung dili ma-load (offline), naa gihapon ang manual entry sa ubos. --}}
<script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<style>
  /* Scanner-only styles; gi-butang diri para dili ma-gunitan ang shared staff layout */
  #qr-reader{width:100%;height:100%;border:0!important;padding:0!important}
  #qr-reader video{width:100%!important;height:100%!important;object-fit:cover;display:block}
  .scanner-inner.is-off .scan-beam{display:none}
  .scanner-hint.is-error{color:#dc2626}
  .scanner-retry{display:none;margin:-12px 0 20px;background:none;border:1px solid var(--green-600);color:var(--green-700);border-radius:var(--radius-md);padding:10px 16px;font-size:14px;font-weight:600;font-family:'DM Sans',sans-serif;cursor:pointer}
  .scanner-retry.show{display:inline-block}
  .scanner-manual{width:100%;display:flex;flex-direction:column;gap:10px}
  .scanner-manual-label{font-size:13px;color:var(--gray-500);text-align:center}

  /* Session sheet: mo-gawas kung rented nga bike ang na-scan (end / extend) */
  .ss-backdrop{position:fixed;inset:0;background:rgba(0,0,0,.4);display:none;align-items:flex-end;justify-content:center;z-index:250}
  .ss-backdrop.show{display:flex}
  .ss-card{width:100%;max-width:480px;max-height:90vh;overflow-y:auto;background:var(--white);border-radius:24px 24px 0 0;padding:20px 20px 28px}
  .ss-head{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;margin-bottom:8px}
  .ss-title{font-size:17px;font-weight:700;color:var(--gray-900)}
  .ss-sub{font-size:12px;color:var(--gray-500);margin-top:2px}
  .ss-close{width:32px;height:32px;border:0;border-radius:50%;background:var(--gray-100);cursor:pointer;font-size:14px;color:var(--gray-600);flex-shrink:0}
  .ss-note{font-size:13px;color:var(--gray-600);background:var(--yellow-50);border-radius:var(--radius-md);padding:10px 12px;margin:8px 0}
  .ss-over{color:var(--red-600)!important}
  .ss-extend-panel{display:none;margin-top:14px}
  .ss-extend-panel.show{display:block}
  .ss-extend-label{font-size:12px;font-weight:700;color:var(--gray-500);text-transform:uppercase;letter-spacing:.4px;margin-bottom:8px}
  .ss-chips{display:grid;grid-template-columns:repeat(3,1fr);gap:8px}
  .ss-chip{border:1.5px solid var(--gray-200);background:var(--white);border-radius:var(--radius-md);padding:10px 4px;cursor:pointer;font-family:'DM Sans',sans-serif;text-align:center}
  .ss-chip b{display:block;font-size:13px;color:var(--gray-900)}
  .ss-chip span{display:block;font-size:12px;color:var(--gray-600);margin-top:2px}
  .ss-chip.active{border-color:var(--green-600);background:var(--green-50)}
  .ss-actions{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:16px}
  .ss-actions.single{grid-template-columns:1fr}
  .ss-btn{border-radius:var(--radius-md);padding:14px;font-size:15px;font-weight:600;font-family:'DM Sans',sans-serif;cursor:pointer;border:1.5px solid transparent}
  .ss-btn:disabled{opacity:.55;cursor:default}
  .ss-btn-outline{background:var(--white);border-color:var(--green-600);color:var(--green-700)}
  .ss-btn-danger{background:var(--red-600);color:#fff}
  .ss-btn-primary{background:linear-gradient(135deg,var(--green-600),var(--green-700));color:#fff}
  .ss-confirm{display:none;width:100%;margin-top:10px}
  .ss-confirm.show{display:block}
</style>
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
        {{-- Diri i-render sa html5-qrcode ang camera video --}}
        <div id="qr-reader"></div>
        <div class="scan-beam"></div>
      </div>
    </div>
    <p class="scanner-hint" id="scanner-status">Position the bike QR code inside the frame to continue.</p>
    <button type="button" class="scanner-retry" id="scanner-retry" onclick="startCameraScan()">Try camera again</button>

    {{-- Manual fallback (para sa demo o kung dili mo-work ang camera) --}}
    <div class="scanner-manual">
      <div class="scanner-manual-label">Or enter the bike code manually</div>
      <input id="manual-code-input" class="form-input" type="text" maxlength="100"
             placeholder="Bike code (e.g. RB-004)" autocomplete="off" autocapitalize="characters"
             spellcheck="false" onkeydown="if (event.key === 'Enter') submitManualCode()">
      <button type="button" class="scan-simulate-btn" onclick="submitManualCode()">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect width="5" height="5" x="3" y="3" rx=".5"/><rect width="5" height="5" x="16" y="3" rx=".5"/><rect width="5" height="5" x="3" y="16" rx=".5"/><path d="M21 16h-3a2 2 0 0 0-2 2v3"/></svg>
        Check Bike Code
      </button>
    </div>
  </div>
</section>

{{-- SESSION SHEET: details sa rented bike + End Session / Extend (gi-render sa JS) --}}
<div class="ss-backdrop" id="session-sheet">
  <div class="ss-card" role="dialog" aria-modal="true" aria-labelledby="ss-title">
    <div class="ss-head">
      <div>
        <div class="ss-title" id="ss-title">Rented bike</div>
        <div class="ss-sub" id="ss-sub"></div>
      </div>
      <button type="button" class="ss-close" onclick="closeSessionSheet()" aria-label="Close">&#10005;</button>
    </div>
    <div id="ss-body"></div>
    <div class="ss-extend-panel" id="ss-extend-panel">
      <div class="ss-extend-label">Extend by</div>
      <div class="ss-chips" id="ss-chips"></div>
      <button type="button" class="ss-btn ss-btn-primary ss-confirm" id="ss-extend-confirm" onclick="confirmExtend()">Confirm Extension</button>
    </div>
    <div class="ss-actions" id="ss-actions">
      <button type="button" class="ss-btn ss-btn-outline" id="ss-extend-btn" onclick="toggleExtendPanel()">Extend</button>
      <button type="button" class="ss-btn ss-btn-danger" id="ss-end-btn" onclick="endSession()">End Session</button>
    </div>
  </div>
</div>
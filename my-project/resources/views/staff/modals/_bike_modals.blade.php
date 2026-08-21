{{-- BIKE MODALS BACKDROP --}}
<div class="modal-backdrop" id="modalBg" onclick="closeModalOutside(event)">
  <div class="modal-sheet">
    <div class="modal-handle"></div>
    <div id="modalContent"></div>
  </div>
</div>

<div class="confirm-backdrop" id="confirmBg" onclick="closeConfirmOutside(event)">
  <div class="confirm-dialog" role="dialog" aria-modal="true" aria-labelledby="confirmTitle">
    <div class="confirm-icon">!</div>
    <h3 id="confirmTitle">Change this bike record?</h3>
    <p id="confirmMessage">Do you want to report an issue for this bike?</p>
    <div class="confirm-actions">
      <button class="primary-btn outline" type="button" onclick="closeConfirm()">No</button>
      <button class="primary-btn" type="button" onclick="confirmAction()">Yes</button>
    </div>
  </div>
</div>

{{-- TOAST --}}
<div class="toast" id="toast">
  <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
    <polyline points="20 6 9 17 4 12"/>
  </svg>
  <span id="toastMsg">Done!</span>
</div>
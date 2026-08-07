{{-- BIKE MODALS BACKDROP --}}
<div class="modal-backdrop" id="modalBg" onclick="closeModalOutside(event)">
  <div class="modal-sheet">
    <div class="modal-handle"></div>
    <div id="modalContent"></div>
  </div>
</div>

{{-- TOAST --}}
<div class="toast" id="toast">
  <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
    <polyline points="20 6 9 17 4 12"/>
  </svg>
  <span id="toastMsg">Done!</span>
</div>
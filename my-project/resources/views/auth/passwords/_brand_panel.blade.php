<aside class="brand-panel">
  <div class="brand-head">
    <div class="brand-logo">
      <img src="{{ asset('images/system_logo.png') }}" alt="RentaBike Logo">
    </div>
    <div>
      <div class="brand-name">RentaBike</div>
      <div class="brand-tag">Admin Portal</div>
    </div>
  </div>

  <div>
    <div class="brand-title">{{ $heading }}</div>
    <p class="brand-copy">{{ $copy }}</p>
  </div>

  <ul class="brand-list">
    <li>
      <div class="dot"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg></div>
      <div><strong>Code sent by email</strong><span>A six-digit code goes to the admin account's email address.</span></div>
    </li>
    <li>
      <div class="dot"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg></div>
      <div><strong>Valid for 10 minutes</strong><span>Request a new code if yours has expired.</span></div>
    </li>
    <li>
      <div class="dot"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
      <div><strong>Five attempts maximum</strong><span>The code is locked after five incorrect entries. Never share it with anyone.</span></div>
    </li>
  </ul>

  <div class="brand-foot">© {{ date('Y') }} RentaBike. All rights reserved.</div>
</aside>

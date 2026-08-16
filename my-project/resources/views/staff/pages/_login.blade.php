{{-- LOGIN SCREEN --}}

<section class="screen active" id="login">
  <div class="login-bg">
    <div class="login-logo-wrap">
      <img src="{{ asset('images/system_logo.png') }}" alt="Logo" class="login-logo-img">
      <div class="login-brand">RentaBike</div>
      <div class="login-sub">Staff Portal · Sign in to continue</div>
    </div>
    <div class="login-card">
      <div class="login-error" id="loginError">Incorrect email or password. Please try again.</div>

      <div class="login-field-group">
        <label class="login-field-label">Email Address</label>
        <div class="login-input-wrap">
          <span class="login-input-icon">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <rect width="20" height="16" x="2" y="4" rx="2"/>
              <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
            </svg>
          </span>
          <input class="login-input" type="email" placeholder="staff@rentabike.com" id="loginEmail"
            onkeydown="if(event.key==='Enter') doLogin()">
        </div>
      </div>

      <div class="login-field-group">
        <label class="login-field-label">Password</label>
        <div class="login-input-wrap">
          <span class="login-input-icon">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <rect width="18" height="11" x="3" y="11" rx="2"/>
              <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            </svg>
          </span>
          <input class="login-input" type="password" placeholder="Enter your password" id="loginPw"
            onkeydown="if(event.key==='Enter') doLogin()">
          <button class="login-pw-toggle" type="button" onclick="togglePw()">
            <svg id="pwEyeIcon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"/>
              <circle cx="12" cy="12" r="3"/>
            </svg>
          </button>
        </div>
      </div>

      <div class="login-options">
        <label class="login-remember">
          <input type="checkbox" id="rememberMe"> Remember me
        </label>
        <a href="#" class="login-forgot">Forgot password?</a>
      </div>

      <button class="login-btn" onclick="doLogin()">Sign In</button>
    </div>

    <div class="login-footer">
      &copy; 2026 RentaBike. All rights reserved.<br>
      Need help? Contact <a class="text-[#01A63E]! font-medium hover:underline" href="mailto:support@bikerental.com">support@bikerental.com</a>.
    </div>
  </div>
</section>
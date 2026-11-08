<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset Password — RentaBike Admin</title>
@vite(['resources/css/app.css', 'resources/js/app.js'])
@include('auth.passwords._styles')
</head>
<body>

<div class="reset-shell">
  @include('auth.passwords._brand_panel', [
    'heading' => 'Set a new admin password',
    'copy' => 'Use the code from your inbox to confirm it\'s you, then choose a strong password you haven\'t used before.',
  ])

  <main class="form-panel">
    <div class="step-pill">Step 2 of 2 · Choose new password</div>
    <h1 class="form-title">Reset your password</h1>
    <p class="form-sub">Enter the six-digit code sent to your email and choose a new password. The code expires after 10 minutes and allows a maximum of five incorrect attempts.</p>

    @if (session('status'))
      <div class="alert-success" role="status">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
      <div class="alert-error" role="alert">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('admin.password.update') }}">
      @csrf
      <div class="form-group">
        <label class="form-label" for="email">Admin email address</label>
        <div class="input-icon-wrap">
          <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
          <input class="form-input" type="email" name="email" id="email" value="{{ old('email') }}" placeholder="admin@rentabike.com" autocomplete="email" maxlength="150" required>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="otp">Verification code</label>
        <div class="input-icon-wrap">
          <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>
          <input class="form-input otp" type="text" name="otp" id="otp" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="••••••" autocomplete="one-time-code" required @if (old('email')) autofocus @endif>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="password">New password</label>
          <div class="input-icon-wrap">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            <input class="form-input has-eye" type="password" name="password" id="password" minlength="9" autocomplete="new-password" required>
            <button type="button" class="eye-btn" data-toggle="password" aria-label="Show password"></button>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="password_confirmation">Confirm new password</label>
          <div class="input-icon-wrap">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            <input class="form-input has-eye" type="password" name="password_confirmation" id="password_confirmation" minlength="9" autocomplete="new-password" required>
            <button type="button" class="eye-btn" data-toggle="password_confirmation" aria-label="Show password"></button>
          </div>
        </div>
      </div>
      <p class="form-hint" style="margin:-6px 0 12px">Use at least 9 characters.</p>

      <button class="submit-btn" type="submit">Update password</button>
    </form>

    <div class="form-links">
      <a class="form-link" href="{{ route('login') }}">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m15 18-6-6 6-6"/></svg>
        Back to admin sign in
      </a>
      <a class="form-link" href="{{ route('admin.password.request') }}">Request another code</a>
    </div>
  </main>
</div>

<div class="page-help">
  Need help? Contact <a href="mailto:support@bikerental.com">support@bikerental.com</a>.
</div>

<script>
(function () {
  const eyeOpen = '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>';
  const eyeOff = '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';

  document.querySelectorAll('.eye-btn[data-toggle]').forEach(function (btn) {
    const input = document.getElementById(btn.dataset.toggle);
    btn.innerHTML = eyeOpen;
    btn.addEventListener('click', function () {
      const show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      btn.innerHTML = show ? eyeOff : eyeOpen;
      btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
    });
  });

  // Keep the code field digits-only.
  const otp = document.getElementById('otp');
  otp.addEventListener('input', function () {
    otp.value = otp.value.replace(/\D/g, '').slice(0, 6);
  });
})();
</script>
</body>
</html>

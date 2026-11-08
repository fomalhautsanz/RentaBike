<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot Password — RentaBike Admin</title>
@vite(['resources/css/app.css', 'resources/js/app.js'])
@include('auth.passwords._styles')
</head>
<body>

<div class="reset-shell">
  @include('auth.passwords._brand_panel', [
    'heading' => 'Locked out of the Admin Portal?',
    'copy' => 'Recover access to the dashboard in two quick steps: verify your email, then set a new password.',
  ])

  <main class="form-panel">
    <div class="step-pill">Step 1 of 2 · Verify email</div>
    <h1 class="form-title">Forgot your password?</h1>
    <p class="form-sub">Enter the email address linked to your admin account. If it matches, we'll send a six-digit verification code.</p>

    @if ($errors->any())
      <div class="alert-error" role="alert">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('admin.password.email') }}">
      @csrf
      <div class="form-group">
        <label class="form-label" for="email">Admin email address</label>
        <div class="input-icon-wrap">
          <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
          <input class="form-input" type="email" name="email" id="email" value="{{ old('email') }}" placeholder="admin@rentabike.com" autocomplete="email" maxlength="150" required autofocus>
        </div>
      </div>

      <button class="submit-btn" type="submit">Send verification code</button>
    </form>

    <div class="form-links">
      <a class="form-link" href="{{ route('login') }}">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m15 18-6-6 6-6"/></svg>
        Back to admin sign in
      </a>
      <a class="form-link" href="{{ route('admin.password.reset') }}">I already have a code</a>
    </div>
  </main>
</div>

<div class="page-help">
  Need help? Contact <a href="mailto:support@bikerental.com">support@bikerental.com</a>.
</div>

</body>
</html>

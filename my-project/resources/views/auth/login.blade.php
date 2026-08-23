<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>RentaBike — Login</title>


@vite(['resources/css/app.css', 'resources/js/app.js'])

<style>
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:linear-gradient(135deg,#f0fdf4,#dcfce7);min-height:100vh;display:flex;align-items:center;justify-content:center;flex-direction:column;gap:20px}
  .login-card{background:#fff;border-radius:20px;padding:40px;width:100%;max-width:420px;box-shadow:0 20px 60px rgba(0,0,0,.1)}
  .login-logo{text-align:center;margin-bottom:32px}
  .login-logo-icon{width:72px;height:72px;display:inline-flex;align-items:center;justify-content:center;margin:0 auto 16px}
  .login-logo-icon img{width:72px;height:72px;object-fit:contain}
  .login-title{font-size:28px;font-weight:700;color:#111827;letter-spacing:-.5px}
  .login-sub{font-size:14px;color:#6b7280;margin-top:8px}
  .login-form{margin-top:28px}
  .form-group{margin-bottom:16px}
  .form-label{display:block;font-size:13px;font-weight:500;color:#374151;margin-bottom:6px}
  .form-input{width:100%;padding:11px 12px 11px 40px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;color:#111827;outline:none;font-family:inherit}
  .form-input:focus{border-color:#16a34a;box-shadow:0 0 0 3px rgba(22,163,74,.1)}
  .input-icon-wrap{position:relative;display:flex;align-items:center}
  .input-icon-wrap > svg{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#9ca3af;flex-shrink:0;pointer-events:none;width:16px;height:16px}
  .eye-btn{position:absolute;right:8px;top:0;bottom:0;margin:auto;width:28px;height:28px;background:transparent;border:none;outline:none;appearance:none;-webkit-appearance:none;cursor:pointer;color:#9ca3af;padding:0;display:flex;align-items:center;justify-content:center;border-radius:6px}
  .eye-btn:hover{background:#f3f4f6;color:#6b7280}
  .eye-btn svg{display:block;width:16px;height:16px}
  .login-extras{display:flex;align-items:center;justify-content:space-between;margin:20px 0 24px;gap:12px}
  .login-extras label{display:flex;align-items:center;gap:8px;font-size:13px;color:#6b7280;cursor:pointer}
  .login-link{font-size:13px;color:#16a34a;text-decoration:none;font-weight:500}
  .login-link:hover{color:#15803d}
  .login-submit{width:100%;padding:12px;background:linear-gradient(135deg,#22c55e,#16a34a);color:#fff;border:none;border-radius:10px;font-size:15px;font-weight:600;cursor:pointer;transition:all .2s}
  .login-submit:hover{box-shadow:0 4px 20px rgba(22,163,74,.3);transform:translateY(-1px)}
  .login-submit:active{transform:translateY(0)}
  .login-footer{margin-top:28px;text-align:center;font-size:13px;color:#9ca3af}
  .login-help{text-align:center;font-size:13px;color:#6b7280}
  .alert-error{background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:16px}
  #pw-input{padding-right:40px}
</style>
</head>
<body>

<div class="login-card">
  <div class="login-logo">
    <div class="login-logo-icon">
      <img src="{{ asset('images/system_logo.png') }}" alt="RentaBike Logo">
    </div>
    <div class="login-title">RentaBike</div>
    <div class="login-sub">Admin Portal · Sign in to continue</div>
  </div>

  @if ($errors->any())
    <div class="alert-error">
        {{ $errors->first() }}
    </div>
  @endif

  <form class="login-form" method="POST" action="{{ route('admin.login.submit') }}">
    @csrf

    <div class="form-group">
      <label class="form-label">Email Address</label>
      <div class="input-icon-wrap">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
        <input type="email" name="email" class="form-input" placeholder="admin@rentabike.com"
               value="{{ old('email') }}" required autofocus>
      </div>
    </div>

    <div class="form-group">
      <label class="form-label">Password</label>
      <div class="input-icon-wrap">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        <input type="password" name="password" id="pw-input" class="form-input" placeholder="••••••••" required>
        <button type="button" class="eye-btn" onclick="togglePw()">
          <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" id="pwEyeIcon">
            <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/>
          </svg>
        </button>
      </div>
    </div>

    <div class="login-extras">
      <label>
        <input type="checkbox" name="remember"> Remember me
      </label>
      <a href="#" class="login-link">Forgot password?</a>
    </div>

    <button type="submit" class="login-submit">Sign In to Admin Portal</button>

    <div class="login-footer">
      © {{ date('Y') }} RentaBike. All rights reserved.
    </div>
  </form>
</div>

<div class="login-help">
  Need help? Contact <a class="text-[#01A63E] font-medium hover:underline" href="mailto:support@bikerental.com">support@bikerental.com</a>.
</div>

<script>
function togglePw() {
  const inp = document.getElementById('pw-input');
  const icon = document.getElementById('pwEyeIcon');
  if (inp.type === 'password') {
    inp.type = 'text';
    icon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/>';
  } else {
    inp.type = 'password';
    icon.innerHTML = '<path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/>';
  }
}
</script>
</body>
</html>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Forgot Password — RentaBike Staff</title>
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  <style>
    *{box-sizing:border-box}body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:linear-gradient(135deg,#f0fdf4,#dcfce7);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}.card{background:#fff;border-radius:20px;padding:36px;width:100%;max-width:420px;box-shadow:0 20px 60px rgba(0,0,0,.1)}h1{font-size:25px;color:#111827;margin:0 0 8px}p{font-size:14px;line-height:1.55;color:#6b7280;margin:0 0 24px}.label{display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:7px}.input{width:100%;padding:12px;border:1px solid #d1d5db;border-radius:8px;font-size:15px;margin-bottom:16px}.button{width:100%;padding:12px;background:#16a34a;color:#fff;border:0;border-radius:9px;font-size:15px;font-weight:600;cursor:pointer}.link{display:block;text-align:center;margin-top:18px;color:#15803d;text-decoration:none;font-size:14px}.message{padding:10px 12px;border-radius:8px;background:#f0fdf4;color:#166534;font-size:13px;margin-bottom:16px}.error{padding:10px 12px;border-radius:8px;background:#fef2f2;color:#b91c1c;font-size:13px;margin-bottom:16px}
  </style>
</head>
<body>
  <main class="card">
    <h1>Forgot your password?</h1>
    <p>Enter your staff account email. If it matches an active account, we’ll send a six-digit verification code.</p>

    @if ($errors->any())
      <div class="error" role="alert">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('staff.password.email') }}">
      @csrf
      <label class="label" for="email">Staff email</label>
      <input class="input" type="email" name="email" id="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
      <button class="button" type="submit">Send verification code</button>
    </form>

    <a class="link" href="{{ route('staff.login') }}">Back to staff sign in</a>
  </main>
</body>
</html>

<style>
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:linear-gradient(135deg,#f0fdf4,#dcfce7);min-height:100vh;display:flex;align-items:center;justify-content:center;flex-direction:column;gap:20px;padding:24px 16px}

  /* Two-column desktop shell */
  .reset-shell{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1.15fr);width:100%;max-width:960px;background:#fff;border-radius:20px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.1)}

  /* Brand panel (left) */
  .brand-panel{position:relative;background:linear-gradient(135deg,#22c55e,#16a34a);color:#fff;padding:44px 40px;display:flex;flex-direction:column;gap:28px;overflow:hidden}
  .brand-panel::after{content:"";position:absolute;width:320px;height:320px;border-radius:50%;background:rgba(255,255,255,.08);right:-120px;bottom:-120px;pointer-events:none}
  .brand-head{display:flex;align-items:center;gap:12px}
  .brand-logo{width:48px;height:48px;border-radius:12px;background:#fff;display:flex;align-items:center;justify-content:center;flex-shrink:0}
  .brand-logo img{width:36px;height:36px;object-fit:contain}
  .brand-name{font-size:20px;font-weight:700;letter-spacing:-.3px}
  .brand-tag{font-size:12px;opacity:.85;margin-top:2px}
  .brand-title{font-size:26px;font-weight:700;line-height:1.25;letter-spacing:-.4px}
  .brand-copy{font-size:14px;line-height:1.6;opacity:.9;margin-top:10px}
  .brand-list{list-style:none;display:flex;flex-direction:column;gap:14px;position:relative;z-index:1}
  .brand-list li{display:flex;gap:12px;align-items:flex-start;font-size:13.5px;line-height:1.5}
  .brand-list .dot{width:28px;height:28px;border-radius:8px;background:rgba(255,255,255,.18);display:flex;align-items:center;justify-content:center;flex-shrink:0}
  .brand-list .dot svg{width:15px;height:15px}
  .brand-list strong{display:block;font-weight:600}
  .brand-list span{opacity:.85}
  .brand-foot{margin-top:auto;font-size:12px;opacity:.75;position:relative;z-index:1}

  /* Form panel (right) */
  .form-panel{padding:44px 48px}
  .step-pill{display:inline-flex;align-items:center;gap:6px;background:#f0fdf4;color:#15803d;font-size:12px;font-weight:600;padding:5px 10px;border-radius:999px;margin-bottom:16px}
  .form-title{font-size:26px;font-weight:700;color:#111827;letter-spacing:-.4px}
  .form-sub{font-size:14px;line-height:1.55;color:#6b7280;margin:8px 0 24px}
  .form-group{margin-bottom:16px}
  .form-label{display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:7px}
  .form-row{display:grid;grid-template-columns:1fr 1fr;gap:14px}
  .form-input{width:100%;padding:12px 12px 12px 40px;border:1px solid #d1d5db;border-radius:8px;font-size:15px;color:#111827;outline:none;font-family:inherit;background:#fff}
  .form-input:focus{border-color:#16a34a;box-shadow:0 0 0 3px rgba(22,163,74,.1)}
  .form-input.has-eye{padding-right:42px}
  .form-input.otp{letter-spacing:8px;font-weight:600;font-variant-numeric:tabular-nums}
  .form-hint{font-size:12px;color:#9ca3af;margin-top:6px}
  .input-icon-wrap{position:relative;display:flex;align-items:center}
  .input-icon-wrap > svg{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#9ca3af;pointer-events:none;width:16px;height:16px}
  .eye-btn{position:absolute;right:8px;top:0;bottom:0;margin:auto;width:28px;height:28px;background:transparent;border:none;cursor:pointer;color:#9ca3af;padding:0;display:flex;align-items:center;justify-content:center;border-radius:6px}
  .eye-btn:hover{background:#f3f4f6;color:#6b7280}
  .eye-btn svg{display:block;width:16px;height:16px}
  .submit-btn{width:100%;padding:13px;background:linear-gradient(135deg,#22c55e,#16a34a);color:#fff;border:none;border-radius:10px;font-size:15px;font-weight:600;cursor:pointer;transition:all .2s;margin-top:8px}
  .submit-btn:hover{box-shadow:0 4px 20px rgba(22,163,74,.3);transform:translateY(-1px)}
  .submit-btn:active{transform:translateY(0)}
  .alert-success{background:#f0fdf4;border:1px solid #bbf7d0;color:#166534;padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:18px;line-height:1.5}
  .alert-error{background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:18px;line-height:1.5}
  .form-links{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-top:22px;flex-wrap:wrap}
  .form-link{font-size:14px;color:#15803d;text-decoration:none;font-weight:500;display:inline-flex;align-items:center;gap:6px}
  .form-link:hover{color:#166534;text-decoration:underline}
  .form-link svg{width:14px;height:14px}
  .page-help{text-align:center;font-size:13px;color:#6b7280}
  .page-help a{color:#01A63E;font-weight:500;text-decoration:none}
  .page-help a:hover{text-decoration:underline}

  @media (max-width: 820px){
    .reset-shell{grid-template-columns:1fr;max-width:480px}
    .brand-panel{padding:28px 28px 24px;gap:16px}
    .brand-title{font-size:20px}
    .brand-list,.brand-foot{display:none}
    .form-panel{padding:32px 28px}
  }
  @media (max-width: 480px){
    .form-row{grid-template-columns:1fr;gap:0}
  }
</style>

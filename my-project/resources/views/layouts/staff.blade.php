<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>RentaBike · Staff</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
@vite(['resources/css/app.css', 'resources/js/app.js'])

<style>

*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
html{font-size:15px}
body{background:#f9fafb;color:#111827;font-family:'DM Sans',sans-serif;-webkit-font-smoothing:antialiased;}

:root{
  --green-50:#f0fdf4;--green-100:#dcfce7;--green-500:#22c55e;--green-600:#16a34a;--green-700:#15803d;--green-900:#14532d;
  --blue-50:#eff6ff;--blue-600:#2563eb;--yellow-50:#fefce8;--yellow-600:#ca8a04;
  --orange-50:#fff7ed;--orange-600:#ea580c;--red-50:#fef2f2;--red-600:#dc2626;
  --purple-50:#faf5ff;--purple-600:#9333ea;
  --gray-50:#f9fafb;--gray-100:#f3f4f6;--gray-200:#e5e7eb;--gray-400:#9ca3af;--gray-500:#6b7280;--gray-600:#4b5563;--gray-700:#374151;--gray-900:#111827;
  --white:#ffffff;
  --shadow-sm:0 1px 3px rgba(0,0,0,.08),0 1px 2px rgba(0,0,0,.04);
  --shadow-md:0 4px 14px rgba(0,0,0,.07);
  --shadow-lg:0 8px 24px rgba(0,0,0,.10);
  --radius-sm:8px;--radius-md:12px;--radius-lg:16px;--radius-xl:20px;
}

/* ── DESKTOP WARNING ── */
#desktop-warning{display:none}

/* ── APP SHELL ── */
.app{max-width:430px;margin:0 auto;min-height:100vh;position:relative;background:#f9fafb;}

/* ── SCREENS ── */
.screen{display:none;padding:0 0 100px;}
.screen.active{display:block}

/* ── TOPBAR ── */
.topbar{background:var(--white);border-bottom:1px solid var(--gray-200);padding:16px 20px 14px;display:flex;justify-content:space-between;align-items:center;position:sticky;top:0;z-index:50;}
.topbar-left{display:flex;align-items:center;gap:10px}
.topbar-logo{width:36px;height:36px;background:linear-gradient(135deg,var(--green-500),var(--green-700));border-radius:10px;display:flex;align-items:center;justify-content:center;}
.topbar-logo svg{color:#fff}
.topbar-brand{font-size:17px;font-weight:700;color:var(--gray-900);letter-spacing:-.3px}
.topbar-sub{font-size:12px;color:var(--gray-500);margin-top:1px}
.time-chip{background:var(--white);border:1px solid var(--gray-200);padding:6px 12px;border-radius:999px;font-family:'Space Mono',monospace;font-size:12px;font-weight:700;color:var(--gray-700);box-shadow:var(--shadow-sm);}

/* ── PAGE HEADER (back button screens) ── */
.page-header{background:var(--white);border-bottom:1px solid var(--gray-200);padding:14px 20px;display:flex;align-items:center;gap:12px;position:sticky;top:0;z-index:50;}
.back-btn{width:36px;height:36px;border:1px solid var(--gray-200);border-radius:var(--radius-sm);background:var(--white);display:flex;align-items:center;justify-content:center;cursor:pointer;box-shadow:var(--shadow-sm);flex-shrink:0;}
.back-btn svg{color:var(--gray-600);width:18px;height:18px}
.page-header h2{font-size:17px;font-weight:700;color:var(--gray-900)}

/* ── CONTENT WRAPPER ── */
.content{padding:20px}

/* ── STAT CARDS ── */
.stat-row{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:20px}
.stat-card{background:var(--white);border:1px solid var(--gray-200);border-radius:var(--radius-lg);padding:16px;box-shadow:var(--shadow-sm);}
.stat-card-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:10px}
.stat-icon{width:36px;height:36px;border-radius:var(--radius-sm);display:flex;align-items:center;justify-content:center;}
.stat-icon svg{width:18px;height:18px}
.stat-change{font-size:11px;font-weight:600;padding:2px 7px;border-radius:999px;}
.stat-change.up{background:var(--green-50);color:var(--green-700)}
.stat-change.neutral{background:var(--gray-100);color:var(--gray-600)}
.stat-value{font-size:26px;font-weight:700;font-family:'Space Mono',monospace;color:var(--gray-900);line-height:1;margin-bottom:4px}
.stat-label{font-size:11px;font-weight:600;color:var(--gray-500);text-transform:uppercase;letter-spacing:.5px}
.stat-track{height:4px;background:var(--gray-100);border-radius:999px;margin-top:10px;overflow:hidden}
.stat-fill-green{height:100%;border-radius:999px;background:var(--green-500)}
.stat-fill-blue{height:100%;border-radius:999px;background:#3b82f6}
.stat-fill-orange{height:100%;border-radius:999px;background:#f97316}

/* ── SECTION TITLE ── */
.section-title{display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;}
.section-title h3{font-size:12px;font-weight:700;color:var(--gray-500);text-transform:uppercase;letter-spacing:.7px}
.section-title a{font-size:12px;font-weight:600;color:var(--green-600);text-decoration:none;cursor:pointer}

/* ── BIKE LIST ── */
.bike-list{display:flex;flex-direction:column;gap:10px}
.bike-card{background:var(--white);border:1px solid var(--gray-200);border-radius:var(--radius-lg);padding:14px 16px;display:flex;align-items:center;gap:12px;cursor:pointer;box-shadow:var(--shadow-sm);transition:all .15s ease;text-decoration:none;}
.bike-card:active{transform:scale(.98);box-shadow:none}
.bike-icon{width:44px;height:44px;border-radius:var(--radius-md);display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.bike-icon svg{width:22px;height:22px}
.bike-icon.green{background:var(--green-50)}.bike-icon.green svg{color:var(--green-600)}
.bike-icon.blue{background:var(--blue-50)}.bike-icon.blue svg{color:var(--blue-600)}
.bike-icon.orange{background:var(--orange-50)}.bike-icon.orange svg{color:var(--orange-600)}
.bike-meta{flex:1;min-width:0}
.bike-meta h4{font-size:14px;font-weight:600;color:var(--gray-900);margin-bottom:2px}
.bike-meta p{font-size:12px;color:var(--gray-500);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.bike-card-arrow{color:var(--gray-300);flex-shrink:0}.bike-card-arrow svg{width:16px;height:16px}

/* ── BADGES ── */
.badge{display:inline-flex;align-items:center;gap:4px;padding:4px 10px;border-radius:999px;font-size:11px;font-weight:600;white-space:nowrap;flex-shrink:0;}
.badge-green{background:var(--green-50);color:var(--green-700)}
.badge-blue{background:var(--blue-50);color:var(--blue-600)}
.badge-orange{background:var(--orange-50);color:var(--orange-600)}
.badge-yellow{background:var(--yellow-50);color:var(--yellow-600)}
.badge-red{background:var(--red-50);color:var(--red-600)}
.badge-gray{background:var(--gray-100);color:var(--gray-600)}
.badge-purple{background:var(--purple-50);color:var(--purple-600)}
.badge-dot{width:6px;height:6px;border-radius:50%}
.badge-dot-green{background:var(--green-600)}
.badge-dot-blue{background:var(--blue-600)}
.badge-dot-orange{background:var(--orange-600)}

/* ── BOTTOM NAV ── */
.bottom-nav{position:fixed;bottom:0;left:50%;transform:translateX(-50%);width:100%;max-width:430px;background:var(--white);border-top:1px solid var(--gray-200);display:flex;align-items:flex-end;padding:10px 8px 18px;box-shadow:0 -4px 20px rgba(0,0,0,.06);z-index:100;}
.nav-btn{flex:1;background:none;border:none;display:flex;flex-direction:column;align-items:center;gap:3px;font-size:11px;font-weight:600;color:var(--gray-400);cursor:pointer;padding:2px 0;font-family:'DM Sans',sans-serif;transition:color .15s;}
.nav-btn svg{width:20px;height:20px}
.nav-btn.active{color:var(--green-700)}.nav-btn.active svg{color:var(--green-600)}
.nav-qr{position:relative;color:var(--gray-700)}.nav-qr span{color:var(--gray-700)}
.qr-pill{width:54px;height:54px;background:linear-gradient(135deg,var(--green-600),var(--green-900));border-radius:16px;display:flex;align-items:center;justify-content:center;margin-top:-18px;margin-bottom:2px;box-shadow:0 6px 18px rgba(22,163,74,.35);}
.qr-pill svg{color:#fff;width:24px;height:24px}

/* ── QR SCANNER ── */
.scanner-wrap{display:flex;flex-direction:column;align-items:center;padding:40px 24px 24px;}
.scanner-frame-outer{width:260px;height:260px;position:relative;margin-bottom:28px;}
.scanner-corner{position:absolute;width:28px;height:28px;border-color:var(--green-600);border-style:solid;border-width:0;}
.scanner-corner.tl{top:0;left:0;border-top-width:3px;border-left-width:3px;border-radius:6px 0 0 0}
.scanner-corner.tr{top:0;right:0;border-top-width:3px;border-right-width:3px;border-radius:0 6px 0 0}
.scanner-corner.bl{bottom:0;left:0;border-bottom-width:3px;border-left-width:3px;border-radius:0 0 0 6px}
.scanner-corner.br{bottom:0;right:0;border-bottom-width:3px;border-right-width:3px;border-radius:0 0 6px 0}
.scanner-inner{position:absolute;inset:0;background:rgba(240,253,244,.08);border-radius:4px;overflow:hidden;}
.scan-beam{position:absolute;left:10px;right:10px;height:2px;background:linear-gradient(90deg,transparent,var(--green-500),transparent);box-shadow:0 0 10px var(--green-500);animation:scanBeam 2.2s ease-in-out infinite;}
@keyframes scanBeam{0%{top:10px;opacity:0}10%{opacity:1}90%{opacity:1}100%{top:calc(100% - 12px);opacity:0}}
.scanner-hint{font-size:14px;color:var(--gray-500);margin-bottom:24px;text-align:center;line-height:1.5}
.scan-simulate-btn{width:100%;background:linear-gradient(135deg,var(--green-600),var(--green-700));color:#fff;border:none;border-radius:var(--radius-lg);padding:16px;font-size:15px;font-weight:600;font-family:'DM Sans',sans-serif;cursor:pointer;box-shadow:0 4px 14px rgba(22,163,74,.3);transition:all .15s;display:flex;align-items:center;justify-content:center;gap:8px;}
.scan-simulate-btn:active{transform:scale(.98);box-shadow:none}
.scan-simulate-btn svg{width:18px;height:18px}

/* ── INVENTORY ── */
.inv-category{background:var(--white);border:1px solid var(--gray-200);border-radius:var(--radius-lg);overflow:hidden;margin-bottom:12px;box-shadow:var(--shadow-sm);}
.inv-cat-header{padding:14px 16px;border-bottom:1px solid var(--gray-100);display:flex;align-items:center;gap:10px;}
.inv-cat-icon{width:34px;height:34px;border-radius:var(--radius-sm);display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0;}
.inv-cat-header h3{flex:1;font-size:14px;font-weight:700;color:var(--gray-900)}
.cat-count{font-size:11px;font-weight:600;color:var(--gray-500);background:var(--gray-100);padding:3px 9px;border-radius:999px;}
.inv-list{padding:4px 0}
.inv-row{display:flex;align-items:center;justify-content:space-between;padding:11px 16px;border-bottom:1px solid var(--gray-100);}
.inv-row:last-child{border-bottom:none}
.inv-row-id{font-size:13px;font-weight:600;color:var(--gray-900);font-family:'Space Mono',monospace}
.inv-row-name{font-size:12px;color:var(--gray-500);margin-top:1px}

/* ── REPORT ── */
.report-types{display:flex;flex-direction:column;gap:10px}
.report-card{background:var(--white);border:1px solid var(--gray-200);border-radius:var(--radius-lg);padding:16px;display:flex;align-items:center;gap:14px;cursor:pointer;box-shadow:var(--shadow-sm);transition:all .15s;}
.report-card:active{transform:scale(.98)}
.report-icon{width:46px;height:46px;border-radius:var(--radius-md);display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.report-icon svg{width:22px;height:22px}
.report-icon.orange{background:var(--orange-50)}.report-icon.orange svg{color:var(--orange-600)}
.report-icon.red{background:var(--red-50)}.report-icon.red svg{color:var(--red-600)}
.report-icon.blue{background:var(--blue-50)}.report-icon.blue svg{color:var(--blue-600)}
.report-body{flex:1}
.report-body h4{font-size:14px;font-weight:700;color:var(--gray-900);margin-bottom:2px}
.report-body p{font-size:12px;color:var(--gray-500)}
.report-chevron{color:var(--gray-300)}.report-chevron svg{width:18px;height:18px}

/* ── FORMS ── */
.form-wrap{padding:20px}
.form-card{background:var(--white);border:1px solid var(--gray-200);border-radius:var(--radius-xl);padding:20px;box-shadow:var(--shadow-sm);}
.form-group{margin-bottom:16px}
.form-label{display:block;margin-bottom:6px;font-size:11px;font-weight:700;color:var(--gray-600);text-transform:uppercase;letter-spacing:.6px;}
.form-input,.form-textarea,.form-select{width:100%;padding:12px 14px;border:1px solid var(--gray-200);border-radius:var(--radius-md);font-size:14px;font-family:'DM Sans',sans-serif;color:var(--gray-900);background:var(--gray-50);outline:none;resize:none;transition:border-color .15s,box-shadow .15s;}
.form-input:focus,.form-textarea:focus,.form-select:focus{border-color:var(--green-500);box-shadow:0 0 0 3px rgba(34,197,94,.1);background:#fff}
.upload-box{border:1.5px dashed var(--gray-200);border-radius:var(--radius-md);background:var(--gray-50);padding:20px;text-align:center;cursor:pointer;transition:border-color .15s;}
.upload-box:hover{border-color:var(--green-400)}
.upload-box svg{width:28px;height:28px;color:var(--gray-400);margin:0 auto 6px;display:block}
.upload-box p{font-size:12px;color:var(--gray-400);font-weight:500}
.upload-box input[type=file]{position:absolute;inset:0;opacity:0;cursor:pointer}
.upload-wrap{position:relative}

/* ── BUTTONS ── */
.primary-btn{width:100%;background:linear-gradient(135deg,var(--green-600),var(--green-700));color:#fff;border:none;border-radius:var(--radius-md);padding:14px;font-size:15px;font-weight:600;font-family:'DM Sans',sans-serif;cursor:pointer;margin-top:16px;box-shadow:0 4px 14px rgba(22,163,74,.25);display:flex;align-items:center;justify-content:center;gap:8px;transition:all .15s;}
.primary-btn:active{transform:scale(.98);box-shadow:none}
.primary-btn svg{width:18px;height:18px}
.primary-btn.outline{background:transparent;color:var(--gray-700);border:1px solid var(--gray-200);box-shadow:none}
.primary-btn.outline:active{background:var(--gray-50)}
.primary-btn.danger{background:linear-gradient(135deg,#ef4444,#dc2626);box-shadow:0 4px 14px rgba(239,68,68,.25)}

/* ── SUCCESS ── */
.success-wrap{display:flex;flex-direction:column;align-items:center;padding:60px 24px 40px;text-align:center;}
.success-anim{width:80px;height:80px;background:var(--green-100);border-radius:50%;display:flex;align-items:center;justify-content:center;margin-bottom:20px;animation:popIn .4s cubic-bezier(.175,.885,.32,1.275);}
@keyframes popIn{from{transform:scale(.5);opacity:0}to{transform:scale(1);opacity:1}}
.success-anim svg{width:40px;height:40px;color:var(--green-700)}
.success-wrap h2{font-size:22px;font-weight:700;color:var(--gray-900);margin-bottom:6px}
.success-wrap .success-sub{font-size:14px;color:var(--gray-500);margin-bottom:24px}
.rental-receipt{width:100%;background:var(--white);border:1px solid var(--gray-200);border-radius:var(--radius-xl);overflow:hidden;box-shadow:var(--shadow-sm);margin-bottom:24px;}
.receipt-header{background:var(--green-50);border-bottom:1px solid var(--green-100);padding:12px 18px;display:flex;align-items:center;gap:8px;}
.receipt-header span{font-size:12px;font-weight:700;color:var(--green-700);text-transform:uppercase;letter-spacing:.5px}
.receipt-header svg{width:14px;height:14px;color:var(--green-600)}
.receipt-row{display:flex;justify-content:space-between;align-items:center;padding:12px 18px;border-bottom:1px solid var(--gray-100);}
.receipt-row:last-child{border-bottom:none}
.receipt-row span{font-size:12px;color:var(--gray-500)}
.receipt-row b{font-size:14px;font-weight:600;color:var(--gray-900)}
.timer-display{font-family:'Space Mono',monospace;font-size:40px;font-weight:700;color:var(--gray-900);letter-spacing:2px;margin-bottom:8px}
.timer-label{font-size:11px;font-weight:600;color:var(--green-600);text-transform:uppercase;letter-spacing:.6px;margin-bottom:24px}

/* ── MODALS ── */
.modal-backdrop{position:fixed;inset:0;background:rgba(0,0,0,.3);display:none;align-items:flex-end;z-index:200;}
.modal-backdrop.open{display:flex}
.modal-sheet{width:100%;background:var(--white);border-radius:24px 24px 0 0;padding:20px 20px 36px;animation:slideUp .25s ease;max-height:85vh;overflow-y:auto;}
@keyframes slideUp{from{transform:translateY(100%)}to{transform:translateY(0)}}
.modal-handle{width:40px;height:4px;background:var(--gray-200);border-radius:999px;margin:0 auto 20px;}
.modal-bike-header{display:flex;align-items:center;gap:12px;padding-bottom:16px;border-bottom:1px solid var(--gray-100);margin-bottom:16px;}
.modal-bike-header .bike-icon{width:48px;height:48px;border-radius:var(--radius-md)}
.modal-bike-title{font-size:18px;font-weight:700;color:var(--gray-900);margin-bottom:2px}
.modal-detail-row{display:flex;justify-content:space-between;align-items:flex-start;padding:10px 0;border-bottom:1px solid var(--gray-100);}
.modal-detail-row:last-of-type{border-bottom:none}
.modal-detail-row .label{font-size:11px;font-weight:700;color:var(--gray-500);text-transform:uppercase;letter-spacing:.4px}
.modal-detail-row .value{font-size:14px;font-weight:600;color:var(--gray-900);text-align:right}
.modal-actions{display:flex;flex-direction:column;gap:10px;margin-top:8px}

/* ── ID PREVIEW ── */
.id-preview-box{background:var(--gray-50);border:1px solid var(--gray-200);border-radius:var(--radius-md);padding:28px;text-align:center;margin-top:10px;}
.id-preview-box svg{width:40px;height:40px;color:var(--gray-300);margin:0 auto 8px;display:block}
.id-preview-box p{font-size:12px;color:var(--gray-400)}

/* ── TOAST ── */
.toast{position:fixed;bottom:90px;left:50%;transform:translateX(-50%) translateY(20px);background:var(--gray-900);color:#fff;padding:10px 20px;border-radius:999px;font-size:13px;font-weight:600;opacity:0;pointer-events:none;transition:opacity .25s,transform .25s;white-space:nowrap;z-index:300;display:flex;align-items:center;gap:8px;box-shadow:0 8px 24px rgba(0,0,0,.2);}
.toast.show{opacity:1;transform:translateX(-50%) translateY(0)}
.toast svg{width:16px;height:16px;color:var(--green-400)}

/* ── LOGIN ── */
.login-bg{min-height:100vh;background:linear-gradient(160deg,#f0fdf4 0%,#dcfce7 40%,#f9fafb 100%);display:flex;flex-direction:column;align-items:center;justify-content:center;padding:32px 24px 40px;}
.login-logo-wrap{display:flex;flex-direction:column;align-items:center;margin-bottom:28px}
.login-logo-img{width:72px;height:72px;border-radius:20px;object-fit:cover;box-shadow:0 8px 28px rgba(22,163,74,.25);margin-bottom:12px}
.login-brand{font-size:22px;font-weight:800;color:#111827;letter-spacing:-.4px}
.login-sub{font-size:13px;color:#6b7280;font-weight:500;margin-top:2px}
.login-card{width:100%;background:#fff;border:1px solid #e5e7eb;border-radius:20px;padding:24px 20px 20px;box-shadow:0 4px 24px rgba(0,0,0,.07)}
.login-field-group{margin-bottom:14px}
.login-field-label{display:block;font-size:11px;font-weight:700;color:#4b5563;text-transform:uppercase;letter-spacing:.6px;margin-bottom:6px}
.login-input-wrap{position:relative}
.login-input-icon{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#9ca3af;pointer-events:none}
.login-input-icon svg{width:16px;height:16px}
.login-input{width:100%;padding:12px 12px 12px 38px;border:1px solid #e5e7eb;border-radius:10px;font-size:14px;font-family:'DM Sans',sans-serif;color:#111827;background:#f9fafb;outline:none;transition:border-color .15s,box-shadow .15s}
.login-input:focus{border-color:#22c55e;box-shadow:0 0 0 3px rgba(34,197,94,.12);background:#fff}
.login-pw-toggle{position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;padding:2px;color:#9ca3af}
.login-pw-toggle svg{width:16px;height:16px}
.login-options{display:flex;align-items:center;justify-content:space-between;margin:12px 0 18px}
.login-remember{display:flex;align-items:center;gap:7px;font-size:13px;color:#374151;cursor:pointer;user-select:none}
.login-remember input[type=checkbox]{width:15px;height:15px;accent-color:#16a34a;cursor:pointer}
.login-forgot{font-size:13px;color:#16a34a;font-weight:600;text-decoration:none}
.login-btn{width:100%;background:linear-gradient(135deg,#16a34a,#15803d);color:#fff;border:none;border-radius:10px;padding:14px;font-size:15px;font-weight:700;font-family:'DM Sans',sans-serif;cursor:pointer;box-shadow:0 4px 16px rgba(22,163,74,.3);transition:all .15s;letter-spacing:.2px}
.login-btn:active{transform:scale(.98);box-shadow:none}
.login-footer{margin-top:28px;font-size:12px;color:#9ca3af;text-align:center;line-height:1.7}
.login-footer a{color:#6b7280;text-decoration:none;font-weight:500}
.login-error{background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:10px 14px;font-size:13px;color:#dc2626;font-weight:500;margin-bottom:14px;display:none}
.login-error.show{display:block}

/* ── DESKTOP WARNING ── */
@media(min-width:640px){
  #desktop-warning{display:flex;justify-content:center;align-items:center;height:100vh;background:#f0fdf4;}
  .desktop-card{background:#fff;border:1px solid var(--gray-200);padding:40px 48px;border-radius:20px;text-align:center;box-shadow:0 8px 32px rgba(0,0,0,.08);}
  .desktop-card .logo{width:56px;height:56px;background:linear-gradient(135deg,var(--green-500),var(--green-700));border-radius:14px;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;}
  .desktop-card .logo svg{color:#fff;width:28px;height:28px}
  .desktop-card h1{font-size:20px;font-weight:700;margin-bottom:6px}
  .desktop-card p{color:var(--gray-500);font-size:14px}
  .app{display:none}
}

</style>
</head>
<body>

{{-- DESKTOP WARNING --}}
<div id="desktop-warning">
  <div class="desktop-card">
    <div class="logo">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <circle cx="5.5" cy="17.5" r="2.5"/><circle cx="18.5" cy="17.5" r="2.5"/>
        <path d="M15 6a1 1 0 1 0 2 0 1 1 0 0 0-2 0z"/>
        <path d="M3 17V7h4l4-4 4 4h2l1 4h1v6"/>
      </svg>
    </div>
    <h1>📱 Mobile Only</h1>
    <p>RentaBike Staff is designed for phones.<br>Please open on a mobile device.</p>
  </div>
</div>

{{-- APP SHELL --}}
<div class="app">
  @yield('content')
</div>

{{-- MODALS & TOAST --}}
@yield('modals')

{{-- SCRIPTS --}}
@yield('scripts')

</body>
</html>
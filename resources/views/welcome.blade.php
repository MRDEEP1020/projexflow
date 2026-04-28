<!DOCTYPE html>
<html lang="en" class="dark">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ProjexFlow — Project Management + Freelance Marketplace</title>
<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=inter:400,500,600&family=syne:600,700,800&family=dm-mono:400,500&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
--bg:#080c14;--bg2:#0d1520;--bg3:#111c2e;--surface:#0e1823;--surface2:#131f2f;
--border:#1a2d45;--border2:#254060;
--text:#dde6f0;--muted:#8da0b8;--dim:#506070;
--accent:#7EE8A2;--accent2:#00d97e;--accent-dim:rgba(126,232,162,.12);
--blue:#60a5fa;--purple:#a78bfa;--amber:#fbbf24;--red:#f87171;
}
html{scroll-behavior:smooth}
body{background:var(--bg);color:var(--text);font-family:'Inter',sans-serif;font-size:16px;line-height:1.6;overflow-x:hidden}

/* ── NAV ── */
nav{position:fixed;top:0;left:0;right:0;z-index:100;height:64px;display:flex;align-items:center;justify-content:space-between;padding:0 5vw;background:rgba(8,12,20,.85);backdrop-filter:blur(12px);border-bottom:1px solid rgba(26,45,69,.6)}
.nav-logo{display:flex;align-items:center;gap:10px}
.nav-logo svg{flex-shrink:0}
.nav-logo-text{font-family:'Syne',sans-serif;font-weight:800;font-size:18px;color:#fff;letter-spacing:-.3px}
.nav-links{display:flex;align-items:center;gap:6px}
.nav-links a{padding:7px 14px;border-radius:8px;font-size:13.5px;color:var(--muted);text-decoration:none;transition:color .2s,background .2s}
.nav-links a:hover{color:var(--text);background:rgba(255,255,255,.05)}
.nav-cta{display:flex;gap:8px;align-items:center}
.btn{display:inline-flex;align-items:center;gap:7px;padding:9px 20px;border-radius:10px;font-size:14px;font-weight:500;text-decoration:none;transition:all .2s;cursor:pointer;border:none}
.btn-ghost{background:transparent;color:var(--muted);border:1px solid var(--border2)}
.btn-ghost:hover{color:var(--text);border-color:var(--accent);background:var(--accent-dim)}
.btn-primary{background:var(--accent);color:#080c14;font-weight:700}
.btn-primary:hover{background:#9ef7b8;transform:translateY(-1px);box-shadow:0 6px 24px rgba(126,232,162,.25)}
.btn-lg{padding:13px 28px;font-size:15px;border-radius:12px}
.btn-outline{background:transparent;color:var(--text);border:1.5px solid var(--border2)}
.btn-outline:hover{border-color:var(--accent);color:var(--accent);background:var(--accent-dim)}

/* ── HERO ── */
.hero{min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:120px 5vw 80px;text-align:center;position:relative;overflow:hidden}
.hero-grid{position:absolute;inset:0;background-image:linear-gradient(rgba(126,232,162,.03) 1px,transparent 1px),linear-gradient(90deg,rgba(126,232,162,.03) 1px,transparent 1px);background-size:60px 60px;pointer-events:none}
.hero-glow{position:absolute;top:20%;left:50%;transform:translate(-50%,-50%);width:700px;height:400px;background:radial-gradient(ellipse,rgba(126,232,162,.07) 0%,transparent 70%);pointer-events:none}
.badge{display:inline-flex;align-items:center;gap:6px;padding:6px 14px;border-radius:99px;border:1px solid rgba(126,232,162,.25);background:rgba(126,232,162,.08);font-size:12.5px;color:var(--accent);font-family:'DM Mono',monospace;margin-bottom:28px}
.badge-dot{width:6px;height:6px;border-radius:50%;background:var(--accent);animation:pulse 2s infinite}
@keyframes pulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.5;transform:scale(.8)}}
h1{font-family:'Syne',sans-serif;font-weight:800;font-size:clamp(36px,6vw,72px);line-height:1.08;letter-spacing:-1.5px;color:#fff;max-width:900px;margin:0 auto 24px}
h1 .accent{color:var(--accent)}
h1 .dim{color:var(--muted)}
.hero-sub{font-size:clamp(15px,1.8vw,19px);color:var(--muted);max-width:580px;margin:0 auto 40px;line-height:1.7}
.hero-actions{display:flex;flex-wrap:wrap;gap:12px;justify-content:center;margin-bottom:60px}
.hero-stats{display:flex;flex-wrap:wrap;gap:0;justify-content:center;border:1px solid var(--border);border-radius:16px;overflow:hidden;background:var(--surface)}
.stat{padding:18px 32px;text-align:center;border-right:1px solid var(--border)}
.stat:last-child{border-right:none}
.stat-num{font-family:'Syne',sans-serif;font-weight:800;font-size:26px;color:#fff;display:block}
.stat-label{font-size:12px;color:var(--dim);font-family:'DM Mono',monospace;text-transform:uppercase;letter-spacing:.8px}

/* ── SECTION ── */
section{padding:100px 5vw}
.section-tag{font-family:'DM Mono',monospace;font-size:11.5px;text-transform:uppercase;letter-spacing:1.5px;color:var(--accent);margin-bottom:14px}
h2{font-family:'Syne',sans-serif;font-weight:800;font-size:clamp(28px,4vw,46px);letter-spacing:-.8px;color:#fff;line-height:1.12}
.section-sub{font-size:17px;color:var(--muted);max-width:540px;margin-top:12px;line-height:1.7}
.centered{text-align:center;align-items:center}
.centered .section-sub{margin-left:auto;margin-right:auto}

/* ── FEATURES GRID ── */
.features-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1px;background:var(--border);border:1px solid var(--border);border-radius:20px;overflow:hidden;margin-top:60px}
.feat{background:var(--bg2);padding:32px;transition:background .2s}
.feat:hover{background:var(--bg3)}
.feat-icon{width:44px;height:44px;border-radius:12px;border:1px solid var(--border2);display:flex;align-items:center;justify-content:center;margin-bottom:18px;background:var(--accent-dim)}
.feat h3{font-family:'Syne',sans-serif;font-weight:700;font-size:17px;color:#fff;margin-bottom:8px}
.feat p{font-size:14px;color:var(--muted);line-height:1.65}

/* ── SCREENSHOT MOCKUPS ── */
.screens-section{background:linear-gradient(180deg,var(--bg) 0%,var(--bg2) 50%,var(--bg) 100%)}
.screens-wrap{margin-top:60px;position:relative}
.screen-main{border:1px solid var(--border);border-radius:16px;overflow:hidden;background:var(--surface);max-width:100%}
.screen-bar{height:36px;background:#0a1420;display:flex;align-items:center;padding:0 14px;gap:8px;border-bottom:1px solid var(--border)}
.dot{width:10px;height:10px;border-radius:50%}
.screen-content{padding:0}

/* ── Kanban board mockup ── */
.kanban{display:flex;gap:12px;padding:20px;overflow-x:auto}
.kol{min-width:200px;flex:1}
.kol-head{font-size:11px;font-family:'DM Mono',monospace;text-transform:uppercase;letter-spacing:1px;color:var(--dim);padding:0 8px 10px;display:flex;align-items:center;justify-content:space-between}
.kol-head span{background:var(--surface2);border-radius:99px;padding:1px 8px;font-size:10px}
.kcard{background:var(--surface2);border:1px solid var(--border);border-radius:10px;padding:12px;margin-bottom:8px}
.kcard-title{font-size:12.5px;color:var(--text);font-weight:500;margin-bottom:8px;line-height:1.4}
.kcard-meta{display:flex;align-items:center;justify-content:space-between}
.kbadge{font-size:10px;padding:2px 7px;border-radius:99px;font-family:'DM Mono',monospace}
.kbd-high{background:rgba(248,113,113,.12);color:#f87171;border:1px solid rgba(248,113,113,.2)}
.kbd-med{background:rgba(251,191,36,.1);color:#fbbf24;border:1px solid rgba(251,191,36,.2)}
.kbd-low{background:rgba(96,165,250,.1);color:#60a5fa;border:1px solid rgba(96,165,250,.2)}
.kbd-done{background:rgba(126,232,162,.1);color:var(--accent);border:1px solid rgba(126,232,162,.2)}
.kavatar{width:20px;height:20px;border-radius:50%;background:var(--border2);display:flex;align-items:center;justify-content:center;font-size:9px;color:var(--muted)}
.kprog{height:2px;background:var(--border);border-radius:1px;margin-top:8px}
.kprog-fill{height:100%;border-radius:1px;background:var(--accent)}

/* ── Marketplace mockup ── */
.market-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;padding:20px}
.mcard{background:var(--surface2);border:1px solid var(--border);border-radius:12px;padding:14px}
.mcard-top{display:flex;align-items:center;gap:10px;margin-bottom:10px}
.mavatar{width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;flex-shrink:0}
.mcard-name{font-size:13px;font-weight:600;color:#fff}
.mcard-role{font-size:11px;color:var(--dim)}
.mcard-stars{display:flex;gap:2px;color:#fbbf24;font-size:11px}
.mcard-rate{font-family:'DM Mono',monospace;font-size:12px;color:var(--accent);margin-top:4px}
.mskills{display:flex;flex-wrap:wrap;gap:4px;margin-top:8px}
.mskill{font-size:10px;padding:2px 7px;border-radius:99px;background:var(--border);color:var(--muted)}
.mbadge-avail{font-size:10px;padding:2px 8px;border-radius:99px;background:rgba(126,232,162,.1);color:var(--accent);border:1px solid rgba(126,232,162,.2);margin-top:8px;display:inline-block}

/* ── Portal mockup ── */
.portal-wrap{padding:20px}
.portal-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px}
.portal-title{font-size:15px;font-weight:700;font-family:'Syne',sans-serif;color:#fff}
.ring-wrap{display:flex;align-items:center;gap:20px}
.progress-ring{position:relative;width:72px;height:72px;flex-shrink:0}
.progress-ring svg{transform:rotate(-90deg)}
.ring-label{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-family:'Syne',sans-serif;font-weight:800;font-size:17px;color:#fff}
.milestone{border:1px solid var(--border);border-radius:10px;padding:12px;margin-bottom:8px;background:var(--bg)}
.ms-head{display:flex;align-items:center;gap:8px;margin-bottom:6px}
.ms-check{width:16px;height:16px;border-radius:50%;border:2px solid var(--border2);flex-shrink:0}
.ms-check.done{background:var(--accent);border-color:var(--accent)}
.ms-name{font-size:12.5px;color:var(--text);font-weight:500}
.ms-bar{height:3px;background:var(--border);border-radius:2px}
.ms-fill{height:100%;border-radius:2px}

/* ── HOW IT WORKS ── */
.steps{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:32px;margin-top:60px;position:relative}
.step{position:relative;padding-left:0;text-align:center}
.step-num{width:48px;height:48px;border-radius:14px;border:1px solid var(--accent);background:var(--accent-dim);display:flex;align-items:center;justify-content:center;font-family:'Syne',sans-serif;font-weight:800;font-size:18px;color:var(--accent);margin:0 auto 18px}
.step h3{font-family:'Syne',sans-serif;font-weight:700;font-size:16px;color:#fff;margin-bottom:8px}
.step p{font-size:14px;color:var(--muted);line-height:1.6}
.step-connector{position:absolute;top:24px;right:-16px;width:32px;height:1px;background:var(--border2);z-index:0}

/* ── DUAL MODE ── */
.dual{display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-top:60px}
.mode-card{background:var(--surface);border:1px solid var(--border);border-radius:20px;padding:36px;position:relative;overflow:hidden}
.mode-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px}
.mode-freelancer::before{background:linear-gradient(90deg,var(--accent),var(--blue))}
.mode-client::before{background:linear-gradient(90deg,var(--purple),var(--accent))}
.mode-icon{width:52px;height:52px;border-radius:14px;display:flex;align-items:center;justify-content:center;margin-bottom:20px;border:1px solid var(--border2)}
.mode-card h3{font-family:'Syne',sans-serif;font-weight:800;font-size:22px;color:#fff;margin-bottom:10px}
.mode-card p{font-size:14.5px;color:var(--muted);margin-bottom:20px;line-height:1.65}
.mode-list{list-style:none;space-y:8px}
.mode-list li{display:flex;align-items:flex-start;gap:8px;font-size:13.5px;color:var(--muted);padding:5px 0}
.mode-list li::before{content:'';width:16px;height:16px;border-radius:50%;background:var(--accent-dim);border:1px solid rgba(126,232,162,.3);flex-shrink:0;margin-top:2px;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='none'%3E%3Cpath d='M4 8l2.5 2.5L12 5' stroke='%237EE8A2' stroke-width='1.8' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E")}

/* ── PRICING ── */
.pricing-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px;margin-top:60px}
.price-card{background:var(--surface);border:1px solid var(--border);border-radius:20px;padding:32px;position:relative}
.price-card.featured{border-color:rgba(126,232,162,.35);background:linear-gradient(135deg,rgba(126,232,162,.04),rgba(126,232,162,.01))}
.price-badge{position:absolute;top:-12px;left:50%;transform:translateX(-50%);background:var(--accent);color:#080c14;font-size:11px;font-weight:700;padding:4px 14px;border-radius:99px;white-space:nowrap;font-family:'DM Mono',monospace}
.price-name{font-family:'Syne',sans-serif;font-weight:700;font-size:15px;color:var(--muted);margin-bottom:10px;text-transform:uppercase;letter-spacing:.5px}
.price-amount{display:flex;align-items:baseline;gap:4px;margin-bottom:6px}
.price-num{font-family:'Syne',sans-serif;font-weight:800;font-size:40px;color:#fff}
.price-per{font-size:14px;color:var(--dim)}
.price-desc{font-size:13.5px;color:var(--dim);margin-bottom:24px;line-height:1.5}
.price-list{list-style:none;margin-bottom:28px}
.price-list li{display:flex;align-items:center;gap:9px;font-size:13.5px;color:var(--muted);padding:6px 0;border-bottom:1px solid rgba(26,45,69,.4)}
.price-list li:last-child{border-bottom:none}
.price-list li svg{flex-shrink:0}
.check-yes{color:var(--accent)}
.check-no{color:var(--dim)}

/* ── REVIEWS ── */
.reviews-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:20px;margin-top:60px}
.review{background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:24px;position:relative}
.review-stars{display:flex;gap:3px;margin-bottom:14px}
.review-body{font-size:14.5px;color:var(--muted);line-height:1.7;margin-bottom:18px}
.review-body strong{color:var(--text)}
.reviewer{display:flex;align-items:center;gap:10px}
.reviewer-avatar{width:38px;height:38px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;flex-shrink:0}
.reviewer-name{font-size:13.5px;font-weight:600;color:#fff}
.reviewer-role{font-size:12px;color:var(--dim)}
.review-platform{position:absolute;top:20px;right:20px;opacity:.35}

/* ── INTEGRATIONS ── */
.integrations{display:flex;flex-wrap:wrap;gap:12px;margin-top:40px;justify-content:center}
.integration{display:flex;align-items:center;gap:8px;padding:10px 18px;background:var(--surface);border:1px solid var(--border);border-radius:10px;font-size:13px;color:var(--muted);transition:all .2s}
.integration:hover{border-color:var(--border2);color:var(--text)}
.int-dot{width:8px;height:8px;border-radius:50%}

/* ── FAQ ── */
.faq-list{margin-top:48px;max-width:740px;margin-left:auto;margin-right:auto}
.faq-item{border-bottom:1px solid var(--border);padding:20px 0}
.faq-q{font-size:16px;font-weight:600;color:#fff;cursor:pointer;display:flex;justify-content:space-between;align-items:center;gap:16px;user-select:none}
.faq-q:hover{color:var(--accent)}
.faq-chevron{width:20px;height:20px;flex-shrink:0;transition:transform .25s;color:var(--dim)}
.faq-item.open .faq-chevron{transform:rotate(180deg)}
.faq-a{font-size:14.5px;color:var(--muted);line-height:1.7;max-height:0;overflow:hidden;transition:max-height .3s ease,padding .3s}
.faq-item.open .faq-a{max-height:200px;padding-top:12px}

/* ── CTA SECTION ── */
.cta-section{padding:100px 5vw;text-align:center;position:relative;overflow:hidden}
.cta-glow{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:600px;height:300px;background:radial-gradient(ellipse,rgba(126,232,162,.08) 0%,transparent 70%);pointer-events:none}
.cta-section h2{font-size:clamp(32px,5vw,56px);max-width:700px;margin:0 auto 20px}
.cta-section p{font-size:17px;color:var(--muted);max-width:500px;margin:0 auto 40px}

/* ── FOOTER ── */
footer{border-top:1px solid var(--border);padding:60px 5vw 40px;background:var(--bg2)}
.footer-grid{display:grid;grid-template-columns:2fr 1fr 1fr 1fr;gap:40px;margin-bottom:48px}
.footer-brand p{font-size:13.5px;color:var(--dim);margin-top:12px;max-width:260px;line-height:1.6}
.footer-col h4{font-size:12px;font-family:'DM Mono',monospace;text-transform:uppercase;letter-spacing:1px;color:var(--dim);margin-bottom:16px}
.footer-col a{display:block;font-size:13.5px;color:var(--muted);text-decoration:none;margin-bottom:10px;transition:color .2s}
.footer-col a:hover{color:var(--accent)}
.footer-bottom{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;padding-top:24px;border-top:1px solid var(--border)}
.footer-bottom p{font-size:13px;color:var(--dim)}
.socials{display:flex;gap:10px}
.social-btn{width:34px;height:34px;border-radius:8px;border:1px solid var(--border);display:flex;align-items:center;justify-content:center;color:var(--dim);transition:all .2s;text-decoration:none}
.social-btn:hover{border-color:var(--accent);color:var(--accent);background:var(--accent-dim)}

/* ── RESPONSIVE ── */
@media(max-width:768px){
.nav-links{display:none}
.dual{grid-template-columns:1fr}
.footer-grid{grid-template-columns:1fr 1fr}
.market-grid{grid-template-columns:1fr}
.hero-stats .stat{padding:14px 20px}
.stat-num{font-size:20px}
}
@media(max-width:480px){
.footer-grid{grid-template-columns:1fr}
.kanban{flex-direction:column}
}

/* scroll reveal */
.reveal{opacity:0;transform:translateY(24px);transition:opacity .6s ease,transform .6s ease}
.reveal.visible{opacity:1;transform:none}
</style>
</head>
<body>

<!-- NAV -->
<nav>
  <div class="nav-logo">
    <svg width="32" height="32" viewBox="0 0 32 32" fill="none">
      <rect width="32" height="32" rx="8" fill="rgba(126,232,162,.1)" stroke="rgba(126,232,162,.2)" stroke-width="1"/>
      <path d="M7 9h9M7 14h13M7 19h7" stroke="#7EE8A2" stroke-width="2" stroke-linecap="round"/>
      <circle cx="23" cy="21" r="4.5" stroke="#7EE8A2" stroke-width="1.8"/>
      <path d="M23 19.5v1.5l1 1" stroke="#7EE8A2" stroke-width="1.5" stroke-linecap="round"/>
    </svg>
    <span class="nav-logo-text">ProjexFlow</span>
  </div>
  <div class="nav-links">
    <a href="#features">Features</a>
    <a href="#how">How it works</a>
    <a href="#marketplace">Marketplace</a>
    <a href="#pricing">Pricing</a>
    <a href="#reviews">Reviews</a>
  </div>
  <div class="nav-cta">
    <a href="{{ route('dashboard') }}" class="btn btn-ghost">Sign in</a>
    <a href="{{ route('register') }}" class="btn btn-primary">Get started free</a>
  </div>
</nav>

<!-- HERO -->
<section class="hero">
  <div class="hero-grid"></div>
  <div class="hero-glow"></div>

  <div class="badge reveal">
    <span class="badge-dot"></span>
    Built for Africa · Powered by AI
  </div>

  <h1 class="reveal">
    Ship projects faster.<br>
    <span class="accent">Hire the best talent.</span><br>
    <span class="dim">Get paid securely.</span>
  </h1>

  <p class="hero-sub reveal">
    ProjexFlow is the all-in-one platform combining project management, a professional freelance marketplace, built-in video meetings, and escrow payments — built for how modern teams actually work.
  </p>

  <div class="hero-actions reveal">
    <a href="{{ route('register') }}" class="btn btn-primary btn-lg">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
      Start for free — no credit card
    </a>
    <a href="#features" class="btn btn-outline btn-lg">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><path d="M10 8l6 4-6 4V8z" fill="currentColor"/></svg>
      See how it works
    </a>
  </div>

  <div class="hero-stats reveal">
    <div class="stat">
      <span class="stat-num">12,400+</span>
      <span class="stat-label">Projects managed</span>
    </div>
    <div class="stat">
      <span class="stat-num">3,800+</span>
      <span class="stat-label">Freelancers active</span>
    </div>
    <div class="stat">
      <span class="stat-num">$2.1M+</span>
      <span class="stat-label">Paid out securely</span>
    </div>
    <div class="stat">
      <span class="stat-num">28</span>
      <span class="stat-label">African countries</span>
    </div>
  </div>
</section>

<!-- FEATURES -->
<section id="features">
  <div class="section-tag reveal">Everything you need</div>
  <h2 class="reveal">One platform. Every workflow.</h2>
  <p class="section-sub reveal">Stop switching between five different tools. ProjexFlow brings your entire work lifecycle under one roof — from planning to payment.</p>

  <div class="features-grid reveal">

    <div class="feat">
      <div class="feat-icon">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#7EE8A2" stroke-width="1.8" stroke-linecap="round">
          <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>
          <rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>
        </svg>
      </div>
      <h3>Kanban & project boards</h3>
      <p>Visual task management with Kanban and list views. Set priorities, assign members, track progress with milestones and deliverables — all in real-time.</p>
    </div>

    <div class="feat">
      <div class="feat-icon">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#60a5fa" stroke-width="1.8" stroke-linecap="round">
          <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
          <circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/>
        </svg>
      </div>
      <h3>Freelance marketplace</h3>
      <p>Browse 3,800+ verified professionals ranked by skills, ratings, and availability. Post jobs or get hired directly — both sides of the marketplace, one platform.</p>
    </div>

    <div class="feat">
      <div class="feat-icon">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#a78bfa" stroke-width="1.8" stroke-linecap="round">
          <path d="M15 10l4.553-2.069A1 1 0 0121 8.876v6.248a1 1 0 01-1.447.894L15 14M3 8a2 2 0 012-2h10a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8z"/>
        </svg>
      </div>
      <h3>Built-in video meetings</h3>
      <p>HD video calls with recording, screen share, and AI-powered transcription via Whisper. Never lose a meeting insight — everything is saved and searchable.</p>
    </div>

    <div class="feat">
      <div class="feat-icon">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fbbf24" stroke-width="1.8" stroke-linecap="round">
          <rect x="2" y="5" width="20" height="14" rx="2"/>
          <path d="M2 10h20"/>
        </svg>
      </div>
      <h3>Escrow & payments</h3>
      <p>Secure milestone-based payments with automatic escrow. MTN Mobile Money, Orange Money, Flutterwave, CinetPay, and Stripe — Africa-first payment rails built in.</p>
    </div>

    <div class="feat">
      <div class="feat-icon">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#7EE8A2" stroke-width="1.8" stroke-linecap="round">
          <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
          <path d="M9 22V12h6v10"/>
        </svg>
      </div>
      <h3>Client portal</h3>
      <p>Give every client a private, branded portal to track progress in real-time. They see milestones, deliverables, and can leave feedback — zero friction.</p>
    </div>

    <div class="feat">
      <div class="feat-icon">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#f87171" stroke-width="1.8" stroke-linecap="round">
          <path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 00-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0020 4.77 5.07 5.07 0 0019.91 1S18.73.65 16 2.48a13.38 13.38 0 00-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 005 4.77a5.44 5.44 0 00-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 009 18.13V22"/>
        </svg>
      </div>
      <h3>GitHub integration</h3>
      <p>Connect your repos and auto-complete tasks when pull requests merge. Push events notify your team, PR merges close tasks — your code workflow, automated.</p>
    </div>

    <div class="feat">
      <div class="feat-icon">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#60a5fa" stroke-width="1.8" stroke-linecap="round">
          <rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>
          <path d="M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01"/>
        </svg>
      </div>
      <h3>Smart calendar & bookings</h3>
      <p>Set your weekly availability, let clients book sessions directly. Your calendar aggregates tasks, milestones, and bookings with intelligent slot detection.</p>
    </div>

    <div class="feat">
      <div class="feat-icon">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#a78bfa" stroke-width="1.8" stroke-linecap="round">
          <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
        </svg>
      </div>
      <h3>Contracts & disputes</h3>
      <p>Create legally-structured contracts with deposit terms, milestone breakdowns, and auto-release conditions. Open disputes with a managed resolution flow.</p>
    </div>

    <div class="feat">
      <div class="feat-icon">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fbbf24" stroke-width="1.8" stroke-linecap="round">
          <path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/>
          <path d="M3.27 6.96L12 12.01l8.73-5.05M12 22.08V12"/>
        </svg>
      </div>
      <h3>Multi-tenant orgs</h3>
      <p>Run multiple organizations from one account. Invite team members, assign roles, manage projects across clients — full multi-tenant architecture.</p>
    </div>
  </div>
</section>

<!-- SCREENS -->
<section class="screens-section" id="screens">
  <div style="text-align:center">
    <div class="section-tag reveal">The platform in action</div>
    <h2 class="reveal">See what you're building with</h2>
    <p class="section-sub reveal" style="margin:12px auto 0">Every screen is production-ready, responsive, and built with Flux UI on the dark terminal theme.</p>
  </div>

  <div class="screens-wrap reveal" style="margin-top:48px">
    <!-- Kanban board mockup -->
    <div style="margin-bottom:16px;font-size:12px;color:var(--dim);font-family:'DM Mono',monospace;text-transform:uppercase;letter-spacing:1px">Project Board — Kanban View</div>
    <div class="screen-main">
      <div class="screen-bar">
        <div class="dot" style="background:#ef4444"></div>
        <div class="dot" style="background:#fbbf24"></div>
        <div class="dot" style="background:#22c55e"></div>
        <span style="margin-left:8px;font-size:11px;color:var(--dim);font-family:'DM Mono',monospace">projexflow.app/backend/projects/website-redesign</span>
      </div>
      <div class="kanban">
        <div class="kol">
          <div class="kol-head">Planning <span>3</span></div>
          <div class="kcard">
            <div class="kcard-title">User authentication flow redesign</div>
            <div class="kcard-meta"><span class="kbadge kbd-high">High</span><div class="kavatar">MK</div></div>
            <div class="kprog"><div class="kprog-fill" style="width:0%"></div></div>
          </div>
          <div class="kcard">
            <div class="kcard-title">Define API contracts for mobile app</div>
            <div class="kcard-meta"><span class="kbadge kbd-med">Med</span><div class="kavatar">AL</div></div>
            <div class="kprog"><div class="kprog-fill" style="width:0%"></div></div>
          </div>
          <div class="kcard">
            <div class="kcard-title">Set up staging environment</div>
            <div class="kcard-meta"><span class="kbadge kbd-low">Low</span><div class="kavatar">BO</div></div>
          </div>
        </div>
        <div class="kol">
          <div class="kol-head">In progress <span>2</span></div>
          <div class="kcard">
            <div class="kcard-title">Build dashboard analytics charts</div>
            <div class="kcard-meta"><span class="kbadge kbd-high">High</span><div class="kavatar">MK</div></div>
            <div class="kprog"><div class="kprog-fill" style="width:60%;background:var(--blue)"></div></div>
          </div>
          <div class="kcard">
            <div class="kcard-title">Integrate payment gateway — Flutterwave</div>
            <div class="kcard-meta"><span class="kbadge kbd-med">Med</span><div class="kavatar">FA</div></div>
            <div class="kprog"><div class="kprog-fill" style="width:35%;background:var(--amber)"></div></div>
          </div>
        </div>
        <div class="kol">
          <div class="kol-head">In review <span>1</span></div>
          <div class="kcard">
            <div class="kcard-title">Responsive mobile layout — all pages</div>
            <div class="kcard-meta"><span class="kbadge kbd-med">Med</span><div class="kavatar">AL</div></div>
            <div class="kprog"><div class="kprog-fill" style="width:90%;background:var(--purple)"></div></div>
          </div>
        </div>
        <div class="kol">
          <div class="kol-head">Done <span>4</span></div>
          <div class="kcard">
            <div class="kcard-title">Project setup and boilerplate</div>
            <div class="kcard-meta"><span class="kbadge kbd-done">Done</span><div class="kavatar">MK</div></div>
          </div>
          <div class="kcard">
            <div class="kcard-title">Database schema v1.0 finalized</div>
            <div class="kcard-meta"><span class="kbadge kbd-done">Done</span><div class="kavatar">FA</div></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Marketplace mockup -->
  <div class="screens-wrap reveal" style="margin-top:48px">
    <div style="margin-bottom:16px;font-size:12px;color:var(--dim);font-family:'DM Mono',monospace;text-transform:uppercase;letter-spacing:1px">Freelance Marketplace — Browse Professionals</div>
    <div class="screen-main">
      <div class="screen-bar">
        <div class="dot" style="background:#ef4444"></div>
        <div class="dot" style="background:#fbbf24"></div>
        <div class="dot" style="background:#22c55e"></div>
        <span style="margin-left:8px;font-size:11px;color:var(--dim);font-family:'DM Mono',monospace">projexflow.app{{ route('backend.marketplace') }}</span>
      </div>
      <div class="market-grid">
        <div class="mcard">
          <div class="mcard-top">
            <div class="mavatar" style="background:rgba(126,232,162,.15);color:#7EE8A2">MK</div>
            <div>
              <div class="mcard-name">Moustapha K.</div>
              <div class="mcard-role">Full-Stack Developer</div>
            </div>
          </div>
          <div class="mcard-stars">★★★★★</div>
          <div class="mcard-rate">$38/hr · Cameroon 🇨🇲</div>
          <div class="mskills"><span class="mskill">Laravel</span><span class="mskill">Livewire</span><span class="mskill">Vue</span></div>
          <span class="mbadge-avail">Open to work</span>
        </div>
        <div class="mcard">
          <div class="mcard-top">
            <div class="mavatar" style="background:rgba(167,139,250,.15);color:#a78bfa">AN</div>
            <div>
              <div class="mcard-name">Amara N.</div>
              <div class="mcard-role">UI/UX Designer</div>
            </div>
          </div>
          <div class="mcard-stars">★★★★★</div>
          <div class="mcard-rate">$45/hr · Nigeria 🇳🇬</div>
          <div class="mskills"><span class="mskill">Figma</span><span class="mskill">Prototyping</span><span class="mskill">Design Systems</span></div>
          <span class="mbadge-avail">Open to work</span>
        </div>
        <div class="mcard">
          <div class="mcard-top">
            <div class="mavatar" style="background:rgba(96,165,250,.15);color:#60a5fa">BT</div>
            <div>
              <div class="mcard-name">Bright T.</div>
              <div class="mcard-role">Data Analyst</div>
            </div>
          </div>
          <div class="mcard-stars">★★★★☆</div>
          <div class="mcard-rate">$32/hr · Ghana 🇬🇭</div>
          <div class="mskills"><span class="mskill">Python</span><span class="mskill">SQL</span><span class="mskill">Tableau</span></div>
          <span class="mbadge-avail">Open to work</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Client portal mockup -->
  <div class="screens-wrap reveal" style="margin-top:48px">
    <div style="margin-bottom:16px;font-size:12px;color:var(--dim);font-family:'DM Mono',monospace;text-transform:uppercase;letter-spacing:1px">Client Portal — Project Progress View</div>
    <div class="screen-main">
      <div class="screen-bar">
        <div class="dot" style="background:#ef4444"></div>
        <div class="dot" style="background:#fbbf24"></div>
        <div class="dot" style="background:#22c55e"></div>
        <span style="margin-left:8px;font-size:11px;color:var(--dim);font-family:'DM Mono',monospace">projexflow.app/portal/a7b3c...</span>
      </div>
      <div class="portal-wrap">
        <div class="portal-top">
          <div>
            <div class="portal-title">Website Redesign — Acme Corp</div>
            <div style="font-size:12px;color:var(--dim);margin-top:4px">Client portal · Last updated 2 hours ago</div>
          </div>
          <div class="ring-wrap">
            <div class="progress-ring">
              <svg width="72" height="72" viewBox="0 0 72 72">
                <circle cx="36" cy="36" r="28" fill="none" stroke="#1a2d45" stroke-width="7"/>
                <circle cx="36" cy="36" r="28" fill="none" stroke="#7EE8A2" stroke-width="7"
                  stroke-linecap="round"
                  stroke-dasharray="175.9"
                  stroke-dashoffset="52.8"/>
              </svg>
              <div class="ring-label">70%</div>
            </div>
            <div>
              <div style="font-size:11px;color:var(--dim)">Complete</div>
              <div style="font-size:13px;color:var(--accent);font-weight:600;margin-top:2px">On track</div>
            </div>
          </div>
        </div>
        <div class="milestone">
          <div class="ms-head">
            <div class="ms-check done"></div>
            <span class="ms-name">Discovery & wireframes</span>
            <span style="margin-left:auto;font-size:11px;color:var(--accent);font-family:'DM Mono',monospace">100%</span>
          </div>
          <div class="ms-bar"><div class="ms-fill" style="width:100%;background:var(--accent)"></div></div>
        </div>
        <div class="milestone">
          <div class="ms-head">
            <div class="ms-check done"></div>
            <span class="ms-name">UI design system & components</span>
            <span style="margin-left:auto;font-size:11px;color:var(--accent);font-family:'DM Mono',monospace">100%</span>
          </div>
          <div class="ms-bar"><div class="ms-fill" style="width:100%;background:var(--accent)"></div></div>
        </div>
        <div class="milestone">
          <div class="ms-head">
            <div class="ms-check"></div>
            <span class="ms-name">Frontend development</span>
            <span style="margin-left:auto;font-size:11px;color:var(--blue);font-family:'DM Mono',monospace">65%</span>
          </div>
          <div class="ms-bar"><div class="ms-fill" style="width:65%;background:var(--blue)"></div></div>
        </div>
        <div class="milestone">
          <div class="ms-head">
            <div class="ms-check"></div>
            <span class="ms-name">Backend integration & testing</span>
            <span style="margin-left:auto;font-size:11px;color:var(--dim);font-family:'DM Mono',monospace">0%</span>
          </div>
          <div class="ms-bar"><div class="ms-fill" style="width:0%"></div></div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- HOW IT WORKS -->
<section id="how">
  <div style="text-align:center">
    <div class="section-tag reveal">Simple workflow</div>
    <h2 class="reveal">Up and running in minutes</h2>
    <p class="section-sub reveal" style="margin:12px auto 0">Whether you're managing a team or finding your next client, ProjexFlow gets you started immediately.</p>
  </div>

  <div class="steps reveal">
    <div class="step">
      <div class="step-num">1</div>
      <h3>Create your org</h3>
      <p>Sign up, create your organization, and invite your team. Role-based access for every member.</p>
    </div>
    <div class="step">
      <div class="step-num">2</div>
      <h3>Set up projects</h3>
      <p>Add projects, create milestones and tasks. Assign work, set deadlines, connect GitHub repos.</p>
    </div>
    <div class="step">
      <div class="step-num">3</div>
      <h3>Hire or get hired</h3>
      <p>Post jobs for freelancers to apply, or build your marketplace profile and start getting discovered.</p>
    </div>
    <div class="step">
      <div class="step-num">4</div>
      <h3>Deliver & get paid</h3>
      <p>Use built-in contracts and escrow. Submit work, client approves, payment releases automatically.</p>
    </div>
  </div>
</section>

<!-- DUAL MODE -->
<section id="marketplace" style="background:var(--bg2)">
  <div style="text-align:center">
    <div class="section-tag reveal">Two sides, one platform</div>
    <h2 class="reveal">Built for freelancers <span style="color:var(--accent)">&amp;</span> clients</h2>
    <p class="section-sub reveal" style="margin:12px auto 0">Switch seamlessly between hiring talent and offering your services — your account works both ways.</p>
  </div>

  <div class="dual reveal">
    <div class="mode-card mode-freelancer">
      <div class="mode-icon" style="background:rgba(126,232,162,.08)">
        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#7EE8A2" stroke-width="1.8" stroke-linecap="round">
          <path d="M20 7H4a2 2 0 00-2 2v6a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2z"/>
          <circle cx="12" cy="12" r="2"/>
          <path d="M6 12H4M20 12h-2"/>
        </svg>
      </div>
      <h3>For freelancers</h3>
      <p>Build your profile, showcase your portfolio, set your rates and availability. Apply to jobs or get discovered by clients browsing the marketplace.</p>
      <ul class="mode-list">
        <li>Verified professional profile with portfolio</li>
        <li>AI-powered marketplace ranking algorithm</li>
        <li>Apply to jobs or let clients find you</li>
        <li>Booking calendar with availability settings</li>
        <li>Secure contracts + automatic escrow</li>
        <li>Wallet with MTN / Orange Money withdrawal</li>
        <li>5-star review system (verified only)</li>
      </ul>
    </div>
    <div class="mode-card mode-client">
      <div class="mode-icon" style="background:rgba(167,139,250,.08)">
        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#a78bfa" stroke-width="1.8" stroke-linecap="round">
          <rect x="2" y="7" width="20" height="14" rx="2"/>
          <path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/>
          <line x1="12" y1="12" x2="12" y2="16"/><line x1="10" y1="14" x2="14" y2="14"/>
        </svg>
      </div>
      <h3>For clients</h3>
      <p>Post jobs, browse talent, manage projects with your team, and give clients a real-time portal to track your work — all in one organized workspace.</p>
      <ul class="mode-list">
        <li>Post jobs and receive applications</li>
        <li>Browse 3,800+ ranked professionals</li>
        <li>Kanban boards, milestones & task tracking</li>
        <li>Client portal with live progress view</li>
        <li>Contracts with deposit + milestone payments</li>
        <li>Built-in video meetings with transcripts</li>
        <li>GitHub PR auto-completion</li>
      </ul>
    </div>
  </div>
</section>

<!-- INTEGRATIONS -->
<section style="text-align:center">
  <div class="section-tag reveal">Integrations</div>
  <h2 class="reveal">Connects with everything</h2>
  <p class="section-sub reveal" style="margin:12px auto 0">Payment rails, development tools, and communication platforms — all wired in.</p>
  <div class="integrations reveal">
    <div class="integration"><div class="int-dot" style="background:#eb4444"></div>Stripe Connect</div>
    <div class="integration"><div class="int-dot" style="background:#ffcc00"></div>MTN Mobile Money</div>
    <div class="integration"><div class="int-dot" style="background:#ff6600"></div>Orange Money</div>
    <div class="integration"><div class="int-dot" style="background:#f5a623"></div>Flutterwave</div>
    <div class="integration"><div class="int-dot" style="background:#00a8e0"></div>CinetPay</div>
    <div class="integration"><div class="int-dot" style="background:#24292e"></div>GitHub</div>
    <div class="integration"><div class="int-dot" style="background:#0fa968"></div>LiveKit</div>
    <div class="integration"><div class="int-dot" style="background:#10a37f"></div>OpenAI Whisper</div>
    <div class="integration"><div class="int-dot" style="background:#ff4f00"></div>Laravel Reverb</div>
    <div class="integration"><div class="int-dot" style="background:#38bdf8"></div>Tailwind CSS</div>
  </div>
</section>

<!-- PRICING -->
<section id="pricing" style="background:var(--bg2)">
  <div style="text-align:center">
    <div class="section-tag reveal">Simple pricing</div>
    <h2 class="reveal">Pay as you grow</h2>
    <p class="section-sub reveal" style="margin:12px auto 0">No per-seat fees. No hidden charges. A 10% platform fee on completed contracts — you only pay when you earn.</p>
  </div>

  <div class="pricing-grid reveal">

    <div class="price-card">
      <div class="price-name">Starter</div>
      <div class="price-amount"><span class="price-num">$0</span><span class="price-per">/month</span></div>
      <div class="price-desc">Everything to get started. No time limit.</div>
      <ul class="price-list">
        <li>
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" class="check-yes"><path d="M20 6L9 17l-5-5"/></svg>
          3 active projects
        </li>
        <li>
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" class="check-yes"><path d="M20 6L9 17l-5-5"/></svg>
          1 organization
        </li>
        <li>
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" class="check-yes"><path d="M20 6L9 17l-5-5"/></svg>
          Marketplace profile
        </li>
        <li>
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" class="check-yes"><path d="M20 6L9 17l-5-5"/></svg>
          Client portal
        </li>
        <li>
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" class="check-no"><path d="M18 6L6 18M6 6l12 12"/></svg>
          <span style="color:var(--dim)">Video meetings</span>
        </li>
        <li>
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" class="check-no"><path d="M18 6L6 18M6 6l12 12"/></svg>
          <span style="color:var(--dim)">GitHub integration</span>
        </li>
      </ul>
      <a href="{{ route('register') }}" class="btn btn-outline" style="width:100%;justify-content:center">Get started free</a>
    </div>

    <div class="price-card featured">
      <div class="price-badge">Most popular</div>
      <div class="price-name">Pro</div>
      <div class="price-amount"><span class="price-num">$19</span><span class="price-per">/month</span></div>
      <div class="price-desc">For growing freelancers and small teams.</div>
      <ul class="price-list">
        <li>
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" class="check-yes"><path d="M20 6L9 17l-5-5"/></svg>
          Unlimited projects
        </li>
        <li>
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" class="check-yes"><path d="M20 6L9 17l-5-5"/></svg>
          3 organizations
        </li>
        <li>
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" class="check-yes"><path d="M20 6L9 17l-5-5"/></svg>
          Video meetings + recordings
        </li>
        <li>
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" class="check-yes"><path d="M20 6L9 17l-5-5"/></svg>
          AI transcripts (Whisper)
        </li>
        <li>
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" class="check-yes"><path d="M20 6L9 17l-5-5"/></svg>
          GitHub integration
        </li>
        <li>
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" class="check-yes"><path d="M20 6L9 17l-5-5"/></svg>
          Priority support
        </li>
      </ul>
      <a href="{{ route('register') }}" class="btn btn-primary" style="width:100%;justify-content:center">Start 14-day trial</a>
    </div>

    <div class="price-card">
      <div class="price-name">Agency</div>
      <div class="price-amount"><span class="price-num">$59</span><span class="price-per">/month</span></div>
      <div class="price-desc">For agencies managing multiple clients.</div>
      <ul class="price-list">
        <li>
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" class="check-yes"><path d="M20 6L9 17l-5-5"/></svg>
          Everything in Pro
        </li>
        <li>
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" class="check-yes"><path d="M20 6L9 17l-5-5"/></svg>
          Unlimited organizations
        </li>
        <li>
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" class="check-yes"><path d="M20 6L9 17l-5-5"/></svg>
          Reduced 7% platform fee
        </li>
        <li>
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" class="check-yes"><path d="M20 6L9 17l-5-5"/></svg>
          Custom client portal domain
        </li>
        <li>
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" class="check-yes"><path d="M20 6L9 17l-5-5"/></svg>
          Advanced analytics
        </li>
        <li>
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" class="check-yes"><path d="M20 6L9 17l-5-5"/></svg>
          Dedicated support
        </li>
      </ul>
      <a href="/contact" class="btn btn-outline" style="width:100%;justify-content:center">Contact sales</a>
    </div>
  </div>

  <p class="reveal" style="text-align:center;margin-top:28px;font-size:13px;color:var(--dim)">
    All plans include a 10% platform fee on contracts. Agency plan is reduced to 7%.
    Prices in USD. Local currency invoicing available.
  </p>
</section>

<!-- REVIEWS -->
<section id="reviews">
  <div style="text-align:center">
    <div class="section-tag reveal">Social proof</div>
    <h2 class="reveal">Loved by teams across Africa</h2>
    <p class="section-sub reveal" style="margin:12px auto 0">Real reviews from freelancers and clients using ProjexFlow every day.</p>
  </div>

  <div class="reviews-grid reveal">

    <div class="review">
      <div class="review-platform">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" style="color:var(--text)"><path d="M12 2C6.477 2 2 6.477 2 12c0 4.42 2.865 8.17 6.839 9.49.5.092.682-.217.682-.482 0-.237-.008-.866-.013-1.7-2.782.604-3.369-1.34-3.369-1.34-.454-1.156-1.11-1.464-1.11-1.464-.908-.62.069-.608.069-.608 1.003.07 1.531 1.03 1.531 1.03.892 1.529 2.341 1.087 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.11-4.555-4.943 0-1.091.39-1.984 1.029-2.683-.103-.253-.446-1.27.098-2.647 0 0 .84-.269 2.75 1.025A9.578 9.578 0 0112 6.836c.85.004 1.705.114 2.504.336 1.909-1.294 2.747-1.025 2.747-1.025.546 1.377.203 2.394.1 2.647.64.699 1.028 1.592 1.028 2.683 0 3.842-2.339 4.687-4.566 4.935.359.309.678.919.678 1.852 0 1.336-.012 2.415-.012 2.743 0 .267.18.579.688.481C19.138 20.167 22 16.418 22 12c0-5.523-4.477-10-10-10z"/></svg>
      </div>
      <div class="review-stars">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="#fbbf24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="#fbbf24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="#fbbf24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="#fbbf24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="#fbbf24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
      </div>
      <p class="review-body">"Finally a platform that understands how we work in Africa. <strong>MTN Mobile Money payouts</strong> work perfectly and the escrow system means I never chase clients for payment anymore. The kanban board is as good as Trello but everything stays in one place."</p>
      <div class="reviewer">
        <div class="reviewer-avatar" style="background:rgba(126,232,162,.15);color:#7EE8A2">EO</div>
        <div>
          <div class="reviewer-name">Emmanuel Osei</div>
          <div class="reviewer-role">Full-Stack Developer · Accra, Ghana</div>
        </div>
      </div>
    </div>

    <div class="review">
      <div class="review-platform">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="#1DA1F2"><path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/></svg>
      </div>
      <div class="review-stars">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="#fbbf24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="#fbbf24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="#fbbf24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="#fbbf24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="#fbbf24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
      </div>
      <p class="review-body">"The <strong>client portal is a game-changer</strong> for my agency. Clients can see progress without us having to send weekly status emails. The video meeting transcripts save us at least 2 hours a week in follow-up documentation."</p>
      <div class="reviewer">
        <div class="reviewer-avatar" style="background:rgba(167,139,250,.15);color:#a78bfa">FN</div>
        <div>
          <div class="reviewer-name">Fatima N'Diaye</div>
          <div class="reviewer-role">Agency Director · Dakar, Senegal</div>
        </div>
      </div>
    </div>

    <div class="review">
      <div class="review-stars">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="#fbbf24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="#fbbf24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="#fbbf24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="#fbbf24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="#fbbf24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
      </div>
      <p class="review-body">"I hired three developers through ProjexFlow for my startup's MVP. The <strong>escrow system gave me confidence</strong> that payments were only released when milestones were truly done. Found better talent here than on Upwork, at better rates."</p>
      <div class="reviewer">
        <div class="reviewer-avatar" style="background:rgba(96,165,250,.15);color:#60a5fa">KM</div>
        <div>
          <div class="reviewer-name">Kwame Mensah</div>
          <div class="reviewer-role">Startup Founder · Lagos, Nigeria</div>
        </div>
      </div>
    </div>

    <div class="review">
      <div class="review-stars">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="#fbbf24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="#fbbf24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="#fbbf24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="#fbbf24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="#506070"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
      </div>
      <p class="review-body">"The GitHub integration is brilliant. When a pull request merges, the task closes automatically. Our sprint velocity reporting in ProjexFlow is now accurate without any manual updates. <strong>Saves us hours every sprint.</strong>"</p>
      <div class="reviewer">
        <div class="reviewer-avatar" style="background:rgba(251,191,36,.12);color:#fbbf24">AI</div>
        <div>
          <div class="reviewer-name">Amina Ibrahim</div>
          <div class="reviewer-role">Engineering Lead · Nairobi, Kenya</div>
        </div>
      </div>
    </div>

    <div class="review">
      <div class="review-stars">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="#fbbf24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="#fbbf24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="#fbbf24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="#fbbf24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="#fbbf24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
      </div>
      <p class="review-body">"Posted my first job and got <strong>12 quality applications within 48 hours</strong>. The applicant profiles show verified reviews from real projects which made filtering easy. Hired in 3 days — faster than any platform I've used."</p>
      <div class="reviewer">
        <div class="reviewer-avatar" style="background:rgba(248,113,113,.12);color:#f87171">TC</div>
        <div>
          <div class="reviewer-name">Thierno Camara</div>
          <div class="reviewer-role">Product Manager · Conakry, Guinea</div>
        </div>
      </div>
    </div>

    <div class="review">
      <div class="review-stars">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="#fbbf24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="#fbbf24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="#fbbf24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="#fbbf24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="#fbbf24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
      </div>
      <p class="review-body">"I withdrew my earnings via <strong>Orange Money in under 10 minutes</strong>. No bank required, no waiting 5 business days. This is the kind of financial infrastructure Africa's freelancers actually need. Genuinely impressed."</p>
      <div class="reviewer">
        <div class="reviewer-avatar" style="background:rgba(126,232,162,.15);color:#7EE8A2">ZB</div>
        <div>
          <div class="reviewer-name">Zainab Bello</div>
          <div class="reviewer-role">UX Designer · Abuja, Nigeria</div>
        </div>
      </div>
    </div>

  </div>
</section>

<!-- FAQ -->
<section style="background:var(--bg2)">
  <div style="text-align:center">
    <div class="section-tag reveal">FAQ</div>
    <h2 class="reveal">Common questions</h2>
  </div>
  <div class="faq-list reveal" id="faq">

    <div class="faq-item">
      <div class="faq-q" onclick="toggleFaq(this)">
        How does the escrow system work?
        <svg class="faq-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 9l6 6 6-6"/></svg>
      </div>
      <div class="faq-a">When a contract is created, the client pays a deposit (typically 30%) which is held in escrow. Work begins once the deposit clears. When work is submitted and approved, the remaining balance releases to the freelancer minus the platform fee. If neither party responds within 7 days, funds auto-release to the freelancer. Disputes freeze the funds and trigger a managed review process.</div>
    </div>

    <div class="faq-item">
      <div class="faq-q" onclick="toggleFaq(this)">
        Which African payment methods are supported?
        <svg class="faq-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 9l6 6 6-6"/></svg>
      </div>
      <div class="faq-a">For withdrawals: MTN Mobile Money (Cameroon, Ghana, Rwanda, Uganda), Orange Money (Cameroon, Côte d'Ivoire, Senegal), Flutterwave for broader Africa coverage (M-Pesa Kenya, Airtel Africa, Wave Senegal), CinetPay for West/Central Africa, and Stripe Connect for global bank payouts. Multi-currency support includes XAF, NGN, GHS, KES, and 7 other currencies.</div>
    </div>

    <div class="faq-item">
      <div class="faq-q" onclick="toggleFaq(this)">
        What is the platform fee?
        <svg class="faq-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 9l6 6 6-6"/></svg>
      </div>
      <div class="faq-a">ProjexFlow charges a 10% platform fee on the total contract value, deducted at the time of payment release. Agency plan subscribers pay a reduced 7% fee. The fee covers payment processing, escrow management, dispute resolution, and platform maintenance. There are no additional per-transaction fees beyond the platform fee.</div>
    </div>

    <div class="faq-item">
      <div class="faq-q" onclick="toggleFaq(this)">
        Can I use ProjexFlow as both a freelancer and a client?
        <svg class="faq-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 9l6 6 6-6"/></svg>
      </div>
      <div class="faq-a">Yes — your single account works both ways. You can build a freelancer profile to get hired, while simultaneously posting jobs to hire other freelancers for your own projects. The marketplace and project management tools are fully unified under one account.</div>
    </div>

    <div class="faq-item">
      <div class="faq-q" onclick="toggleFaq(this)">
        How does the video meeting and transcription work?
        <svg class="faq-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 9l6 6 6-6"/></svg>
      </div>
      <div class="faq-a">Video meetings are powered by LiveKit, a real-time WebRTC infrastructure. Hosts can start recording during any meeting. Once the meeting ends, the recording is processed and sent to OpenAI Whisper for AI transcription. Transcript and recording links are automatically attached to the booking record and accessible by both participants.</div>
    </div>

    <div class="faq-item">
      <div class="faq-q" onclick="toggleFaq(this)">
        Is there a free plan?
        <svg class="faq-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 9l6 6 6-6"/></svg>
      </div>
      <div class="faq-a">Yes. The Starter plan is permanently free with up to 3 active projects, 1 organization, a marketplace profile, and client portals. Video meetings, GitHub integration, and AI transcription require the Pro plan ($19/month) or above. There is no time limit on the free plan.</div>
    </div>

  </div>
</section>

<!-- CTA -->
<section class="cta-section">
  <div class="cta-glow"></div>
  <div class="section-tag reveal">Get started today</div>
  <h2 class="reveal">
    Your projects. Your freelancers.<br>
    <span style="color:var(--accent)">Your payments.</span>
  </h2>
  <p class="reveal">Join 12,400+ teams and freelancers across 28 African countries. Start free — no credit card required.</p>
  <div class="reveal" style="display:flex;flex-wrap:wrap;gap:14px;justify-content:center">
    <a href="{{ route('register') }}" class="btn btn-primary btn-lg">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
      Create free account
    </a>
    <a href="{{ route('backend.marketplace') }}" class="btn btn-outline btn-lg">Browse freelancers</a>
  </div>
  <p class="reveal" style="margin-top:20px;font-size:13px;color:var(--dim)">
    Free forever · No credit card · Setup in 2 minutes
  </p>
</section>

<!-- FOOTER -->
<footer>
  <div class="footer-grid">
    <div class="footer-brand">
      <div class="nav-logo">
        <svg width="28" height="28" viewBox="0 0 32 32" fill="none">
          <rect width="32" height="32" rx="7" fill="rgba(126,232,162,.1)" stroke="rgba(126,232,162,.2)" stroke-width="1"/>
          <path d="M7 9h9M7 14h13M7 19h7" stroke="#7EE8A2" stroke-width="2" stroke-linecap="round"/>
          <circle cx="23" cy="21" r="4.5" stroke="#7EE8A2" stroke-width="1.8"/>
          <path d="M23 19.5v1.5l1 1" stroke="#7EE8A2" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
        <span class="nav-logo-text">ProjexFlow</span>
      </div>
      <p>The all-in-one platform for African freelancers and teams. Project management, marketplace, video, and payments — built for how you work.</p>
      <div class="socials" style="margin-top:16px">
        <a href="https://twitter.com/projexflow" class="social-btn" title="Twitter / X">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
        </a>
        <a href="https://linkedin.com/company/projexflow" class="social-btn" title="LinkedIn">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
        </a>
        <a href="https://github.com/projexflow" class="social-btn" title="GitHub">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.477 2 2 6.477 2 12c0 4.42 2.865 8.17 6.839 9.49.5.092.682-.217.682-.482 0-.237-.008-.866-.013-1.7-2.782.604-3.369-1.34-3.369-1.34-.454-1.156-1.11-1.464-1.11-1.464-.908-.62.069-.608.069-.608 1.003.07 1.531 1.03 1.531 1.03.892 1.529 2.341 1.087 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.11-4.555-4.943 0-1.091.39-1.984 1.029-2.683-.103-.253-.446-1.27.098-2.647 0 0 .84-.269 2.75 1.025A9.578 9.578 0 0112 6.836c.85.004 1.705.114 2.504.336 1.909-1.294 2.747-1.025 2.747-1.025.546 1.377.203 2.394.1 2.647.64.699 1.028 1.592 1.028 2.683 0 3.842-2.339 4.687-4.566 4.935.359.309.678.919.678 1.852 0 1.336-.012 2.415-.012 2.743 0 .267.18.579.688.481C19.138 20.167 22 16.418 22 12c0-5.523-4.477-10-10-10z"/></svg>
        </a>
        <a href="https://discord.gg/projexflow" class="social-btn" title="Discord">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M20.317 4.37a19.791 19.791 0 00-4.885-1.515.074.074 0 00-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 00-5.487 0 12.64 12.64 0 00-.617-1.25.077.077 0 00-.079-.037A19.736 19.736 0 003.677 4.37a.07.07 0 00-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 00.031.057 19.9 19.9 0 005.993 3.03.078.078 0 00.084-.028c.462-.63.874-1.295 1.226-1.994a.076.076 0 00-.041-.106 13.107 13.107 0 01-1.872-.892.077.077 0 01-.008-.128 10.2 10.2 0 00.372-.292.074.074 0 01.077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 01.078.01c.12.098.246.198.373.292a.077.077 0 01-.006.127 12.299 12.299 0 01-1.873.892.077.077 0 00-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 00.084.028 19.839 19.839 0 006.002-3.03.077.077 0 00.032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 00-.031-.03zM8.02 15.33c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.956-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.956 2.418-2.157 2.418zm7.975 0c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.955-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.946 2.418-2.157 2.418z"/></svg>
        </a>
      </div>
    </div>
    <div class="footer-col">
      <h4>Product</h4>
      <a href="#features">Features</a>
      <a href="#pricing">Pricing</a>
      <a href="{{ route('backend.marketplace') }}">Marketplace</a>
      <a href="/backend/jobs">Job board</a>
      <a href="{{ route('dashboard') }}">Dashboard</a>
    </div>
    <div class="footer-col">
      <h4>For freelancers</h4>
      <a href="{{ route('register') }}">Create profile</a>
      <a href="{{ route('backend.marketplace') }}">Browse jobs</a>
      <a href="{{ route('backend.wallet') }}">Wallet & payouts</a>
      <a href="/settings/availability">Set availability</a>
      <a href="/backend/contracts">Contracts</a>
    </div>
    <div class="footer-col">
      <h4>Company</h4>
      <a href="/about">About</a>
      <a href="/blog">Blog</a>
      <a href="/contact">Contact</a>
      <a href="/privacy">Privacy policy</a>
      <a href="/terms">Terms of service</a>
    </div>
  </div>
  <div class="footer-bottom">
    <p>© 2025 ProjexFlow. Built in Cameroon 🇨🇲 for Africa and the world.</p>
    <div style="display:flex;align-items:center;gap:16px">
      <span style="font-size:12px;color:var(--dim)">Available in</span>
      <span style="font-size:12px;color:var(--dim)">🇨🇲 🇳🇬 🇬🇭 🇰🇪 🇸🇳 🇨🇮 🇹🇿 🇺🇬 +20 more</span>
    </div>
  </div>
</footer>

<script>
const revealEls = document.querySelectorAll('.reveal');
const obs = new IntersectionObserver((entries) => {
  entries.forEach(e => { if(e.isIntersecting) { e.target.classList.add('visible'); obs.unobserve(e.target); } });
}, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });
revealEls.forEach(el => obs.observe(el));

function toggleFaq(el) {
  const item = el.parentElement;
  const isOpen = item.classList.contains('open');
  document.querySelectorAll('.faq-item.open').forEach(i => i.classList.remove('open'));
  if (!isOpen) item.classList.add('open');
}

window.addEventListener('scroll', () => {
  const nav = document.querySelector('nav');
  nav.style.background = window.scrollY > 60 ? 'rgba(8,12,20,.96)' : 'rgba(8,12,20,.85)';
});
</script>
</body>
</html>

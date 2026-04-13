<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FoundIt! | BISU Candijay Campus</title>
    <meta name="description" content="The smart Lost & Found platform of BISU Candijay Campus. Report, browse, and reclaim your belongings.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root { --navy:#0b1d3a; --navy2:#122347; --gold:#f5c842; --gold2:#e8b800; --white:#ffffff; --offwhite:#f5f6f8; --muted:#8494a8; --text:#1a2a3a; }
        html { scroll-behavior: smooth; }
        body { font-family: 'DM Sans', sans-serif; background: var(--offwhite); color: var(--text); overflow-x: hidden; }

        /* NAVBAR */
        .navbar { position:fixed; top:0; left:0; right:0; z-index:100; display:flex; align-items:center; justify-content:space-between; padding:16px 48px; background:rgba(11,29,58,0.97); backdrop-filter:blur(16px); border-bottom:3px solid transparent; border-image:linear-gradient(90deg,#ffcc00,#f5c842,#e8b800,#ffcc00) 1; }
        .nav-brand { display:flex; align-items:center; gap:12px; text-decoration:none; transition:transform .2s; }
        .nav-brand:hover { transform:scale(1.02); }
        .nav-brand-svg { height:36px; width:36px; filter:drop-shadow(0 0 6px rgba(255,204,0,0.3)); }
        .nav-brand-text { display:flex; flex-direction:column; line-height:1.15; }
        .nav-brand-name { font-family:'Syne',sans-serif; font-size:1.3rem; font-weight:800; color:var(--white); }
        .nav-brand-name em { font-style:normal; color:var(--gold); }
        .nav-brand-sub { font-size:.65rem; color:rgba(255,255,255,0.5); letter-spacing:1.8px; font-weight:600; text-transform:uppercase; }
        .btn-nav-gold { padding:9px 22px; border-radius:10px; background:linear-gradient(135deg,var(--gold),var(--gold2)); color:var(--navy); text-decoration:none; font-size:.875rem; font-weight:700; transition:all .25s; box-shadow:0 2px 12px rgba(245,200,66,0.25); }
        .btn-nav-gold:hover { transform:translateY(-2px); box-shadow:0 6px 20px rgba(245,200,66,0.4); }

        /* HERO */
        .hero { min-height:100vh; background:var(--navy); display:flex; align-items:center; position:relative; overflow:hidden; padding:120px 48px 80px; }
        .hero::before { content:''; position:absolute; inset:0; background:radial-gradient(ellipse 60% 50% at 70% 50%,rgba(245,200,66,0.1) 0%,transparent 70%),radial-gradient(ellipse 40% 60% at 20% 80%,rgba(13,110,253,0.08) 0%,transparent 60%); }
        .hero::after { content:''; position:absolute; inset:0; background-image:linear-gradient(rgba(255,255,255,0.03) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,0.03) 1px,transparent 1px); background-size:60px 60px; }
        .hero-particles { position:absolute; inset:0; z-index:1; pointer-events:none; overflow:hidden; }
        .hero-particle { position:absolute; border-radius:50%; background:rgba(245,200,66,0.15); animation:particleFloat linear infinite; }
        @keyframes particleFloat { 0%{transform:translateY(100vh) scale(0);opacity:0} 10%{opacity:1} 90%{opacity:1} 100%{transform:translateY(-20vh) scale(1);opacity:0} }
        .hero-inner { position:relative; z-index:2; max-width:1200px; margin:0 auto; width:100%; display:grid; grid-template-columns:1fr 1fr; gap:60px; align-items:center; }
        .hero-tag { display:inline-flex; align-items:center; gap:8px; background:rgba(245,200,66,0.12); border:1px solid rgba(245,200,66,0.3); color:var(--gold); font-size:.78rem; font-weight:600; padding:6px 14px; border-radius:100px; letter-spacing:.08em; text-transform:uppercase; margin-bottom:24px; animation:fadeUp .6s ease both; }
        .hero h1 { font-family:'Syne',sans-serif; font-size:clamp(2.4rem,5vw,3.8rem); font-weight:800; line-height:1.1; color:var(--white); margin-bottom:20px; animation:fadeUp .6s .1s ease both; }
        .hero h1 em { font-style:normal; color:var(--gold); }
        .hero-desc { color:#8fa3bb; font-size:1.05rem; line-height:1.7; margin-bottom:36px; animation:fadeUp .6s .2s ease both; }
        .hero-actions { display:flex; gap:12px; flex-wrap:wrap; animation:fadeUp .6s .3s ease both; }
        .btn-hero-primary { padding:14px 32px; border-radius:10px; background:var(--gold); color:var(--navy); text-decoration:none; font-weight:700; font-size:.95rem; transition:all .25s; display:inline-flex; align-items:center; gap:8px; box-shadow:0 4px 20px rgba(245,200,66,0.3); }
        .btn-hero-primary:hover { background:var(--gold2); transform:translateY(-2px); box-shadow:0 8px 30px rgba(245,200,66,0.4); color:var(--navy); }
        .btn-hero-outline { padding:14px 32px; border-radius:10px; border:1.5px solid rgba(255,255,255,0.2); color:var(--white); text-decoration:none; font-weight:500; font-size:.95rem; transition:all .25s; display:inline-flex; align-items:center; gap:8px; }
        .btn-hero-outline:hover { border-color:var(--gold); color:var(--gold); }
        .hero-panel { animation:fadeUp .6s .4s ease both; }
        .stat-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
        .stat-box { background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.08); border-radius:16px; padding:24px 20px; transition:all .3s; }
        .stat-box:hover { background:rgba(255,255,255,0.08); transform:translateY(-4px); border-color:rgba(245,200,66,0.2); }
        .stat-box .num { font-family:'Syne',sans-serif; font-size:2.4rem; font-weight:800; color:var(--gold); line-height:1; margin-bottom:6px; }
        .stat-box .lbl { color:#8fa3bb; font-size:.8rem; font-weight:500; }
        .stat-box .ico { font-size:1.4rem; margin-bottom:12px; }
        .stat-box:nth-child(1) .ico{color:#60a5fa} .stat-box:nth-child(2) .ico{color:#34d399} .stat-box:nth-child(3) .ico{color:#f87171} .stat-box:nth-child(4) .ico{color:var(--gold)}

        /* SECTIONS */
        section { padding:80px 48px; }
        .section-inner { max-width:1200px; margin:0 auto; }
        .section-tag { display:inline-block; background:rgba(11,29,58,0.08); color:var(--navy); font-size:.72rem; font-weight:700; padding:5px 14px; border-radius:100px; letter-spacing:.1em; text-transform:uppercase; margin-bottom:14px; }
        .section-title { font-family:'Syne',sans-serif; font-size:clamp(1.6rem,3vw,2.4rem); font-weight:800; color:var(--navy); margin-bottom:10px; }
        .section-sub { color:var(--muted); font-size:1rem; margin-bottom:48px; }

        .items-section { background: var(--white); }
        .items-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(200px,1fr)); gap:20px; }
        .item-card { border-radius:16px; overflow:hidden; border:1px solid #e8ecf0; transition:all .3s; background:var(--offwhite); }
        .item-card:hover { transform:translateY(-6px); box-shadow:0 16px 40px rgba(11,29,58,0.12); border-color:var(--gold); }
        .item-card img { width:100%; height:160px; object-fit:cover; background:#dde3ea; }
        .item-card-body { padding:14px 16px; }
        .item-card-name { font-weight:600; font-size:.9rem; color:var(--navy); margin-bottom:4px; }
        .item-card-loc { font-size:.78rem; color:var(--muted); display:flex; align-items:center; gap:4px; }
        .item-card-date { font-size:.72rem; color:#bcc5ce; margin-top:6px; }
        .badge-found { display:inline-block; background:#dcfce7; color:#16a34a; font-size:.65rem; font-weight:700; padding:3px 8px; border-radius:100px; margin-bottom:8px; text-transform:uppercase; }
        .badge-lost { display:inline-block; background:#fee2e2; color:#dc2626; font-size:.65rem; font-weight:700; padding:3px 8px; border-radius:100px; margin-bottom:8px; text-transform:uppercase; }
        .no-items { grid-column:1/-1; text-align:center; padding:60px 20px; color:var(--muted); }
        .no-items i { font-size:3rem; display:block; margin-bottom:12px; opacity:.4; }

        .how-section { background: var(--white); }
        .steps-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:28px; }
        .step-card { background:var(--white); border-radius:20px; padding:36px 28px; border:1px solid #e8ecf0; transition:all .3s; position:relative; overflow:hidden; }
        .step-card::before { content:attr(data-num); position:absolute; top:-10px; right:16px; font-family:'Syne',sans-serif; font-size:5rem; font-weight:800; color:var(--navy); opacity:.04; line-height:1; }
        .step-card:hover { transform:translateY(-6px); box-shadow:0 16px 40px rgba(11,29,58,0.1); border-color:var(--gold); }
        .step-icon { width:52px; height:52px; border-radius:14px; display:flex; align-items:center; justify-content:center; font-size:1.4rem; margin-bottom:20px; }
        .step-icon.blue{background:#eff6ff;color:#2563eb} .step-icon.green{background:#f0fdf4;color:#16a34a} .step-icon.gold{background:#fefce8;color:#ca8a04}
        .step-card h4 { font-family:'Syne',sans-serif; font-size:1.1rem; font-weight:700; color:var(--navy); margin-bottom:10px; }
        .step-card p { color:var(--muted); font-size:.9rem; line-height:1.6; }

        .cta-section { background:var(--navy); text-align:center; padding:80px 48px; }
        .cta-section h2 { font-family:'Syne',sans-serif; font-size:clamp(1.8rem,4vw,2.8rem); font-weight:800; color:var(--white); margin-bottom:16px; }
        .cta-section h2 span { color:var(--gold); }
        .cta-section p { color:#8fa3bb; margin-bottom:36px; font-size:1rem; }

        footer { background:#070f1e; color:#8fa3bb; padding:32px 48px; text-align:center; font-size:.85rem; }
        footer strong { color:var(--gold); }

        @keyframes fadeUp { from{opacity:0;transform:translateY(24px)} to{opacity:1;transform:translateY(0)} }
        .reveal { opacity:0; transform:translateY(30px); transition:opacity .6s ease,transform .6s ease; }
        .reveal.visible { opacity:1; transform:translateY(0); }

        @media (max-width:768px) {
            .navbar{padding:14px 20px} .hero{padding:100px 20px 60px} .hero-inner{grid-template-columns:1fr;gap:40px}
            .steps-grid{grid-template-columns:1fr} section{padding:60px 20px} .stat-grid{grid-template-columns:1fr 1fr}
        }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
    <a class="nav-brand" href="#">
        <svg class="nav-brand-svg" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="20" cy="20" r="14" stroke="#ffcc00" stroke-width="3.5"/>
            <line x1="30" y1="30" x2="42" y2="42" stroke="#ffcc00" stroke-width="3.5" stroke-linecap="round"/>
            <polyline points="13,20 18,25 27,15" stroke="#ffffff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <div class="nav-brand-text">
            <div class="nav-brand-name">Found<em>It!</em></div>
            <div class="nav-brand-sub">BISU Candijay Campus</div>
        </div>
    </a>
    <div>
        @auth
            <a href="{{ route('dashboard') }}" class="btn-nav-gold"><i class="bi bi-speedometer2"></i> Dashboard</a>
        @else
            <a href="{{ route('login') }}" class="btn-nav-gold"><i class="bi bi-box-arrow-in-right"></i> Log In</a>
        @endauth
    </div>
</nav>

<!-- HERO -->
<section class="hero">
    <div class="hero-particles" id="heroParticles"></div>
    <div class="hero-inner">
        <div>
            <div class="hero-tag"><i class="bi bi-geo-alt-fill"></i> BISU Candijay Campus · Bohol, Philippines</div>
            <h1>Lost something on <em>campus?</em><br><em>FoundIt!</em> will help.</h1>
            <p class="hero-desc">The smart Lost & Found platform of BISU Candijay Campus. Report, browse, and reclaim your belongings — fast and hassle-free.</p>
            <div class="hero-actions">
                @auth
                    <a href="{{ route('dashboard') }}" class="btn-hero-primary"><i class="bi bi-speedometer2"></i> Go to Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="btn-hero-primary"><i class="bi bi-box-arrow-in-right"></i> Log In</a>
                    <a href="#how" class="btn-hero-outline"><i class="bi bi-play-circle"></i> How it works</a>
                @endauth
            </div>
        </div>
        <div class="hero-panel">
            <div class="stat-grid">
                <div class="stat-box"><div class="ico"><i class="bi bi-search"></i></div><div class="num count-num" data-target="{{ $totalLost }}">0</div><div class="lbl">Lost Reports</div></div>
                <div class="stat-box"><div class="ico"><i class="bi bi-check2-circle"></i></div><div class="num count-num" data-target="{{ $totalFound }}">0</div><div class="lbl">Items Found</div></div>
                <div class="stat-box"><div class="ico"><i class="bi bi-people"></i></div><div class="num count-num" data-target="{{ $totalUsers }}">0</div><div class="lbl">Active Users</div></div>
                <div class="stat-box"><div class="ico"><i class="bi bi-trophy"></i></div><div class="num count-num" data-target="{{ $successRate }}">0</div><div class="lbl">Success Rate %</div></div>
            </div>
        </div>
    </div>
</section>

<!-- RECENTLY LOST -->
<section class="items-section">
    <div class="section-inner">
        <div class="reveal">
            <span class="section-tag">🔍 Recently Lost</span>
            <h2 class="section-title">Items people are looking for</h2>
            <p class="section-sub">See what others have lost on campus. Have you seen any of these items?</p>
        </div>
        <div class="items-grid reveal">
            @forelse($recentLostReports as $report)
            <div class="item-card">
                <img src="{{ $report->image_path ? asset('storage/'.$report->image_path) : '' }}"
                     alt="{{ $report->item_name }}"
                     onerror="this.onerror=null;this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22300%22 height=%22160%22%3E%3Crect width=%22300%22 height=%22160%22 fill=%22%23dde3ea%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 font-family=%22sans-serif%22 font-size=%2214%22 fill=%22%238494a8%22 text-anchor=%22middle%22 dy=%22.3em%22%3ENo Photo%3C/text%3E%3C/svg%3E'">
                <div class="item-card-body">
                    <span class="badge-lost">Lost</span>
                    <div class="item-card-name">{{ $report->item_name }}</div>
                    <div class="item-card-loc"><i class="bi bi-geo-alt-fill" style="color:#ef4444"></i> Place Lost: {{ $report->last_seen_location ?: 'Unknown location' }}</div>
                    <div class="item-card-date"><i class="bi bi-clock me-1"></i>{{ $report->created_at->format('M d, Y') }}</div>
                </div>
            </div>
            @empty
            <div class="no-items"><i class="bi bi-search"></i><p>No lost reports at the moment. That's great news!</p></div>
            @endforelse
        </div>
        <div class="text-center mt-4 reveal">
            <a href="{{ auth()->check() ? route('dashboard') : route('login') }}"
               style="display:inline-flex;align-items:center;gap:8px;padding:12px 28px;background:var(--navy);color:#fff;border-radius:10px;text-decoration:none;font-weight:600;font-size:.9rem;transition:all .2s">
                <i class="bi bi-list-check"></i> View All Lost Reports
            </a>
        </div>
    </div>
</section>

<!-- RECENTLY FOUND -->
<section class="items-section" style="background:#f9fafb">
    <div class="section-inner">
        <div class="reveal">
            <span class="section-tag">📦 Recently Found</span>
            <h2 class="section-title">Items waiting for their owners</h2>
            <p class="section-sub">Browse the latest items found on campus. Is one of them yours?</p>
        </div>
        <div class="items-grid reveal">
            @forelse($recentItems as $item)
            <div class="item-card">
                <img src="{{ $item->image_path ? asset('storage/'.$item->image_path) : '' }}"
                     alt="{{ $item->item_name }}"
                     onerror="this.onerror=null;this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22300%22 height=%22160%22%3E%3Crect width=%22300%22 height=%22160%22 fill=%22%23dde3ea%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 font-family=%22sans-serif%22 font-size=%2214%22 fill=%22%238494a8%22 text-anchor=%22middle%22 dy=%22.3em%22%3ENo Photo%3C/text%3E%3C/svg%3E'">
                <div class="item-card-body">
                    <span class="badge-found">Found</span>
                    <div class="item-card-name">{{ $item->item_name }}</div>
                    <div class="item-card-loc"><i class="bi bi-geo-alt-fill" style="color:#ef4444"></i> Place Found: {{ $item->found_location ?: 'Unknown location' }}</div>
                    <div class="item-card-date"><i class="bi bi-clock me-1"></i>{{ $item->created_at->format('M d, Y') }}</div>
                </div>
            </div>
            @empty
            <div class="no-items"><i class="bi bi-inbox"></i><p>No published items yet. Check back soon!</p></div>
            @endforelse
        </div>
        <div class="text-center mt-4 reveal">
            <a href="{{ auth()->check() ? route('dashboard') : route('login') }}"
               style="display:inline-flex;align-items:center;gap:8px;padding:12px 28px;background:var(--navy);color:#fff;border-radius:10px;text-decoration:none;font-weight:600;font-size:.9rem;transition:all .2s">
                <i class="bi bi-grid-3x3-gap"></i> View All Found Items
            </a>
        </div>
    </div>
</section>

<!-- HOW IT WORKS -->
<section class="how-section" id="how">
    <div class="section-inner">
        <div class="reveal" style="text-align:center">
            <span class="section-tag">⚡ Simple Process</span>
            <h2 class="section-title">How it works</h2>
            <p class="section-sub">Three simple steps to report or recover your lost item</p>
        </div>
        <div class="steps-grid">
            <div class="step-card reveal" data-num="1">
                <div class="step-icon blue"><i class="bi bi-box-arrow-in-right"></i></div>
                <h4>Log In</h4>
                <p>Use your school email to log in. Your account is pre-registered by the admin, so you can start tracking reports right away.</p>
            </div>
            <div class="step-card reveal" data-num="2" style="transition-delay:.1s">
                <div class="step-icon green"><i class="bi bi-megaphone"></i></div>
                <h4>Submit a Report</h4>
                <p>Lost something? Report it with a description and location. Found something? Log it so the owner can claim it.</p>
            </div>
            <div class="step-card reveal" data-num="3" style="transition-delay:.2s">
                <div class="step-icon gold"><i class="bi bi-check2-circle"></i></div>
                <h4>Get Reunited</h4>
                <p>Our admin team matches reports and notifies both parties. Pick up your item at the SSG Office or Guard House.</p>
            </div>
        </div>
    </div>
</section>

<!-- SUCCESS RATE BANNER -->
<section style="background:linear-gradient(135deg,#f5c842 0%,#e8b800 100%);padding:60px 48px;text-align:center">
    <div class="section-inner">
        <p style="font-size:.85rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:rgba(11,29,58,0.6);margin-bottom:10px">Our track record</p>
        <h2 style="font-family:'Syne',sans-serif;font-size:clamp(2rem,5vw,4rem);font-weight:800;color:var(--navy);margin-bottom:8px">
            <span class="count-num" data-target="{{ $successRate }}">0</span>% Success Rate
        </h2>
        <p style="color:rgba(11,29,58,0.65);font-size:1rem">{{ $totalResolved }} out of {{ $totalLost }} lost reports successfully resolved</p>
    </div>
</section>

<!-- CTA -->
<section class="cta-section">
    <div class="section-inner">
        <h2>Lost something? <span>FoundIt!</span> has your back.</h2>
        <p>{{ $totalUsers }} BISU Candijay students are already using FoundIt!</p>
        @auth
            <a href="{{ route('dashboard') }}" class="btn-hero-primary" style="display:inline-flex"><i class="bi bi-speedometer2"></i> Go to Dashboard</a>
        @else
            <a href="{{ route('login') }}" class="btn-hero-primary" style="display:inline-flex"><i class="bi bi-box-arrow-in-right"></i> Log In Now</a>
        @endauth
    </div>
</section>

<!-- FOOTER -->
<footer>
    <p>© {{ date('Y') }} <strong>FoundIt!</strong> · BISU Candijay Campus &nbsp;|&nbsp; Bohol Island State University · Candijay, Bohol, Philippines</p>
</footer>

<script>
function animateCounter(el) {
    const target = parseInt(el.dataset.target) || 0;
    if (target === 0) { el.textContent = '0'; return; }
    let current = 0;
    const step = Math.ceil(target / 50);
    const timer = setInterval(() => {
        current = Math.min(current + step, target);
        el.textContent = current.toLocaleString();
        if (current >= target) clearInterval(timer);
    }, 30);
}

const observer = new IntersectionObserver(entries => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('visible');
            entry.target.querySelectorAll('.count-num').forEach(animateCounter);
            observer.unobserve(entry.target);
        }
    });
}, { threshold: 0.15 });
document.querySelectorAll('.reveal').forEach(el => observer.observe(el));

setTimeout(() => { document.querySelectorAll('.hero-panel .count-num').forEach(animateCounter); }, 500);

// Floating particles
(function() {
    const c = document.getElementById('heroParticles');
    if (!c) return;
    for (let i = 0; i < 20; i++) {
        const p = document.createElement('div');
        p.className = 'hero-particle';
        const s = Math.random() * 6 + 3;
        p.style.width = s+'px'; p.style.height = s+'px';
        p.style.left = Math.random()*100+'%';
        p.style.animationDuration = (Math.random()*8+6)+'s';
        p.style.animationDelay = (Math.random()*8)+'s';
        if (Math.random()>0.5) p.style.background='rgba(13,110,253,0.12)';
        c.appendChild(p);
    }
})();

const successObserver = new IntersectionObserver(entries => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.querySelectorAll('.count-num').forEach(animateCounter);
            successObserver.unobserve(entry.target);
        }
    });
}, { threshold: 0.3 });
document.querySelectorAll('section').forEach(s => successObserver.observe(s));
</script>
</body>
</html>

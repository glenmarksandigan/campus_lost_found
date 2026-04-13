<style>
:root {
    --primary-dark: #021d3a;
    --primary-main: #003c7a;
    --accent-gold: #ffc107;
    --accent-blue: #3fa9f5;
    --text-muted: rgba(255,255,255,0.8);
}

/* ===== WAVE DIVIDER ===== */
.footer-wave {
    position: relative; overflow: hidden;
    background: #f8fafc; /* matches page bg */
    line-height: 0;
}
.footer-wave svg { display: block; width: 100%; height: 80px; }

/* ===== FOOTER BASE ===== */
.custom-footer {
    position: relative;
    background:
        radial-gradient(circle at top right, rgba(63,169,245,0.15), transparent 40%),
        linear-gradient(135deg, var(--primary-main), var(--primary-dark));
    color: #fff;
    overflow: hidden;
}

/* Animated accent glow */
.custom-footer::before {
    content: "";
    position: absolute;
    top: 0; left: -50%;
    width: 200%; height: 4px;
    background: linear-gradient(90deg, transparent, var(--accent-gold), transparent);
    animation: glowMove 6s linear infinite;
}
@keyframes glowMove {
    from { transform: translateX(0); }
    to { transform: translateX(50%); }
}

/* ===== HEADINGS ===== */
.custom-footer h4, .custom-footer h5 {
    letter-spacing: 1px; position: relative; padding-bottom: 8px;
}
.custom-footer h5::after {
    content: ""; position: absolute; left: 0; bottom: 0;
    width: 35px; height: 2px; background: var(--accent-gold);
}

/* ===== TEXT ===== */
.custom-footer p, .custom-footer li {
    font-size: 0.9rem; line-height: 1.8; color: var(--text-muted);
}

/* ===== LINKS ===== */
.footer-link {
    display: inline-flex; align-items: center; gap: 6px;
    color: var(--text-muted); text-decoration: none; transition: all 0.3s ease;
}
.footer-link::before {
    content: "➜"; opacity: 0; transform: translateX(-5px);
    transition: all 0.3s ease; color: var(--accent-gold);
}
.footer-link:hover { color: #fff; }
.footer-link:hover::before { opacity: 1; transform: translateX(0); }

/* ===== INFO CARDS ===== */
.footer-card {
    background: rgba(255,255,255,0.05);
    border-radius: 14px; padding: 20px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.2);
    transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s;
    border: 1px solid rgba(255,255,255,0.05);
}
.footer-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 28px rgba(0,0,0,0.35);
    border-color: rgba(255,204,7,0.2);
}

/* ===== SOCIAL ICONS ===== */
.footer-socials { display: flex; gap: 10px; margin-top: 16px; }
.footer-social-icon {
    width: 36px; height: 36px; border-radius: 50%;
    background: rgba(255,255,255,0.1);
    display: flex; align-items: center; justify-content: center;
    color: rgba(255,255,255,0.7); font-size: 1rem;
    text-decoration: none; transition: all 0.3s;
}
.footer-social-icon:hover {
    background: var(--accent-gold); color: var(--primary-dark);
    transform: translateY(-3px);
}

/* ===== DIVIDER ===== */
.footer-divider { border-color: rgba(255,255,255,0.15); }

/* ===== COPYRIGHT ===== */
.footer-bottom {
    background: rgba(0,0,0,0.15);
    border-radius: 12px;
    padding: 12px; font-size: 0.85rem;
}

/* ===== BACK TO TOP ===== */
.back-to-top {
    position: fixed; bottom: 30px; right: 30px; z-index: 9000;
    width: 44px; height: 44px; border-radius: 50%;
    background: linear-gradient(135deg, #0d6efd, #0a4ab2);
    color: white; border: none; font-size: 1.1rem;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; opacity: 0; visibility: hidden;
    transition: all 0.3s ease;
    box-shadow: 0 4px 16px rgba(13,110,253,0.3);
}
.back-to-top.visible { opacity: 1; visibility: visible; }
.back-to-top:hover { transform: translateY(-3px) scale(1.05); box-shadow: 0 8px 24px rgba(13,110,253,0.4); }
</style>

<?php
// Hide visual footer for admin (4), superadmin (5), and organizer (6) roles
$_footerTypeId = (int)($_SESSION['type_id'] ?? 0);
$_isAdminRole = in_array($_footerTypeId, [4, 5, 6]);
?>

<?php if (!$_isAdminRole): ?>
<!-- Wave Divider -->
<div class="footer-wave">
    <svg viewBox="0 0 1200 80" preserveAspectRatio="none">
        <path d="M0,40 C300,80 600,0 900,40 C1050,60 1150,50 1200,40 L1200,80 L0,80 Z" fill="#003c7a"/>
    </svg>
</div>

<footer class="custom-footer pb-4">
    <div class="container">
        <div class="row g-4 pt-4">

            <!-- About -->
            <div class="col-md-4">
                <div class="footer-card h-100">
                    <h4 class="fw-bold text-uppercase mb-3">
                        🔍 FoundIt!
                    </h4>
                    <p>
                        The official lost and found platform of BISU Candijay —
                        designed to reconnect students with their belongings
                        quickly, securely, and efficiently.
                    </p>
                    <div class="footer-socials">
                        <a href="#" class="footer-social-icon"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="footer-social-icon"><i class="bi bi-envelope-fill"></i></a>
                        <a href="#" class="footer-social-icon"><i class="bi bi-globe2"></i></a>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <div class="col-md-4">
                <div class="footer-card h-100 text-md-center">
                    <h5 class="fw-bold text-warning mb-3">Quick Navigation</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <a href="index.php" class="footer-link">Explore Gallery</a>
                        </li>
                        <li class="mb-2">
                            <a href="report.php" class="footer-link">Found an Item?</a>
                        </li>
                        <li class="mb-2">
                            <a href="report_lost.php" class="footer-link">Report Lost Item</a>
                        </li>
                        <li class="mb-2">
                            <a href="inbox.php" class="footer-link">Messages</a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Contact -->
            <div class="col-md-4">
                <div class="footer-card h-100">
                    <h5 class="fw-bold text-warning mb-3">Contact Support</h5>
                    <p class="mb-2">📍 <strong>SSG Office:</strong> BISU Candijay Campus</p>
                    <p class="mb-2">📞 Guard House</p>
                    <p>📧 lostandfound@bisu.edu.ph</p>
                </div>
            </div>

        </div>

        <hr class="footer-divider my-4">

        <div class="footer-bottom text-center">
            &copy; <?php echo date("Y"); ?> FoundIt! · BISU Candijay Campus · Built for Student Success
        </div>
    </div>
</footer>
<?php endif; ?>


<!-- Back to Top Button -->
<button class="back-to-top" id="backToTop" onclick="window.scrollTo({top:0,behavior:'smooth'})">
    <i class="bi bi-chevron-up"></i>
</button>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Back to top visibility
window.addEventListener('scroll', function() {
    const btn = document.getElementById('backToTop');
    if (btn) {
        if (window.scrollY > 400) {
            btn.classList.add('visible');
        } else {
            btn.classList.remove('visible');
        }
    }
}, { passive: true });
</script>

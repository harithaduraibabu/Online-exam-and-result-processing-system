/**
 * assets/js/dynamic.js
 * Site-wide dynamic effects for Exam Portal
 * Applies to all admin and student pages automatically
 */
document.addEventListener('DOMContentLoaded', function () {

    // ─── 1. Scroll-Reveal (Intersection Observer) ─────────────────────────────
    const revealEls = document.querySelectorAll('.stat-card, .auth-card, table, .content-row > div');
    const revealObserver = new IntersectionObserver((entries) => {
        entries.forEach((entry, i) => {
            if (entry.isIntersecting) {
                entry.target.style.animationDelay = `${i * 0.07}s`;
                entry.target.classList.add('revealed');
                revealObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.08 });

    revealEls.forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(24px)';
        el.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        revealObserver.observe(el);
    });

    // ─── 2. Animated Number Counters ──────────────────────────────────────────
    document.querySelectorAll('.stat-value').forEach(el => {
        const target = parseFloat(el.innerText.replace(/[^0-9.]/g, ''));
        if (isNaN(target) || target === 0) return;
        let start = 0;
        const duration = 1200;
        const step = target / (duration / 16);
        el.innerText = '0';
        const timer = setInterval(() => {
            start += step;
            if (start >= target) {
                el.innerText = Number.isInteger(target) ? target : target.toFixed(1);
                clearInterval(timer);
            } else {
                el.innerText = Number.isInteger(target) ? Math.floor(start) : start.toFixed(1);
            }
        }, 16);
    });

    // ─── 3. Ripple Effect on all Buttons ─────────────────────────────────────
    document.querySelectorAll('.btn, .btn-primary, button').forEach(btn => {
        btn.addEventListener('click', function (e) {
            const ripple = document.createElement('span');
            const rect = btn.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            ripple.style.cssText = `
                position:absolute; border-radius:50%; pointer-events:none;
                width:${size}px; height:${size}px;
                left:${e.clientX - rect.left - size/2}px;
                top:${e.clientY - rect.top - size/2}px;
                background:rgba(255,255,255,0.35);
                transform:scale(0); animation:rippleAnim 0.6s ease-out forwards;
            `;
            if (getComputedStyle(btn).position === 'static') btn.style.position = 'relative';
            btn.style.overflow = 'hidden';
            btn.appendChild(ripple);
            setTimeout(() => ripple.remove(), 700);
        });
    });

    // ─── 4. Card tilt on hover (subtle 3D effect) ─────────────────────────────
    document.querySelectorAll('.stat-card').forEach(card => {
        card.addEventListener('mousemove', function (e) {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            const cx = rect.width / 2, cy = rect.height / 2;
            const rotY = ((x - cx) / cx) * 4;
            const rotX = -((y - cy) / cy) * 4;
            card.style.transform = `perspective(600px) rotateX(${rotX}deg) rotateY(${rotY}deg) translateY(-6px)`;
        });
        card.addEventListener('mouseleave', function () {
            card.style.transform = '';
            card.style.transition = 'transform 0.4s ease';
        });
    });

    // ─── 5. Page transition out (on link click) ───────────────────────────────
    document.querySelectorAll('a:not([target="_blank"]):not([href^="#"]):not([href^="javascript"])').forEach(link => {
        link.addEventListener('click', function (e) {
            const href = link.getAttribute('href');
            if (!href || href.startsWith('?') || href.startsWith('#')) return;
            e.preventDefault();
            document.body.style.transition = 'opacity 0.25s ease';
            document.body.style.opacity = '0';
            setTimeout(() => { window.location.href = href; }, 260);
        });
    });

    // ─── 6. Active menu link highlight ───────────────────────────────────────
    const currentPath = window.location.pathname.split('/').pop();
    document.querySelectorAll('.menu-link').forEach(link => {
        if (link.getAttribute('href') === currentPath || link.href.includes(currentPath)) {
            link.classList.add('active');
        }
    });

    // ─── 7. Tooltip on hover for icon buttons ────────────────────────────────
    document.querySelectorAll('[title]').forEach(el => {
        el.addEventListener('mouseenter', function () {
            const tip = document.createElement('div');
            tip.className = '__tooltip__';
            tip.innerText = el.getAttribute('title');
            tip.style.cssText = `
                position:fixed; background:#1e293b; color:#f8fafc;
                padding:5px 10px; border-radius:8px; font-size:0.78rem;
                pointer-events:none; z-index:9999; white-space:nowrap;
                box-shadow:0 4px 12px rgba(0,0,0,0.2);
                animation: scaleBounce 0.2s ease;
            `;
            document.body.appendChild(tip);
            const rect = el.getBoundingClientRect();
            tip.style.left = `${rect.left + rect.width/2 - tip.offsetWidth/2}px`;
            tip.style.top  = `${rect.top - tip.offsetHeight - 8}px`;
            el.addEventListener('mouseleave', () => tip.remove(), { once: true });
        });
    });

    // ─── 8. Flash message auto-dismiss ───────────────────────────────────────
    document.querySelectorAll('.alert').forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(() => alert.remove(), 500);
        }, 4000);
    });

    // ─── 9. Sidebar logo bounce on hover ─────────────────────────────────────
    const logo = document.querySelector('.sidebar-logo');
    if (logo) {
        logo.addEventListener('mouseenter', () => {
            logo.style.transform = 'scale(1.06)';
            logo.style.transition = 'transform 0.2s ease';
        });
        logo.addEventListener('mouseleave', () => {
            logo.style.transform = '';
        });
    }

    // ─── 10. Table row fade-in stagger ───────────────────────────────────────
    document.querySelectorAll('tbody tr').forEach((row, i) => {
        row.style.opacity = '0';
        row.style.transform = 'translateX(-12px)';
        row.style.transition = `opacity 0.35s ease ${i * 0.05}s, transform 0.35s ease ${i * 0.05}s`;
        setTimeout(() => {
            row.style.opacity = '1';
            row.style.transform = 'translateX(0)';
        }, 80 + i * 40);
    });

});

// ─── Keyframe injection ───────────────────────────────────────────────────────
const styleSheet = document.createElement('style');
styleSheet.innerText = `
    @keyframes rippleAnim {
        to { transform: scale(2.5); opacity: 0; }
    }
    @keyframes scaleBounce {
        0%   { transform: scale(0.7); opacity: 0; }
        70%  { transform: scale(1.05); }
        100% { transform: scale(1); opacity: 1; }
    }
    .revealed {
        opacity: 1 !important;
        transform: translateY(0) !important;
    }
`;
document.head.appendChild(styleSheet);

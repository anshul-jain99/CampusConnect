/**
 * CampusConnect - Frontend Interactivity Script
 * Handles mobile navbar toggle, live event filtering, alert auto-dismiss,
 * and lightweight canvas stats visualization.
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Mobile Navbar Toggle
    const navToggle = document.querySelector('.nav-toggle');
    const navLinks = document.querySelector('.nav-links');

    if (navToggle && navLinks) {
        navToggle.addEventListener('click', () => {
            navLinks.classList.toggle('open');
        });
    }

    // 2. Auto-dismiss flash messages after 5 seconds
    const flashAlerts = document.querySelectorAll('.flash-alert');
    flashAlerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(() => alert.remove(), 400);
        }, 5000);
    });

    // 3. Live Client-Side Event Filtering & Search
    const searchInput = document.getElementById('eventSearchInput');
    const categoryFilter = document.getElementById('categoryFilterSelect');
    const eventCards = document.querySelectorAll('.event-card-item');
    const noEventsMsg = document.getElementById('noEventsFoundMessage');

    function filterEvents() {
        if (!eventCards.length) return;

        const searchTerm = (searchInput ? searchInput.value : '').toLowerCase().trim();
        const selectedCategory = (categoryFilter ? categoryFilter.value : 'all').toLowerCase();

        let visibleCount = 0;

        eventCards.forEach(card => {
            const title = card.getAttribute('data-title') || '';
            const category = card.getAttribute('data-category') || '';
            const venue = card.getAttribute('data-venue') || '';
            const desc = card.getAttribute('data-desc') || '';

            const matchesSearch = title.includes(searchTerm) || venue.includes(searchTerm) || desc.includes(searchTerm);
            const matchesCategory = selectedCategory === 'all' || category === selectedCategory;

            if (matchesSearch && matchesCategory) {
                card.style.display = 'flex';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });

        if (noEventsMsg) {
            noEventsMsg.style.display = visibleCount === 0 ? 'block' : 'none';
        }
    }

    if (searchInput) {
        searchInput.addEventListener('input', filterEvents);
    }
    if (categoryFilter) {
        categoryFilter.addEventListener('change', filterEvents);
    }

    // Category pills click handler
    const categoryPills = document.querySelectorAll('.category-pill');
    categoryPills.forEach(pill => {
        pill.addEventListener('click', (e) => {
            categoryPills.forEach(p => p.classList.remove('active'));
            pill.classList.add('active');

            const cat = pill.getAttribute('data-category');
            if (categoryFilter) {
                categoryFilter.value = cat;
                filterEvents();
            }
        });
    });

    // 4. Quick Demo Login Autofill Helpers
    const fillAdminBtn = document.getElementById('fillAdminLogin');
    const fillOrgBtn = document.getElementById('fillOrgLogin');
    const fillStudentBtn = document.getElementById('fillStudentLogin');

    const emailInput = document.getElementById('loginEmail');
    const passwordInput = document.getElementById('loginPassword');

    if (fillAdminBtn && emailInput && passwordInput) {
        fillAdminBtn.addEventListener('click', () => {
            emailInput.value = 'admin@campus.edu';
            passwordInput.value = 'admin123';
        });
    }
    if (fillOrgBtn && emailInput && passwordInput) {
        fillOrgBtn.addEventListener('click', () => {
            emailInput.value = 'organizer.tech@campus.edu';
            passwordInput.value = 'org123';
        });
    }
    if (fillStudentBtn && emailInput && passwordInput) {
        fillStudentBtn.addEventListener('click', () => {
            emailInput.value = 'anshul@student.edu';
            passwordInput.value = 'student123';
        });
    }

    // 5. Admin Category Statistics Bar Chart (Vanilla HTML5 Canvas)
    const chartCanvas = document.getElementById('adminStatsCanvas');
    if (chartCanvas && chartCanvas.getContext) {
        const ctx = chartCanvas.getContext('2d');
        const rawData = chartCanvas.getAttribute('data-chart-data');
        if (rawData) {
            try {
                const data = JSON.parse(rawData);
                drawBarChart(ctx, chartCanvas.width, chartCanvas.height, data);
            } catch (err) {
                console.error("Could not parse chart data", err);
            }
        }
    }

    function drawBarChart(ctx, width, height, data) {
        const padding = 40;
        const chartWidth = width - padding * 2;
        const chartHeight = height - padding * 2;
        
        ctx.clearRect(0, 0, width, height);

        const keys = Object.keys(data);
        const values = Object.values(data);
        const maxVal = Math.max(...values, 5);

        const barWidth = Math.min(50, (chartWidth / keys.length) - 20);
        const gap = (chartWidth - (barWidth * keys.length)) / (keys.length + 1);

        // Draw baseline
        ctx.beginPath();
        ctx.moveTo(padding, height - padding);
        ctx.lineTo(width - padding, height - padding);
        ctx.strokeStyle = '#cbd5e1';
        ctx.lineWidth = 2;
        ctx.stroke();

        // Draw bars
        keys.forEach((key, index) => {
            const val = values[index];
            const barH = (val / maxVal) * chartHeight;
            const x = padding + gap + index * (barWidth + gap);
            const y = height - padding - barH;

            // Gradient bar
            const grad = ctx.createLinearGradient(0, y, 0, height - padding);
            grad.addColorStop(0, '#3b82f6');
            grad.addColorStop(1, '#6366f1');
            ctx.fillStyle = grad;
            ctx.beginPath();
            ctx.roundRect ? ctx.roundRect(x, y, barWidth, barH, [4, 4, 0, 0]) : ctx.rect(x, y, barWidth, barH);
            ctx.fill();

            // Label text
            ctx.fillStyle = '#475569';
            ctx.font = '11px Segoe UI, sans-serif';
            ctx.textAlign = 'center';
            ctx.fillText(key, x + barWidth / 2, height - padding + 18);

            // Value text on top of bar
            ctx.fillStyle = '#1e293b';
            ctx.font = 'bold 12px Segoe UI, sans-serif';
            ctx.fillText(val, x + barWidth / 2, y - 6);
        });
    }
});

// Confirmation helper for delete/cancel actions
function confirmAction(message) {
    return confirm(message || 'Are you sure you want to proceed?');
}

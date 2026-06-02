/**
 * CTISMS - Application JavaScript
 */

document.addEventListener('DOMContentLoaded', function () {

    // ---- Sidebar Toggle (Mobile) --------------------------------
    const sidebar = document.getElementById('sidebar');
    const toggleBtn = document.getElementById('sidebarToggle');
    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', () => sidebar.classList.toggle('open'));
        document.addEventListener('click', (e) => {
            if (window.innerWidth < 768 && sidebar.classList.contains('open')
                && !sidebar.contains(e.target) && !toggleBtn.contains(e.target)) {
                sidebar.classList.remove('open');
            }
        });
    }

    // ---- Auto-dismiss alerts after 5 seconds -------------------
    document.querySelectorAll('.alert.auto-dismiss').forEach(alert => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            bsAlert?.close();
        }, 5000);
    });

    // ---- Tooltips -----------------------------------------------
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
        new bootstrap.Tooltip(el);
    });

    // ---- Confirm dialogs ----------------------------------------
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', (e) => {
            if (!confirm(el.dataset.confirm)) e.preventDefault();
        });
    });

    // ---- AJAX: Mark notification read --------------------------
    document.querySelectorAll('.mark-notif-read').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.dataset.id;
            fetch(`${window.APP_URL}/notifications/read.php?id=${id}&ajax=1`)
                .then(() => {
                    this.closest('.notif-item')?.classList.add('opacity-50');
                    updateUnreadBadge(-1);
                });
        });
    });

    // ---- Table row click ----------------------------------------
    document.querySelectorAll('tr[data-href]').forEach(row => {
        row.style.cursor = 'pointer';
        row.addEventListener('click', function () {
            window.location.href = this.dataset.href;
        });
    });

    // ---- Status filter pills -----------------------------------
    document.querySelectorAll('.filter-pill').forEach(pill => {
        pill.addEventListener('click', function () {
            document.querySelectorAll('.filter-pill').forEach(p => p.classList.remove('active'));
            this.classList.add('active');
        });
    });

    // ---- Character counter for textareas -----------------------
    document.querySelectorAll('textarea[data-maxlength]').forEach(ta => {
        const max = parseInt(ta.dataset.maxlength);
        const counter = document.createElement('small');
        counter.className = 'text-muted float-end';
        ta.parentNode.insertBefore(counter, ta.nextSibling);
        const update = () => {
            const remaining = max - ta.value.length;
            counter.textContent = `${ta.value.length}/${max}`;
            counter.className = remaining < 50 ? 'text-warning float-end' : 'text-muted float-end';
        };
        ta.addEventListener('input', update);
        update();
    });

    // ---- File upload preview -----------------------------------
    const fileInput = document.getElementById('attachment');
    if (fileInput) {
        fileInput.addEventListener('change', function () {
            const preview = document.getElementById('file-preview');
            if (!preview) return;
            preview.innerHTML = '';
            Array.from(this.files).forEach(file => {
                const div = document.createElement('div');
                div.className = 'badge bg-light text-dark border me-1';
                div.innerHTML = `<i class="bi bi-paperclip me-1"></i>${file.name} (${formatBytes(file.size)})`;
                preview.appendChild(div);
            });
        });
    }

    // ---- Ticket search debounce --------------------------------
    const searchInput = document.getElementById('ticketSearch');
    if (searchInput) {
        let timer;
        searchInput.addEventListener('input', function () {
            clearTimeout(timer);
            timer = setTimeout(() => {
                const url = new URL(window.location);
                url.searchParams.set('search', this.value);
                url.searchParams.delete('page');
                window.location.href = url.toString();
            }, 500);
        });
    }

    // ---- Print ticket ------------------------------------------
    const printBtn = document.getElementById('printTicket');
    if (printBtn) printBtn.addEventListener('click', () => window.print());

});

// ---- Helpers ------------------------------------------------

function updateUnreadBadge(delta) {
    const badge = document.querySelector('.notif-btn .badge');
    if (!badge) return;
    const current = parseInt(badge.textContent) || 0;
    const newVal = current + delta;
    if (newVal <= 0) badge.remove();
    else badge.textContent = newVal > 9 ? '9+' : newVal;
}

function formatBytes(bytes) {
    if (bytes >= 1048576) return (bytes / 1048576).toFixed(1) + ' MB';
    if (bytes >= 1024)    return (bytes / 1024).toFixed(1) + ' KB';
    return bytes + ' B';
}

// Expose APP_URL from meta tag if present
const appUrlMeta = document.querySelector('meta[name="app-url"]');
window.APP_URL = appUrlMeta ? appUrlMeta.content : '';

// ---- Chart.js defaults --------------------------------------
if (typeof Chart !== 'undefined') {
    Chart.defaults.font.family = "'Plus Jakarta Sans', sans-serif";
    Chart.defaults.color = '#6c757d';
    Chart.defaults.plugins.legend.position = 'bottom';
    Chart.defaults.plugins.tooltip.padding = 10;
    Chart.defaults.plugins.tooltip.cornerRadius = 6;
    Chart.defaults.elements.bar.borderRadius = 4;
}

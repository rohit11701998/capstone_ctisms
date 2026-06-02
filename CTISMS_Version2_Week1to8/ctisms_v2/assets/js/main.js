// assets/js/main.js — Week 7 addition
document.addEventListener('DOMContentLoaded', function () {
    // Auto-dismiss alerts
    document.querySelectorAll('.alert').forEach(function (el) {
        setTimeout(function () {
            bootstrap.Alert.getOrCreateInstance(el)?.close();
        }, 4000);
    });
    // Confirm dialogs
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            if (!confirm(el.dataset.confirm)) e.preventDefault();
        });
    });
    // Tooltips
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
        new bootstrap.Tooltip(el);
    });
    // Week 7 — live search filter (client-side, basic)
    const searchInput = document.getElementById('liveSearch');
    if (searchInput) {
        searchInput.addEventListener('keyup', function () {
            const q = this.value.toLowerCase();
            document.querySelectorAll('tbody tr').forEach(function (row) {
                row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
            });
        });
    }
});

// assets/js/app.js

document.addEventListener('DOMContentLoaded', function () {

    // Sidebar toggle (desktop collapse)
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const mobileToggle = document.getElementById('mobileToggle');

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
        });
    }

    if (mobileToggle) {
        mobileToggle.addEventListener('click', () => {
            sidebar.classList.toggle('open');
        });
        // Close sidebar on outside click
        document.addEventListener('click', (e) => {
            if (sidebar && sidebar.classList.contains('open') &&
                !sidebar.contains(e.target) && !mobileToggle.contains(e.target)) {
                sidebar.classList.remove('open');
            }
        });
    }

    // Auto-dismiss flash messages
    const flash = document.getElementById('flashAlert');
    if (flash) {
        setTimeout(() => flash.remove(), 5000);
    }

    // Confirm delete dialogs
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', function (e) {
            if (!confirm(this.dataset.confirm || 'Are you sure?')) {
                e.preventDefault();
            }
        });
    });

    // Modal support
    document.querySelectorAll('[data-modal]').forEach(trigger => {
        trigger.addEventListener('click', () => {
            const modal = document.getElementById(trigger.dataset.modal);
            if (modal) modal.classList.add('open');
        });
    });
    document.querySelectorAll('.modal-close, .modal-overlay').forEach(el => {
        el.addEventListener('click', function (e) {
            if (e.target === this) {
                this.closest('.modal-overlay')?.classList.remove('open');
            }
        });
    });
    document.querySelectorAll('.modal-close').forEach(btn => {
        btn.addEventListener('click', () => {
            btn.closest('.modal-overlay').classList.remove('open');
        });
    });

    // Search input debounce
    const searchInputs = document.querySelectorAll('.live-search');
    searchInputs.forEach(input => {
        let timer;
        input.addEventListener('input', () => {
            clearTimeout(timer);
            timer = setTimeout(() => {
                input.closest('form')?.submit();
            }, 400);
        });
    });

});

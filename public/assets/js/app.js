'use strict';

document.documentElement.classList.add('js-enabled');

const mobileNavigation = document.getElementById('mobileNavigation');
const desktopBreakpoint = window.matchMedia('(min-width: 992px)');

if (mobileNavigation && window.bootstrap) {
    const closeMobileNavigation = (event) => {
        if (!event.matches) {
            return;
        }

        const offcanvas = window.bootstrap.Offcanvas.getInstance(mobileNavigation);

        if (offcanvas) {
            offcanvas.hide();
        }
    };

    desktopBreakpoint.addEventListener('change', closeMobileNavigation);
}

const logoutForms = document.querySelectorAll('.js-logout-form');
const logoutModalElement = document.getElementById('logoutConfirmationModal');
const logoutConfirmButton = document.getElementById('logoutConfirmButton');

if (logoutForms.length > 0) {
    if (logoutModalElement && logoutConfirmButton && window.bootstrap) {
        const logoutModal = new window.bootstrap.Modal(logoutModalElement);
        let pendingLogoutForm = null;

        logoutForms.forEach((form) => {
            form.addEventListener('submit', (event) => {
                event.preventDefault();
                pendingLogoutForm = form;
                logoutModal.show();
            });
        });

        logoutConfirmButton.addEventListener('click', () => {
            if (!pendingLogoutForm) {
                return;
            }

            const form = pendingLogoutForm;
            pendingLogoutForm = null;
            logoutModal.hide();
            form.submit();
        });

        logoutModalElement.addEventListener('hidden.bs.modal', () => {
            pendingLogoutForm = null;
        });
    } else {
        logoutForms.forEach((form) => {
            form.addEventListener('submit', (event) => {
                if (!window.confirm('Apakah Anda yakin ingin keluar dari aplikasi?')) {
                    event.preventDefault();
                }
            });
        });
    }
}

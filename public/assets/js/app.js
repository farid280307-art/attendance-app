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

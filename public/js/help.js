(function () {
    const widget = document.querySelector('[data-help-widget]');
    if (!widget) return;

    const toggle = widget.querySelector('[data-help-toggle]');
    const panel = widget.querySelector('[data-help-panel]');
    const close = widget.querySelector('[data-help-close]');
    const icon = widget.querySelector('[data-help-icon]');
    let lastFocused = null;
    const emailLink = widget.querySelector('[data-email-href]');

    const setOpen = (open) => {
        panel.classList.toggle('is-open', open);
        panel.setAttribute('aria-hidden', String(!open));
        toggle.setAttribute('aria-expanded', String(open));
        toggle.setAttribute('aria-label', open ? toggle.dataset.closeLabel || 'Close help center' : toggle.dataset.openLabel || 'Open help center');
        icon.textContent = open ? '✕' : '?';
        if (open) {
            lastFocused = document.activeElement;
            close.focus();
        } else if (lastFocused && typeof lastFocused.focus === 'function') {
            lastFocused.focus();
        }
    };

    toggle.addEventListener('click', () => setOpen(!panel.classList.contains('is-open')));
    close.addEventListener('click', () => setOpen(false));
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && panel.classList.contains('is-open')) setOpen(false);
    });
    document.addEventListener('click', (event) => {
        if (panel.classList.contains('is-open') && !widget.contains(event.target)) setOpen(false);
    });

    if (emailLink && emailLink.dataset.gmailHref) {
        emailLink.addEventListener('click', () => {
            let appOpened = false;
            const markAppOpened = () => {
                appOpened = true;
            };
            const fallbackTimer = window.setTimeout(() => {
                if (!appOpened && !document.hidden && document.hasFocus()) {
                    window.open(emailLink.dataset.gmailHref, '_blank', 'noopener,noreferrer');
                }
            }, 1200);

            window.addEventListener('blur', markAppOpened, { once: true });
            document.addEventListener('visibilitychange', markAppOpened, { once: true });
            window.setTimeout(() => window.clearTimeout(fallbackTimer), 5000);
        });
    }
}());

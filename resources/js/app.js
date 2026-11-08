import './bootstrap';

// Keep password visibility controls dependency-free and keyboard accessible.
document.querySelectorAll('[data-password-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        const input = document.getElementById(button.dataset.passwordToggle);

        if (!input) return;

        const visible = input.type === 'password';
        input.type = visible ? 'text' : 'password';
        button.setAttribute('aria-label', `${visible ? 'Hide' : 'Show'} ${input.labels?.[0]?.textContent?.trim().toLowerCase() || 'password'}`);
        button.querySelector('[data-eye-icon]')?.classList.toggle('hidden', visible);
        button.querySelector('[data-eye-off-icon]')?.classList.toggle('hidden', !visible);
    });
});

document.addEventListener('DOMContentLoaded', () => {
  const root = document;

  const toggleCollapse = (button) => {
    const target = root.querySelector(button.dataset.bsTarget);
    if (!target || target.classList.contains('collapsing')) return;

    const isOpen = target.classList.contains('show');
    button.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
    button.classList.toggle('collapsed', isOpen);
    target.classList.add('collapsing');
    target.classList.remove('collapse', 'show');
    target.style.height = isOpen ? `${target.scrollHeight}px` : '0px';
    target.offsetHeight;
    target.style.height = isOpen ? '0px' : `${target.scrollHeight}px`;

    window.setTimeout(() => {
      target.classList.remove('collapsing');
      target.classList.add('collapse');
      if (!isOpen) target.classList.add('show');
      target.style.height = '';
    }, 350);
  };

  root.addEventListener('click', (event) => {
    const button = event.target.closest?.('[data-bs-toggle="collapse"]');
    if (!button) return;

    event.preventDefault();
    toggleCollapse(button);
  });

  root.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((element) => {
    window.bootstrap?.Tooltip.getOrCreateInstance(element);
  });

  root.querySelectorAll('[data-permalink-field="permalink"]').forEach((field) => {
    const tokenButtons = field.parentElement.querySelectorAll('[data-permalink-token]');

    const refreshTokens = () => {
      tokenButtons.forEach((button) => {
        button.classList.toggle('d-none', field.value.includes(button.dataset.permalinkToken));
      });
    };

    tokenButtons.forEach((button) => {
      button.addEventListener('click', (event) => {
        event.preventDefault();
        const token = button.dataset.permalinkToken || '';
        if (!token || field.value.includes(token)) return;

        const start = field.selectionStart ?? field.value.length;
        const end = field.selectionEnd ?? start;
        const left = field.value.slice(0, start).replace(/\/+$/, '');
        const right = field.value.slice(end).replace(/^\/+/, '');
        const prefix = left ? `${left}/` : '';
        const suffix = right ? `/${right}` : '';
        field.value = `${prefix}${token}/${suffix}`.replace(/\/{2,}/g, '/');
        field.dispatchEvent(new Event('input', { bubbles: true }));
        field.focus();
      });
    });

    field.addEventListener('input', refreshTokens);
    refreshTokens();
  });
});
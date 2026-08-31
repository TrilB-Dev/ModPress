document.addEventListener('DOMContentLoaded', () => {
  const root = document;
  root.querySelectorAll('.modpress-editor-form').forEach((form) => {
    form.addEventListener('submit', () => {
      const submit = form.querySelector('[type="submit"]');
      if (submit) submit.disabled = true;
    });
  });
});
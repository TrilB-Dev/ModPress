document.addEventListener('DOMContentLoaded', () => {
  const root = document;
  const resetScope = root.querySelector('#modpress-reset-scope');
  const resetPlugins = root.querySelector('#modpress-reset-plugins');

  if (!resetScope || !resetPlugins) {
    return;
  }

  const applyResetScopeState = () => {
    const isPluginsScope = resetScope.value === 'plugins';
    resetPlugins.hidden = !isPluginsScope;
    resetPlugins.querySelectorAll('input[type="checkbox"]').forEach((checkbox) => {
      checkbox.disabled = !isPluginsScope;
    });
  };

  resetScope.addEventListener('change', applyResetScopeState);
  applyResetScopeState();
});

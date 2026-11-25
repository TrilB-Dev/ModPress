document.addEventListener('DOMContentLoaded', () => {
  const root = document;
  const integrationsEnabled = root.querySelector('#modpress-enable-integrations');
  const integrationSwitches = root.querySelector('#modpress-integration-switches');

  if (integrationsEnabled && integrationSwitches) {
    const applyIntegrationsState = () => {
      const isEnabled = integrationsEnabled.checked;
      integrationSwitches.classList.toggle('d-none', !isEnabled);
      integrationSwitches.querySelectorAll('input[type="checkbox"]').forEach((checkbox) => {
        checkbox.disabled = !isEnabled;
      });
    };

    integrationsEnabled.addEventListener('change', applyIntegrationsState);
    applyIntegrationsState();
  }

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

/**
  * Tools Manager JS
  *
  * @package: ModPress
  * @subpackage: Admin\Manager\Tools
  * @version 1.0.0
  * @since 1.0.0
  */
(() => {
  /**
   * Initialize integration-tool controls.
   *
   * @since 1.0.0
   */
  const initIntegrationTools = () => {
    const toolsIntegration = document.querySelector('.modpress-tools-integration-page');
    if (!toolsIntegration) {
      return;
    }

    const integrationsEnabled = toolsIntegration.querySelector('input#modpress-enable-integrations');
    const integrationSwitches = toolsIntegration.querySelector('#modpress-integration-switches');

    if (!integrationsEnabled || !integrationSwitches) {
      return;
    }

    const applyIntegrationsState = () => {
      const isEnabled = integrationsEnabled.checked;
      integrationSwitches.classList.toggle('d-none', !isEnabled);
      integrationSwitches.querySelectorAll('input[type="checkbox"]').forEach((checkbox) => {
        checkbox.disabled = !isEnabled;
      });
    };

    if (integrationsEnabled.dataset.modpressIntegrationBound !== 'true') {
      integrationsEnabled.dataset.modpressIntegrationBound = 'true';
      integrationsEnabled.addEventListener('change', applyIntegrationsState);
    }

    applyIntegrationsState();
  };

  /**
   * Initialize reset-tool controls.
   *
   * @since 1.0.0
   */
  const initResetTools = () => {
    const toolsReset = document.querySelector('.modpress-tools-reset-page');
    if (!toolsReset) {
      return;
    }

    const resetScope = toolsReset.querySelector('#modpress-reset-scope');
    const resetPlugins = toolsReset.querySelector('#modpress-reset-plugins');
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

    if (resetScope.dataset.modpressResetScopeBound !== 'true') {
      resetScope.dataset.modpressResetScopeBound = 'true';
      resetScope.addEventListener('change', applyResetScopeState);
    }

    applyResetScopeState();
  };

  const initToolsPages = () => {
    initIntegrationTools();
    initResetTools();
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initToolsPages, { once: true });
  } else {
    initToolsPages();
  }
})();

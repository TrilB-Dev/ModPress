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
  const toolsIntegration = document.getElementById('modpress-tools-integration-page');
  toolsIntegration && (() => {
    /**
     * Show or hide integration block based on switch state.
     * 
     * @since 1.0.0
     */
    const enableIntegrations = document.getElementById('modpress-enable-integrations');
    const integrationBlock = document.getElementById('modpress-integrations');

    enableIntegrations.addEventListener('change', function () {
      // Toggle d-none class based on switch state
      if (this.checked) {
        integrationBlock.classList.remove('d-none');
      } else {
        integrationBlock.classList.add('d-none');
      }
    });
  })();

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
    initResetTools();
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initToolsPages, { once: true });
  } else {
    initToolsPages();
  }
})();

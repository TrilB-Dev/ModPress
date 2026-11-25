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
     * Integration Tools Page
     * 
     * This script handles the functionality for the Integration Tools page.
     * @since 1.0.0
     */
    const toolsIntegration = document.querySelector('.modpress-tools-integration-page');
    toolsIntegration && (() => {

      const integrationsEnabled = toolsIntegration.querySelector('#modpress-enable-integrations');
      const integrationSwitches = toolsIntegration.querySelector('#modpress-integration-switches');

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
    })();
    /**
     * Reset Tools Page
     *
     * This script handles the functionality for the Reset Tools page.
     * @since 1.0.0
     */
    const toolsReset = document.querySelector('.modpress-tools-reset-page');
    toolsReset && (() => {
      const resetScope = toolsReset.querySelector('#modpress-reset-scope');
      const resetPlugins = toolsReset.querySelector('#modpress-reset-plugins');

      if (resetScope && resetPlugins) {
        const applyResetScopeState = () => {
          const isPluginsScope = resetScope.value === 'plugins';
          resetPlugins.hidden = !isPluginsScope;
          resetPlugins.querySelectorAll('input[type="checkbox"]').forEach((checkbox) => {
            checkbox.disabled = !isPluginsScope;
          });
        };

        resetScope.addEventListener('change', applyResetScopeState);
        applyResetScopeState();
      }
    })();
})();

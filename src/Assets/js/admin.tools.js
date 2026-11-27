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
  if (!toolsIntegration) {
    console.warn('[ModPress] Integration tools page wrapper not found.', {
      wrapperId: 'modpress-tools-integration-page',
    });
  } else {
    /**
     * Show or hide integration block based on switch state.
     * 
     * @since 1.0.0
     */
    const enableIntegrations = document.getElementById('modpress-enable-integrations');
    const integrationBlock = document.getElementById('modpress-integrations');

    if (!enableIntegrations) {
      console.error('[ModPress] Integrations enable switch not found.', {
        switchId: 'modpress-enable-integrations',
      });
    }
    if (!integrationBlock) {
      console.error('[ModPress] Integrations switch block not found.', {
        blockId: 'modpress-integrations',
      });
    }

    if (enableIntegrations && integrationBlock) {
      const applyIntegrationsState = () => {
        try {
          if (enableIntegrations.checked) {
            integrationBlock.classList.remove('d-none');
          } else {
            integrationBlock.classList.add('d-none');
          }
          console.info('[ModPress] Integration switch state applied.', {
            enabled: enableIntegrations.checked,
            blockId: integrationBlock.id,
          });
        } catch (error) {
          console.error('[ModPress] Failed to apply integration switch state.', {
            error: error instanceof Error ? error.message : String(error),
            enabled: enableIntegrations.checked,
            blockId: integrationBlock.id,
          });
        }
      };

      enableIntegrations.addEventListener('change', applyIntegrationsState);
      applyIntegrationsState();
    }
  }

  /**
   * Initialize reset-tool controls.
   *
   * @since 1.0.0
   */
  const initResetTools = () => {
    const toolsReset = document.querySelector('.modpress-tools-reset-page');
    if (!toolsReset) {
      console.warn('[ModPress] Reset tools page wrapper not found.');
      return;
    }

    const resetScope = toolsReset.querySelector('#modpress-reset-scope');
    const resetPlugins = toolsReset.querySelector('#modpress-reset-plugins');
    if (!resetScope || !resetPlugins) {
      console.error('[ModPress] Reset scope controls missing.', {
        resetScopeFound: Boolean(resetScope),
        resetPluginsFound: Boolean(resetPlugins),
      });
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

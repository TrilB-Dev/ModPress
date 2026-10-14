<?php
/**
 * Base manager for ModPress mod admin screens.
 *
 * @package ModPress
 * @subpackage Admin\Manager\Mods
 * @since 1.0.0
 */
namespace ModPress\Admin\Manager\Mods;

use ModPress\Admin\Manager\Manager;
use ModPress\Assets\Assets;
use ModPress\Includes\Functions\Helpers\PermissionHelper;
use ModPress\Includes\Functions\Helpers\RequestHelper;
use ModPress\Includes\Functions\Admin\FunctionsMod;
use ModPress\Includes\Functions\Helpers\SanitizationHelper;
use ModPress\Includes\Functions\Helpers\FormFieldHelper;
use ModPress\Includes\Functions\Helpers\AlertHelper;

if ( ! defined( 'ABSPATH' ) ) {
exit;
}

class ModManager extends Manager {
    /**
     * The Page variable.
     *
     * @since 1.0.0
     * @access protected
     * @var string $page
     */
    protected string $page;
    /**
     * Instances of the mod manager tabs.
     * 
     * @since 1.0.0
     * @access private
     * @var ModDashboard $mod_dashboard
     */
    private ModDashboard $mod_dashboard;
    /**
     * The mod groups tab instance.
     * 
     * @since 1.0.0
     * @access private
     * @var ModGroups $mod_groups.
     */
    private ModGroups $mod_groups;
    /**
     * The mod tags tab instance.
     * 
     * @since 1.0.0
     * @access private
     * @var ModTags $mod_tags
     */
    private ModTags $mod_tags;
    /**
     * The mod changelogs tab instance.
     * 
     * @since 1.0.0
     * @access private
     * @var ModChangeLogs $mod_changelogs
     */
    private ModChangeLogs $mod_changelogs;
    /**
     * The mod files tab instance.
     * 
     * @since 1.0.0
     * @access private
     * @var ModFiles $mod_files
     */
    private ModFiles $mod_files;
    /**
     * The mods tab instance.
     * 
     * @since 1.0.0
     * @access private
     * @var Mods $mods
     */
    private Mods $mods;
    /**
     * The functions mod tab instance.
     * 
     * @since 1.0.0
     * @access private
     * @var FunctionsMod $functions_mod
     */
    private FunctionsMod $functions_mod;

    /**
     * Supported mod tabs.
     *
     * @var array<int, string>
     * @since 1.0.0
     */
    protected array $tabs;
    /**
     * Initialize the mod manager.
     *
     * @since 1.0.0
     */
    public function __construct() {
        /**
         * Initialize the base mod manager state.
         *
         * @since 1.0.0
         */
        $this->page = 'mod-manager';
        /**
         * Initialize the supported mod tabs.
         *
         * @since 1.0.0
         */
        $this->tabs = array(
            'dashboard',
            'groups',
            'changelog',
            'files',
            'mods',
            'tags',
        );
        /**
         * Initialize the instances for each mod manager tab.
         *
         * @since 1.0.0
         */
        $this->mod_dashboard = new ModDashboard();
        /**
         * Initialize the mod groups tab instance.
         *
         * @since 1.0.0
         */
        $this->mod_groups = new ModGroups();
        /**
         * Initialize the mod changelogs tab instance.
         *
         * @since 1.0.0
         */
        $this->mod_changelogs = new ModChangeLogs();
        /**
         * Initialize the mod files tab instance.
         *
         * @since 1.0.0
         */
        $this->mod_files = new ModFiles();
        /**
         * Initialize the mods tab instance.
         *
         * @since 1.0.0
         */
        $this->mods = new Mods();
        /**
         * Initialize the mod tags tab instance.
         *
         * @since 1.0.0
         */
        $this->mod_tags = new ModTags();
        /**
         * Initialize the functions mod tab instance.
         *
         * @since 1.0.0
         */
        $this->functions_mod = new FunctionsMod();
        /**
         * Register the actions for the mod manager.
         *
         * @since 1.0.0
         */
        $this->register_actions();
    }
    /**
     * Register the mod manager admin-post handlers used by taxonomy forms.
     *
     * @return void
     * @since 1.0.0
     */
    public function register_actions(): void {
        add_action( 'admin_post_modpress_save_group', array( $this->functions_mod, 'save_group' ) );
        add_action( 'admin_post_modpress_save_group_entry', array( $this->functions_mod, 'save_group_entry' ) );
        add_action( 'admin_post_modpress_save_tags', array( $this->functions_mod, 'save_tags' ) );
        add_action( 'admin_post_modpress_save_files', array( $this->functions_mod, 'save_files' ) );
        add_action( 'admin_post_modpress_save_file_type', array( $this->functions_mod, 'save_file_type' ) );
        add_action( 'admin_post_modpress_save_changelog', array( $this->functions_mod, 'save_changelog' ) );
        add_action( 'admin_post_modpress_save_changelog_category', array( $this->functions_mod, 'save_changelog_category' ) );
        add_action( 'admin_post_modpress_save_mod', array( $this->functions_mod, 'save_mod' ) );
    }
    /**
     * Register the assets for the mod manager.
     *
     * @param Assets $assets Assets instance.
     * @return void
     * @since 1.0.0
     */
    public function register_assets(Assets $assets): void {

        $this->register_page_assets( $assets, array( 'modpress-mods' ), 'mods' );
    }
    /**
     * Render the mod manager shell.
     *
     * @return void
     * @since 1.0.0
     */
    public function render(): void {
        $tab = SanitizationHelper::key( wp_unslash( $_GET['tab'] ?? 'dashboard' ), 'general' );
        $tab = $this->normalize_tab( $tab );
        $capabilities = array(
            'dashboard' => 'modpress_manager_dashboard',
            'groups'    => 'modpress_manager_groups_view',
            'tags'      => 'modpress_manager_tags_view',
            'changelog' => 'modpress_manager_mods_view',
            'files'     => 'modpress_manager_mods_view',
            'mods'      => 'modpress_manager_mods_view',
        );

        if ( ! PermissionHelper::can( $capabilities[ $tab ] ?? 'modpress_manager_dashboard' ) ) {
            wp_die( esc_html__( 'You are not authorized to access this ModPress screen.', 'modpress' ) );
        }

        $this->header( $this->title( $tab ) );
        $this->render_tab_content( $tab );
        $this->footer();
    }

    /**
     * Render the content for a specific tab.
     *
     * @param string $tab Tab slug.
     * @return void
     * @since 1.0.0
     */
    public function render_tab_content( string $tab ): void {
        $tab = $this->normalize_tab( $tab );

        $view_capabilities = [
            'dashboard' => [ 
                'modpress_manager_mods_view' 
            ],
            'changelog' => [ 
                'modpress_manager_mods_view' 
            ],
            'mods' => [ 
                'modpress_manager_mods_view' 
            ],
            'groups' => [ 
                'modpress_manager_groups_view' 
            ],
            'files' => [ 
                'modpress_manager_mods_view' 
            ],
            'tags' => [ 
                'modpress_manager_tags_view' 
            ],
        ];
        $can_view = true;
        foreach ( $view_capabilities[ $tab ] ?? [] as $capability ) {
            if ( current_user_can( 'manage_options' ) || current_user_can( $capability ) ) {
                continue;
            }

            $can_view = false;
            break;
        }
        if ( ! $can_view ) {
            wp_die( esc_html__( 'You are not authorized to view these Mod Manager pages.', 'modpress' ) );
        }

        ?>
        <?php if ( 'dashboard' === $tab) : ?>
            <?php $this->mod_dashboard->render(); ?>
        <?php else : ?>
            <div class="modpress-settings-tab-content" role="tabpanel">
                <?php if ( 'mods' === $tab ) : ?>
                    <?php $this->mods->render(); ?>
                <?php elseif ( 'groups' === $tab ) : ?>
                    <?php $this->mod_groups->render(); ?>
                <?php elseif ( 'changelog' === $tab ) : ?>
                    <?php $this->mod_changelogs->render(); ?>
                <?php elseif ( 'files' === $tab ) : ?>
                    <?php $this->mod_files->render(); ?>
                <?php elseif ( 'tags' === $tab ) : ?>
                    <?php $this->mod_tags->render(); ?>
                <?php endif; ?>

            </div>
        <?php endif; ?>
        <?php
    }

    /**
     * Render the specific page content for a screen class.
     *
     * @return void
     * @since 1.0.0
     */
    public function render_page_content(): void {
    // Intentionally left for concrete screen classes.
    }

    /**
     * Normalize a route alias.
     *
     * @param string $tab Raw tab.
     * @return string
     * @since 1.0.0
     */
    protected function normalize_tab( string $tab ): string {
        $allowed = [ 
            'dashboard', 
            'mods', 
            'groups', 
            'changelog', 
            'files', 
            'tags' 
        ];

        if ( in_array( $tab, $allowed, true ) ) {
            return $tab;
        }
        return 'dashboard';
    }

    /**
     * Get the page title for a given tab.
     *
     * @param string $tab Tab slug.
     * @return string
     * @since 1.0.0
     */
    protected function title( string $tab ): string {
        $labels = array(
            'dashboard'     => __( 'Mod Manager - Dashboard', 'modpress' ),
            'groups'        => __( 'Mod Manager - Groups', 'modpress' ),
            'changelog'     => __( 'Mod Manager - Change Logs', 'modpress' ),
            'files'         => __( 'Mod Manager - Files', 'modpress' ),
            'mods'          => __( 'Mod Manager - Mods', 'modpress' ),
            'tags'          => __( 'Mod Manager - Tags', 'modpress' ),
        );

        return $labels[ $tab ] ?? $labels['dashboard'];
    }
}

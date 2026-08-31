<?php
/**
 * FontAwesome Icon Picker Class
 *
 * Provides functionality for browsing and selecting FontAwesome icons
 *
 * @package ModPress
 * @subpackage Includes\FontAwesome
 * @since 1.0.0
 */

namespace ModPress\Includes\Plugins\FontAwesome\Includes;

use Exception;
use Throwable;
use ModPress\Includes\Functions\Helpers\AjaxHelper;
use ModPress\Includes\Functions\Helpers\LoggerHelper;
use ModPress\Includes\Functions\Helpers\LoaderHelper;
use ModPress\Includes\Functions\Helpers\RequestHelper;
use ModPress\Includes\Functions\Helpers\FormFieldHelper;

// Import FontAwesome functions
use function FortAwesome\fa;
use FortAwesome\FontAwesome as FAFontAwesome;

/**
 * Class IconPicker
 *
 * Handles the FontAwesome icon picker functionality.
 */
class IconPicker {

    /**
     * Singleton instance.
     *
     * @var IconPicker
     */
    private static $instance = null;
    private LoaderHelper $loader;

    /**
     * Check if FontAwesome is available and properly initialized.
     *
     * @return bool
     */
    private function is_fontawesome_available() {
        // Check if the fa function exists (primary check)
        if (!function_exists('fa')) {
            // Try FontAwesome::instance() directly
            if (!class_exists('FortAwesome\\FontAwesome')) {
                return false;
            }
            
            try {
                $fa = FAFontAwesome::instance();
                $fa->version(); // Test if it's working
                return true;
            } catch (Throwable $e) {
                return false;
            }
        }

        try {
            $fa = fa();
            if (!$fa) {
                return false;
            }

            // Try a simple method call to test if it's working
            $fa->version();
            return true;

        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Cached icon data
     *
     * @var array
     */
    private $icon_cache = array();

    /**
     * Get singleton instance.
     *
     * @return IconPicker
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct() {
        $this->loader = new LoaderHelper();
        $this->init();
    }

    /**
     * Initialize the icon picker.
     */
    private function init() {
        // Only initialize WordPress hooks if WordPress functions are available
        if ( function_exists( 'add_action' ) ) {
            $this->loader->register_component( $this, [
                [ 'type' => 'action', 'hook' => 'wp_ajax_modpress_fontawesome_search_icons', 'callback' => 'ajax_search_icons' ],
                [ 'type' => 'action', 'hook' => 'wp_ajax_modpress_fontawesome_get_icon_data', 'callback' => 'ajax_get_icon_data' ],
            ] )->run();
        }
    }

    /**
     * AJAX handler for searching icons.
     */
    public function ajax_search_icons() {
        if ( ! AjaxHelper::has_valid_nonce( 'modpress_fontawesome_picker' ) ) {
            AjaxHelper::unauthorized( 'Security check failed.' );
        }

        $search = RequestHelper::text( $_POST, 'search' );
        $pack = RequestHelper::text( $_POST, 'pack', 'classic' );
        $style = RequestHelper::text( $_POST, 'style', 'solid' );
        $page = RequestHelper::integer( $_POST, 'page', 1 );
        $per_page = RequestHelper::integer( $_POST, 'per_page', 50 );

        $icons = $this->search_icons( $search, $pack, $style, $page, $per_page );

        AjaxHelper::success( $icons );
    }

    /**
     * AJAX handler for getting icon data.
     */
    public function ajax_get_icon_data() {
        if ( ! AjaxHelper::has_valid_nonce( 'modpress_fontawesome_picker' ) ) {
            AjaxHelper::unauthorized( 'Security check failed.' );
        }

        $icon_name = RequestHelper::text( $_POST, 'icon_name' );
        $style = RequestHelper::text( $_POST, 'style', 'fas' );

        $icon_data = $this->get_icon_data( $icon_name, $style );

        if ( $icon_data ) {
            AjaxHelper::success( $icon_data );
        } else {
            AjaxHelper::error( 'Icon not found' );
        }
    }

    /**
     * Search for icons based on criteria.
     *
     * @param string $search Search term
     * @param string $pack Icon pack (classic, duotone, sharp, sharp-duotone)
     * @param string $style Icon style (solid, regular, light, thin)
     * @param int $page Page number
     * @param int $per_page Items per page
     * @return array
     */
    public function search_icons( $search = '', $pack = 'classic', $style = 'solid', $page = 1, $per_page = 50 ) {
        if ( ! $this->is_fontawesome_available() ) {
            // Provide fallback icons when FontAwesome is not available
            $fallback_icons = $this->get_fallback_icons( $pack, $style, $search );
            $total = count( $fallback_icons );
            $offset = ( $page - 1 ) * $per_page;
            $icons = array_slice( $fallback_icons, $offset, $per_page );

            return array(
                'icons' => array_values( $icons ),
                'total' => $total,
                'page' => $page,
                'per_page' => $per_page,
                'total_pages' => ceil( $total / $per_page ),
                'prefix' => $this->get_fontawesome_prefix( $pack, $style ),
            );
        }

        try {
            $fa = fa();

            // Get available icons from FontAwesome API
            $all_icons = $this->get_available_icons_from_api( $pack, $style );

            // Filter by search term
            if ( ! empty( $search ) ) {
                $all_icons = array_filter( $all_icons, function( $icon ) use ( $search ) {
                    return stripos( $icon['name'], $search ) !== false ||
                           stripos( $icon['label'], $search ) !== false ||
                           ( isset( $icon['search_terms'] ) && is_array( $icon['search_terms'] ) &&
                             array_filter( $icon['search_terms'], function( $term ) use ( $search ) {
                                 return stripos( $term, $search ) !== false;
                             } ) );
                } );
            }

            // Paginate results
            $total = count( $all_icons );
            $offset = ( $page - 1 ) * $per_page;
            $icons = array_slice( $all_icons, $offset, $per_page );

            return array(
                'icons' => array_values( $icons ),
                'total' => $total,
                'page' => $page,
                'per_page' => $per_page,
                'total_pages' => ceil( $total / $per_page ),
                'prefix' => $this->get_fontawesome_prefix( $pack, $style ),
            );

        } catch ( Exception $e ) {
            LoggerHelper::write_log( 'ModPress FontAwesome IconPicker: Error searching icons: ' . $e->getMessage() );
            // Fallback to static icons on error
            $fallback_icons = $this->get_fallback_icons( $pack, $style, $search );
            $total = count( $fallback_icons );
            $offset = ( $page - 1 ) * $per_page;
            $icons = array_slice( $fallback_icons, $offset, $per_page );

            return array(
                'icons' => array_values( $icons ),
                'total' => $total,
                'page' => $page,
                'per_page' => $per_page,
                'total_pages' => ceil( $total / $per_page ),
                'prefix' => $this->get_fontawesome_prefix( $pack, $style ),
            );
        }
    }

    /**
     * Get data for a specific icon.
     *
     * @param string $icon_name Icon name
     * @param string $pack Icon pack
     * @param string $style Icon style
     * @return array|null
     */
    public function get_icon_data( $icon_name, $pack = 'classic', $style = 'solid' ) {
        if ( ! $this->is_fontawesome_available() ) {
            return null;
        }

        try {
            // Convert pack/style to FontAwesome prefix
            $prefix = $this->get_fontawesome_prefix( $pack, $style );

            // Use FontAwesome's icon definition if available
            if ( function_exists( 'FortAwesome\findIconDefinition' ) ) {
                $definition = \FortAwesome\findIconDefinition( array(
                    'prefix' => $prefix,
                    'iconName' => $icon_name,
                ) );

                if ( $definition ) {
                    return array(
                        'name' => $icon_name,
                        'pack' => $pack,
                        'style' => $style,
                        'prefix' => $prefix,
                        'class' => $prefix . ' fa-' . $icon_name,
                        'unicode' => $definition->icon[3], // Unicode character
                        'svg' => $this->definition_to_svg( $definition ),
                        'definition' => $definition,
                    );
                }
            }

            // Fallback to basic data
            return array(
                'name' => $icon_name,
                'pack' => $pack,
                'style' => $style,
                'prefix' => $prefix,
                'class' => $prefix . ' fa-' . $icon_name,
                'unicode' => '', // Would need to be looked up
                'svg' => '',
            );

        } catch ( Exception $e ) {
            LoggerHelper::write_log( 'ModPress FontAwesome IconPicker: Error getting icon data: ' . $e->getMessage() );
            return null;
        }
    }

    /**
     * Convert FontAwesome icon definition to SVG.
     *
     * @param object $definition FontAwesome icon definition
     * @return string
     */
    private function definition_to_svg( $definition ) {
        if ( ! $definition || ! isset( $definition->icon ) ) {
            return '';
        }

        $paths = array();
        foreach ( $definition->icon[4] as $path ) {
            $paths[] = '<path d="' . $path . '" />';
        }

        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 %d %d" class="svg-inline--fa">%s</svg>',
            $definition->icon[0], // width
            $definition->icon[1], // height
            implode( '', $paths )
        );
    }

    /**
     * Get FontAwesome prefix for pack and style combination.
     *
     * @param string $pack Icon pack
     * @param string $style Icon style
     * @return string
     */
    private function get_fontawesome_prefix( $pack, $style ) {
        $prefix_map = array(
            'classic' => array(
                'solid' => 'fas',
                'regular' => 'far',
                'light' => 'fal',
                'thin' => 'fat',
            ),
            'duotone' => array(
                'solid' => 'fad',
                'regular' => 'fadr',
                'light' => 'fadl',
                'thin' => 'fadt',
            ),
            'sharp' => array(
                'solid' => 'fass',
                'regular' => 'fasr',
                'light' => 'fasl',
                'thin' => 'fast',
            ),
            'sharp-duotone' => array(
                'solid' => 'fasd',
                'regular' => 'fasdr',
                'light' => 'fasdl',
                'thin' => 'fasdt',
            ),
            'brands' => array(
                'solid' => 'fab',
            ),
        );

        return $prefix_map[$pack][$style] ?? 'fas';
    }

    /**
     * Get available icons from FontAwesome API.
     *
     * @param string $pack Icon pack
     * @param string $style Icon style
     * @return array
     */
    private function get_available_icons_from_api( $pack, $style ) {
        $cache_key = 'fa_icons_' . $pack . '_' . $style;

        if ( isset( $this->icon_cache[$cache_key] ) ) {
            return $this->icon_cache[$cache_key];
        }

        if ( ! $this->is_fontawesome_available() ) {
            return array();
        }

        try {
            $fa = \FortAwesome\fa();

            // Try to get icons from FontAwesome GraphQL API or metadata
            $icons = $this->query_fontawesome_api( $pack, $style );

            $this->icon_cache[$cache_key] = $icons;
            return $icons;

        } catch ( Exception $e ) {
            LoggerHelper::write_log( 'ModPress FontAwesome IconPicker: Error getting icons from API: ' . $e->getMessage() );
            return array();
        }
    }

    /**
     * Query FontAwesome API for icons.
     *
     * @param string $pack Icon pack
     * @param string $style Icon style
     * @return array
     */
    private function query_fontawesome_api( $pack, $style ) {
        if ( ! $this->is_fontawesome_available() ) {
            return array();
        }

        try {
            $fa = fa();

            // Build GraphQL query to get icons for the specified pack and style
            $query = 'query {
                release(version: "latest") {
                    icons {
                        id
                        label
                        membership {
                            free
                            pro
                        }
                        styles
                        unicode
                    }
                }
            }';

            LoggerHelper::write_log( 'ModPress FontAwesome IconPicker: Executing GraphQL query for pack=' . $pack . ', style=' . $style );

            // Execute the query using FontAwesome plugin's API
            $response_json = $fa->query( $query );
            $response = json_decode( $response_json, true );

            if ( json_last_error() !== JSON_ERROR_NONE ) {
                LoggerHelper::write_log( 'ModPress FontAwesome IconPicker: JSON decode error: ' . json_last_error_msg() );
                return array();
            }

            if ( ! isset( $response['data']['release']['icons'] ) ) {
                LoggerHelper::write_log( [
                    'message' => 'ModPress FontAwesome IconPicker: Invalid API response structure.',
                    'response' => $response,
                ] );
                return array();
            }

            $all_icons = $response['data']['release']['icons'];
            LoggerHelper::write_log( 'ModPress FontAwesome IconPicker: Retrieved ' . count( $all_icons ) . ' total icons from API' );

            $icons = array();
            foreach ( $all_icons as $icon_data ) {
                // Filter icons based on pack and style availability
                if ( ! $this->icon_matches_pack_style( $icon_data, $pack, $style ) ) {
                    continue;
                }

                // Build search terms array (empty for now since search field not available)
                $search_terms = array();

                $icons[] = array(
                    'name' => $icon_data['id'],
                    'label' => $icon_data['label'],
                    'search_terms' => $search_terms,
                );
            }

            LoggerHelper::write_log( 'ModPress FontAwesome IconPicker: Filtered to ' . count( $icons ) . ' icons for pack=' . $pack . ', style=' . $style );
            return $icons;

        } catch ( Exception $e ) {
            LoggerHelper::write_log( 'ModPress FontAwesome IconPicker: Error querying FontAwesome API: ' . $e->getMessage() );
            LoggerHelper::write_log( 'ModPress FontAwesome IconPicker: Exception trace: ' . $e->getTraceAsString() );
            return array();
        }
    }
    private function icon_matches_pack_style( $icon_data, $pack, $style ) {
        $membership = $icon_data['membership'] ?? array();
        $free_styles = $membership['free'] ?? array();
        $pro_styles = $membership['pro'] ?? array();
        $all_styles = $icon_data['styles'] ?? array();

        // Map pack/style to the style name used in membership
        $membership_style_map = array(
            'classic' => array(
                'solid' => 'solid',
                'regular' => 'regular',
                'light' => 'light',
                'thin' => 'thin',
            ),
            'duotone' => array(
                'solid' => 'duotone',
                'regular' => 'duotone',
                'light' => 'duotone',
                'thin' => 'duotone',
            ),
            'sharp' => array(
                'solid' => 'sharp',
                'regular' => 'sharp',
                'light' => 'sharp',
                'thin' => 'sharp',
            ),
            'sharp-duotone' => array(
                'solid' => 'sharp-duotone',
                'regular' => 'sharp-duotone',
                'light' => 'sharp-duotone',
                'thin' => 'sharp-duotone',
            ),
            'brands' => array(
                'solid' => 'brands',
            ),
        );

        $membership_style = $membership_style_map[$pack][$style] ?? null;
        if ( ! $membership_style ) {
            return false;
        }

        // Check if the style is available in free or pro membership
        return in_array( $membership_style, $free_styles ) || in_array( $membership_style, $pro_styles );
    }

    /**
     * Render the icon picker HTML.
     *
     * @param string $input_name Name attribute for the hidden input
     * @param string $selected_icon Currently selected icon (class or name)
     * @param array $args Additional arguments
     * @return string
     */
    public function render_picker( $input_name, $selected_icon = '', $args = array() ) {
        $args = wp_parse_args( $args, array(
            'id' => uniqid( 'fa-picker-' ),
            'input_id' => '',
            'class' => 'modpress-fa-picker',
            'button_text' => __( 'Choose Icon', 'modpress' ),
            'preview_size' => '24px',
        ) );

        if ( empty( $args['input_id'] ) ) {
            $args['input_id'] = $args['id'] . '-input';
        }

        $selected_data = $this->parse_icon_value( $selected_icon );

        ob_start();
        ?>
        <div class="modpress-fa-picker-container" id="<?php echo esc_attr( $args['id'] ); ?>">
                 <?php echo wp_kses_post( FormFieldHelper::input( $input_name, $selected_icon, [ 'type' => 'hidden', 'id' => $args['input_id'], 'class' => 'modpress-fa-picker-input' ] ) ); ?>

                <button type="button"
                    class="btn btn-secondary modpress-fa-picker-button"
                    data-picker-id="<?php echo esc_attr( $args['id'] ); ?>">
                <span class="modpress-fa-picker-preview">
                    <?php if ( $selected_data ) : ?>
                        <i class="<?php echo esc_attr( $selected_data['class'] ); ?>"
                           style="font-size: <?php echo esc_attr( $args['preview_size'] ); ?>"></i>
                    <?php endif; ?>
                </span>
                <span class="modpress-fa-picker-text">
                    <?php echo esc_html( $args['button_text'] ); ?>
                </span>
            </button>

            <div class="modpress-fa-picker-modal" style="display: none;">
                <div class="modpress-fa-picker-overlay"></div>
                <div class="modpress-fa-picker-dialog">
                    <div class="modpress-fa-picker-header">
                        <h3><?php _e( 'Choose an Icon', 'modpress' ); ?></h3>
                        <button type="button" class="modpress-fa-picker-close">&times;</button>
                    </div>

                    <div class="modpress-fa-picker-search">
                        <?php echo wp_kses_post( FormFieldHelper::input( 'modpress_fa_search', '', [ 'type' => 'search', 'class' => 'modpress-fa-picker-search-input', 'placeholder' => __( 'Search icons...', 'modpress' ) ] ) ); ?>
                        <?php echo wp_kses_post( FormFieldHelper::select( 'modpress_fa_pack', [ 'classic' => __( 'Classic', 'modpress' ), 'duotone' => __( 'Duotone', 'modpress' ), 'sharp' => __( 'Sharp', 'modpress' ), 'sharp-duotone' => __( 'Sharp Duotone', 'modpress' ), 'brands' => __( 'Brands', 'modpress' ) ], 'classic', [ 'class' => 'modpress-fa-picker-pack-filter' ] ) ); ?>
                        <?php echo wp_kses_post( FormFieldHelper::select( 'modpress_fa_style', [ 'solid' => __( 'Solid', 'modpress' ), 'regular' => __( 'Regular', 'modpress' ), 'light' => __( 'Light', 'modpress' ), 'thin' => __( 'Thin', 'modpress' ) ], 'solid', [ 'class' => 'modpress-fa-picker-style-filter' ] ) ); ?>
                    </div>

                    <div class="modpress-fa-picker-results">
                        <div class="modpress-fa-picker-loading">
                            <?php _e( 'Loading icons...', 'modpress' ); ?>
                        </div>
                    </div>

                    <div class="modpress-fa-picker-footer">
                        <button type="button" class="btn btn-secondary modpress-fa-picker-cancel">
                            <?php _e( 'Cancel', 'modpress' ); ?>
                        </button>
                        <button type="button" class="btn btn-primary modpress-fa-picker-select" disabled>
                            <?php _e( 'Select Icon', 'modpress' ); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Parse icon value into components.
     *
     * @param string $icon_value Icon class or name
     * @return array|null
     */
    private function parse_icon_value( $icon_value ) {
        if ( empty( $icon_value ) ) {
            return null;
        }

        // Check if it's a full class string with FontAwesome prefix
        if ( preg_match( '/^(fa[sdrl][a-z]*)\s+fa-([a-z0-9-]+)$/', $icon_value, $matches ) ) {
            $prefix = $matches[1];
            $name = $matches[2];

            // Convert prefix back to pack/style
            $pack_style = $this->parse_fontawesome_prefix( $prefix );

            return array(
                'pack' => $pack_style['pack'],
                'style' => $pack_style['style'],
                'prefix' => $prefix,
                'name' => $name,
                'class' => $icon_value,
            );
        }

        // Check if it's just a name
        if ( preg_match( '/^[a-z0-9-]+$/', $icon_value ) ) {
            return array(
                'pack' => 'classic',
                'style' => 'solid',
                'prefix' => 'fas',
                'name' => $icon_value,
                'class' => 'fas fa-' . $icon_value,
            );
        }

        return null;
    }

    /**
     * Parse FontAwesome prefix into pack and style.
     *
     * @param string $prefix FontAwesome prefix
     * @return array
     */
    private function parse_fontawesome_prefix( $prefix ) {
        $reverse_map = array(
            'fas' => array( 'pack' => 'classic', 'style' => 'solid' ),
            'far' => array( 'pack' => 'classic', 'style' => 'regular' ),
            'fal' => array( 'pack' => 'classic', 'style' => 'light' ),
            'fat' => array( 'pack' => 'classic', 'style' => 'thin' ),
            'fab' => array( 'pack' => 'brands', 'style' => 'solid' ),
            'fad' => array( 'pack' => 'duotone', 'style' => 'solid' ),
            'fadr' => array( 'pack' => 'duotone', 'style' => 'regular' ),
            'fadl' => array( 'pack' => 'duotone', 'style' => 'light' ),
            'fadt' => array( 'pack' => 'duotone', 'style' => 'thin' ),
            'fass' => array( 'pack' => 'sharp', 'style' => 'solid' ),
            'fasr' => array( 'pack' => 'sharp', 'style' => 'regular' ),
            'fasl' => array( 'pack' => 'sharp', 'style' => 'light' ),
            'fast' => array( 'pack' => 'sharp', 'style' => 'thin' ),
            'fasd' => array( 'pack' => 'sharp-duotone', 'style' => 'solid' ),
            'fasdr' => array( 'pack' => 'sharp-duotone', 'style' => 'regular' ),
            'fasdl' => array( 'pack' => 'sharp-duotone', 'style' => 'light' ),
            'fasdt' => array( 'pack' => 'sharp-duotone', 'style' => 'thin' ),
        );

        return $reverse_map[$prefix] ?? array( 'pack' => 'classic', 'style' => 'solid' );
    }

    /**
     * Get fallback icons when FontAwesome is not available
     *
     * @param string $pack Icon pack
     * @param string $style Icon style
     * @param string $search Search term
     * @return array
     */
    private function get_fallback_icons( $pack = 'classic', $style = 'solid', $search = '' ) {
        // Common FontAwesome icons that should be available
        $common_icons = array(
            'user' => 'User',
            'home' => 'Home',
            'search' => 'Search',
            'heart' => 'Heart',
            'star' => 'Star',
            'check' => 'Check',
            'times' => 'Times',
            'plus' => 'Plus',
            'minus' => 'Minus',
            'cog' => 'Cog',
            'wrench' => 'Wrench',
            'trash' => 'Trash',
            'edit' => 'Edit',
            'save' => 'Save',
            'download' => 'Download',
            'upload' => 'Upload',
            'file' => 'File',
            'folder' => 'Folder',
            'image' => 'Image',
            'video' => 'Video',
            'music' => 'Music',
            'play' => 'Play',
            'pause' => 'Pause',
            'stop' => 'Stop',
            'chevron-left' => 'Chevron Left',
            'chevron-right' => 'Chevron Right',
            'chevron-up' => 'Chevron Up',
            'chevron-down' => 'Chevron Down',
            'arrow-left' => 'Arrow Left',
            'arrow-right' => 'Arrow Right',
            'arrow-up' => 'Arrow Up',
            'arrow-down' => 'Arrow Down',
            'lock' => 'Lock',
            'unlock' => 'Unlock',
            'key' => 'Key',
            'sign-in' => 'Sign In',
            'sign-out' => 'Sign Out',
            'user-plus' => 'User Plus',
            'users' => 'Users',
            'comment' => 'Comment',
            'comments' => 'Comments',
            'envelope' => 'Envelope',
            'phone' => 'Phone',
            'mobile' => 'Mobile',
            'map-marker' => 'Map Marker',
            'calendar' => 'Calendar',
            'clock' => 'Clock',
            'bell' => 'Bell',
            'bookmark' => 'Bookmark',
            'tag' => 'Tag',
            'tags' => 'Tags',
            'thumbs-up' => 'Thumbs Up',
            'thumbs-down' => 'Thumbs Down',
            'eye' => 'Eye',
            'exclamation-triangle' => 'Exclamation Triangle',
            'info-circle' => 'Info Circle',
            'question-circle' => 'Question Circle',
            'check-circle' => 'Check Circle',
            'times-circle' => 'Times Circle',
            'plus-circle' => 'Plus Circle',
            'minus-circle' => 'Minus Circle',
            'spinner' => 'Spinner',
            'cog' => 'Cog',
            'briefcase' => 'Briefcase',
            'building' => 'Building',
            'coffee' => 'Coffee',
            'gamepad' => 'Gamepad',
            'keyboard' => 'Keyboard',
            'desktop' => 'Desktop',
            'laptop' => 'Laptop',
            'tablet' => 'Tablet',
            'tv' => 'TV',
            'camera' => 'Camera',
            'code' => 'Code',
            'database' => 'Database',
            'server' => 'Server',
            'cloud' => 'Cloud',
            'globe' => 'Globe',
            'shield' => 'Shield',
            'flag' => 'Flag',
            'gift' => 'Gift',
            'trophy' => 'Trophy',
            'medal' => 'Medal',
            'star-half' => 'Star Half',
        );

        $icons = array();
        foreach ( $common_icons as $name => $label ) {
            // Filter by search term
            if ( ! empty( $search ) && stripos( $name, $search ) === false && stripos( $label, $search ) === false ) {
                continue;
            }

            $icons[] = array(
                'name' => $name,
                'label' => $label,
                'search_terms' => array( $name, $label ),
            );
        }

        return $icons;
    }
}

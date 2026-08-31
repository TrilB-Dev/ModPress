<?php
/**
 * Post identity and ModPress post-type helpers.
 *
 * @package ModPress
 * @subpackage Includes\Functions\Helpers
 * @since 1.0.0
 */

namespace ModPress\Includes\Functions\Helpers;

use ModPress\Includes\Core\PostType;
use ModPress\Includes\Functions\Helpers\QueryHelper;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Provide null-safe post checks shared by admin, API, and frontend code.
 */
final class PostHelper {
    public static function current(): ?\WP_Post {
        $query = QueryHelper::current();
        return $query instanceof \WP_Query && $query->post instanceof \WP_Post ? $query->post : null;
    }

    public static function current_id(): int {
        return self::current() ? absint( self::current()->ID ) : 0;
    }

    public static function current_type(): string {
        return self::current() ? (string) self::current()->post_type : '';
    }

    public static function get( $post = null ): ?\WP_Post {
        if ( $post instanceof \WP_Post ) {
            return $post;
        }

        if ( is_numeric( $post ) && absint( $post ) > 0 ) {
            $post = get_post( absint( $post ) );
        } elseif ( null === $post ) {
            $post = get_post();
        }

        return $post instanceof \WP_Post ? $post : null;
    }

    public static function id( $post = null ): int {
        $post = self::get( $post );
        return $post ? absint( $post->ID ) : 0;
    }

    public static function is_type( $post, string $post_type ): bool {
        $post = self::get( $post );
        return null !== $post && $post->post_type === $post_type;
    }

    public static function is_mod( $post ): bool {
        return self::is_type( $post, PostType::MOD );
    }

    public static function is_mod_page( $post ): bool {
        return self::is_type( $post, PostType::PAGE );
    }

    public static function permalink( $post = null ): string {
        $post_id = self::id( $post );
        return $post_id > 0 ? (string) get_permalink( $post_id ) : '';
    }
}

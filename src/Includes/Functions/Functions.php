<?php
/**
 * ModPress - Functions
 *
 * Shared utilities used by Wiki services, pages, and REST routes.
 *
 * @package TrilBDev
 * @subpackage Includes\Wiki\Functions
 * @since 1.0.0
 */

namespace ModPress\Includes\Functions;

use ModPress\Includes\Functions\Helpers\PostHelper;
use ModPress\Includes\Functions\Helpers\SanitizationHelper;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Backwards-compatible facade for common ModPress utility operations.
 *
 * New code may use the focused helper classes directly. This facade remains
 * useful to extensions that need one stable entry point for Wiki payloads.
 */
final class Functions {
    /**
     * Default status for Wiki posts.
     */
    public const DEFAULT_STATUS = 'publish';
    /**
     * Allowed statuses for Wiki posts.
     */
    public const ALLOWED_STATUSES = [ 'publish', 'draft', 'private' ];
    /**
     * Sanitizes a Wiki payload array for safe use.
     *
     * @param array $payload The payload to sanitize.
     * @return array The sanitized payload.
     */
    public static function sanitize_payload( array $payload ): array {
        $status = SanitizationHelper::key( $payload['status'] ?? self::DEFAULT_STATUS );

        return [
            'title' => SanitizationHelper::text( $payload['title'] ?? '' ),
            'content' => self::sanitize_content( $payload['content'] ?? '' ),
            'excerpt' => SanitizationHelper::text( $payload['excerpt'] ?? '' ),
            'status' => SanitizationHelper::one_of( $status, self::ALLOWED_STATUSES, self::DEFAULT_STATUS ),
            'post_id' => SanitizationHelper::integer( $payload['post_id'] ?? 0 ),
            'categories' => self::normalize_terms( $payload['categories'] ?? [] ),
            'tags' => self::normalize_terms( $payload['tags'] ?? [] ),
        ];
    }
    /**
     * Normalizes an array of terms (categories or tags) for safe use.
     *
     * @param mixed $terms The terms to normalize.
     * @return array The normalized terms.
     */
    public static function normalize_terms( $terms ): array {
        return SanitizationHelper::terms( $terms );
    }
    /**
     * Checks if a given post is a Wiki post.
     *
     * @param mixed $post The post to check.
     * @return bool True if the post is a Wiki post, false otherwise.
     */
    public static function is_post( $post ): bool {
        return self::is_page( $post );
    }
    /**
     * Checks if a given post is a Wiki page.
     *
     * @param mixed $post The post to check.
     * @return bool True if the post is a Wiki page, false otherwise.
     */
    public static function is( $post ): bool {
        return PostHelper::is( $post );
    }
    /**
     * Checks if a given post is a Wiki content (either a Wiki post or a Wiki page).
     *
     * @param mixed $post The post to check.
     * @return bool True if the post is a Wiki content, false otherwise.
     */
    public static function is_page( $post ): bool {
        return PostHelper::is_page( $post );
    }
    /**
     * Checks if a given post is either a Wiki post or a Wiki page.
     *
     * @param mixed $post The post to check.
     * @return bool True if the post is a Wiki content, false otherwise.
     */
    public static function is_content( $post ): bool {
        return self::is( $post ) || self::is_page( $post );
    }
    /**
     * Returns a standardized REST response array.
     *
     * @param bool   $success Indicates if the operation was successful.
     * @param string $message A message describing the result.
     * @param array  $data    Additional data to include in the response.
     * @return array The standardized REST response.
     */
    public static function rest_response( bool $success, string $message = '', array $data = [] ): array {
        return [
            'success' => $success,
            'message' => $message,
            'data' => $data,
        ];
    }
    /**
     * Sanitizes a string for safe use in HTML output.
     *
     * @param string $string The string to sanitize.
     * @return string The sanitized string.
     */
    private static function sanitize_content( $content ): string {
        return is_scalar( $content ) ? wp_kses_post( (string) $content ) : '';
    }
}

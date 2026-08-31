<?php
/**
 * Content formatting and measurement helpers for ModPress.
 *
 * @package ModPress
 * @subpackage Includes\Functions\Helpers
 * @since 1.0.0
 */

namespace ModPress\Includes\Functions\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Provide consistent content-derived values for frontend and API consumers.
 */
final class ContentHelper {
    public static function plain_text( $content ): string {
        $content = is_scalar( $content ) ? (string) $content : '';
        return wp_strip_all_tags( strip_shortcodes( $content ) );
    }

    public static function word_count( $content ): int {
        $plain_text = trim( self::plain_text( $content ) );
        return '' === $plain_text ? 0 : str_word_count( wp_check_invalid_utf8( $plain_text ) );
    }

    public static function reading_time( $content, int $words_per_minute = 200 ): int {
        $words_per_minute = max( 1, $words_per_minute );
        return max( 1, (int) ceil( self::word_count( $content ) / $words_per_minute ) );
    }

    public static function excerpt( $content, int $words = 30 ): string {
        return wp_trim_words( self::plain_text( $content ), max( 1, $words ) );
    }

    public static function heading_id( $heading, string $fallback = 'section' ): string {
        return SanitizationHelper::slug( $heading, $fallback );
    }
}

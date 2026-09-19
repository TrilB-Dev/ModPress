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


final class ContentHelper {
	public static function plain_text( $content ): string {
		$content = is_scalar( $content ) ? (string) $content : '';
		return wp_strip_all_tags( strip_shortcodes( $content ) );
	}
	/**
	 * Returns the word count of the given content.
	 *
	 * @param mixed $content The content to measure.
	 * @return int The word count.
	 */
	public static function word_count( $content ): int {
		$plain_text = trim( self::plain_text( $content ) );
		return '' === $plain_text ? 0 : str_word_count( wp_check_invalid_utf8( $plain_text ) );
	}

	/**
	 * Estimates the reading time for the given content.
	 *
	 * @param mixed $content The content to measure.
	 * @param int   $words_per_minute Reading speed in words per minute.
	 * @return int Estimated reading time in minutes.
	 */
	public static function reading_time( $content, int $words_per_minute = 200 ): int {
		$words_per_minute = max( 1, $words_per_minute );
		return max( 1, (int) ceil( self::word_count( $content ) / $words_per_minute ) );
	}

	/**
	 * Generates an excerpt from the given content.
	 *
	 * @param mixed $content The content to excerpt.
	 * @param int   $words Number of words for the excerpt.
	 * @return string The generated excerpt.
	 */
	public static function excerpt( $content, int $words = 30 ): string {
		return wp_trim_words( self::plain_text( $content ), max( 1, $words ) );
	}
	/**
	 * Generates a sanitized ID for a heading.
	 *
	 * @param mixed  $heading The heading text.
	 * @param string $fallback Fallback ID if the heading is empty or invalid.
	 * @return string The generated heading ID.
	 */
	public static function heading_id( $heading, string $fallback = 'section' ): string {
		return SanitizationHelper::slug( $heading, $fallback );
	}
}






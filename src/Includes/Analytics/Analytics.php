<?php
/**
 * Core analytics tracker for ModPress.
 *
 * @package ModPress\Includes\Analytics
 */

namespace ModPress\Includes\Analytics;

use ModPress\Includes\Core\WP\Database;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Analytics {
	/**
	 * Record a page view against a post.
	 *
	 * WordPress hooks can pass a mixed value to action callbacks, so we normalize
	 * the value before using it as a post ID.
	 *
	 * @param mixed $post_id Optional post ID or hook payload.
	 * @return void
	 */
	public static function track_view( $post_id = 0 ): void {
		if ( is_string( $post_id ) ) {
			$post_id = trim( $post_id );
			$post_id = is_numeric( $post_id ) ? (int) $post_id : 0;
		} elseif ( ! is_int( $post_id ) && ! is_numeric( $post_id ) ) {
			$post_id = 0;
		}

		$post_id = (int) $post_id;

		if ( ! function_exists( 'is_admin' ) || ! function_exists( 'is_singular' ) ) {
			return;
		}

		if ( ! is_admin() && $post_id > 0 ) {
			self::track_event( 'page_view', array( 'post_id' => $post_id ) );
			return;
		}

		if ( ! is_admin() && ! is_singular() ) {
			return;
		}

		$post_id = $post_id > 0 ? $post_id : ( function_exists( 'get_the_ID' ) ? (int) get_the_ID() : 0 );
		if ( $post_id > 0 ) {
			self::track_event( 'page_view', array( 'post_id' => $post_id ) );
		}
	}

	/**
	 * Record an analytics event from ModPress flows.
	 *
	 * @param string $event Event key.
	 * @param array  $context Event payload.
	 * @param int    $user_id Optional user ID.
	 * @return void
	 */
	public static function track_event( string $event, array $context = array(), int $user_id = 0 ): void {
		global $wpdb;

		if ( '' === trim( $event ) || ! is_object( $wpdb ) || ! method_exists( $wpdb, 'insert' ) ) {
			return;
		}

		Database::install();

		$event_key = sanitize_key( $event );
		if ( '' === $event_key ) {
			return;
		}

		$payload = array(
			'event_key'    => $event_key,
			'event_value'  => maybe_serialize( $context ),
			'user_id'      => $user_id > 0 ? $user_id : ( function_exists( 'get_current_user_id' ) ? get_current_user_id() : 0 ),
			'event_source' => function_exists( 'is_admin' ) && is_admin() ? 'admin' : 'frontend',
			'created_at'   => current_time( 'mysql' ),
			'updated_at'   => current_time( 'mysql' ),
		);

		$wpdb->insert( Database::table_name( 'analytics' ), $payload );
	}

	/**
	 * Store a structured log event in the ModPress logs table.
	 *
	 * @param string $level Log level.
	 * @param string $message Log message.
	 * @param array  $context Additional log context.
	 * @return void
	 */
	public static function log_event( string $level, string $message, array $context = array() ): void {
		global $wpdb;

		if ( '' === trim( $message ) || ! is_object( $wpdb ) || ! method_exists( $wpdb, 'insert' ) ) {
			return;
		}

		Database::install();

		$wpdb->insert(
			Database::table_name( 'logs' ),
			array(
				'log_level'  => sanitize_key( $level ),
				'message'    => $message,
				'context'    => maybe_serialize( $context ),
				'source'     => 'modpress',
				'created_at' => current_time( 'mysql' ),
				'updated_at' => current_time( 'mysql' ),
			)
		);
	}

	/**
	 * Summarize analytics events for reporting.
	 *
	 * @param array $filters Optional filters.
	 * @return array<string, mixed>
	 */
	public static function get_summary( array $filters = array() ): array {
		global $wpdb;

		$summary = array(
			'event' => '',
			'count' => 0,
			'total' => 0,
			'last_seen' => null,
		);

		if ( ! is_object( $wpdb ) || ! method_exists( $wpdb, 'get_results' ) ) {
			return $summary;
		}

		Database::install();

		$event = isset( $filters['event'] ) ? sanitize_key( (string) $filters['event'] ) : '';
		$where = '';
		if ( '' !== $event ) {
			$where = $wpdb->prepare( ' WHERE event_key = %s ', $event );
		}

		$rows = $wpdb->get_results( 'SELECT event_key, COUNT(*) AS count, MAX(created_at) AS last_seen FROM ' . Database::table_name( 'analytics' ) . $where . ' GROUP BY event_key LIMIT 1', ARRAY_A );
		$rows = is_array( $rows ) ? $rows : array();
		if ( empty( $rows ) ) {
			$raw_rows = $wpdb->get_results( 'SELECT event_key, created_at FROM ' . Database::table_name( 'analytics' ) . $where . ' ORDER BY created_at DESC', ARRAY_A );
			$raw_rows = is_array( $raw_rows ) ? $raw_rows : array();
			if ( empty( $raw_rows ) ) {
				return array_merge( $summary, array( 'event' => $event ) );
			}

			$count = 0;
			$last_seen = null;
			$event_name = (string) ( $raw_rows[0]['event_key'] ?? $event );
			foreach ( $raw_rows as $row ) {
				if ( '' !== $event && (string) ( $row['event_key'] ?? '' ) !== $event ) {
					continue;
				}
				$count++;
				if ( ! empty( $row['created_at'] ) && ( null === $last_seen || $row['created_at'] > $last_seen ) ) {
					$last_seen = (string) $row['created_at'];
				}
			}

			$summary['event'] = $event_name;
			$summary['count'] = $count;
			$summary['total'] = $count;
			$summary['last_seen'] = $last_seen;
			return $summary;
		}

		$row = $rows[0];
		$summary['event'] = (string) ( $row['event_key'] ?? $event );
		if ( isset( $row['count'] ) || isset( $row['COUNT(*)'] ) ) {
			$summary['count'] = (int) ( $row['count'] ?? $row['COUNT(*)'] ?? 0 );
			$summary['total'] = $summary['count'];
			$summary['last_seen'] = $row['last_seen'] ?? null;
			return $summary;
		}

		$count = count( $rows );
		$last_seen = null;
		foreach ( $rows as $event_row ) {
			if ( ! empty( $event_row['created_at'] ) && ( null === $last_seen || $event_row['created_at'] > $last_seen ) ) {
				$last_seen = (string) $event_row['created_at'];
			}
		}

		$summary['count'] = $count;
		$summary['total'] = $count;
		$summary['last_seen'] = $last_seen;
		return $summary;
	}

	/**
	 * Retrieve recent analytics events.
	 *
	 * @param int $limit Maximum items.
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_recent_events( int $limit = 10 ): array {
		global $wpdb;

		if ( ! is_object( $wpdb ) || ! method_exists( $wpdb, 'get_results' ) ) {
			return array();
		}

		Database::install();
		$rows = $wpdb->get_results( 'SELECT event_key, event_value, user_id, event_source, created_at FROM ' . Database::table_name( 'analytics' ) . ' ORDER BY created_at DESC LIMIT ' . max( 1, (int) $limit ), ARRAY_A );
		$rows = is_array( $rows ) ? $rows : array();

		foreach ( $rows as &$row ) {
			$row['event_value'] = maybe_unserialize( $row['event_value'] );
		}
		unset( $row );

		return $rows;
	}
}




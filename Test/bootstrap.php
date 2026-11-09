<?php

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . DIRECTORY_SEPARATOR );
}

if ( ! defined( 'OBJECT' ) ) {
	define( 'OBJECT', 1 );
}

if ( ! defined( 'ARRAY_A' ) ) {
	define( 'ARRAY_A', 2 );
}

if ( ! defined( 'ARRAY_N' ) ) {
	define( 'ARRAY_N', 3 );
}

if ( ! function_exists( 'sanitize_key' ) ) {
	function sanitize_key( $key ): string {
		return strtolower( preg_replace( '/[^a-z0-9_\-]/', '', (string) $key ) );
	}
}

if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = null ): string {
		return (string) $text;
	}
}

if ( ! function_exists( '_e' ) ) {
	function _e( $text, $domain = null ) {
		echo (string) $text;
	}
}

if ( ! class_exists( 'WP_REST_Request' ) ) {
	class WP_REST_Request {
		protected array $params = array();

		public function __construct() {}

		public function set_param( string $key, $value ): self {
			$this->params[ $key ] = $value;
			return $this;
		}

		public function get_param( string $key ) {
			return $this->params[ $key ] ?? null;
		}
	}
}

if ( ! class_exists( 'WP_REST_Response' ) ) {
	class WP_REST_Response {
		protected $data;

		public function __construct( $data = null ) {
			$this->data = $data;
		}

		public function get_data() {
			return $this->data;
		}
	}
}

if ( ! function_exists( 'rest_ensure_response' ) ) {
	function rest_ensure_response( $response ) {
		if ( $response instanceof \WP_REST_Response ) {
			return $response;
		}

		return new \WP_REST_Response( $response );
	}
}

if ( ! function_exists( 'sanitize_title' ) ) {
	function sanitize_title( $title, $fallback_title = '', $context = 'save' ): string {
		$slug = strtolower( trim( preg_replace( '/[^a-z0-9]+/', '-', (string) $title ), '-' ) );
		return '' !== $slug ? $slug : (string) $fallback_title;
	}
}

if ( ! function_exists( 'wp_unique_id' ) ) {
	function wp_unique_id( $prefix = '' ): string {
		static $counter = 0;
		$counter++;
		return $prefix . $counter;
	}
}

if ( ! function_exists( 'get_post_thumbnail_id' ) ) {
	function get_post_thumbnail_id( $post = null ) {
		$post_id = is_object( $post ) ? (int) ( $post->ID ?? 0 ) : (int) $post;
		return $GLOBALS['modpress_test_thumbnail_ids'][ $post_id ] ?? 0;
	}
}

if ( ! function_exists( 'get_the_date' ) ) {
	function get_the_date( $format = '', $post = null ) {
		if ( is_object( $post ) && ! empty( $post->post_date ) ) {
			$date = $post->post_date;
			if ( '' !== $format ) {
				return gmdate( $format, strtotime( $date ) );
			}
			return $date;
		}

		return '';
	}
}

if ( ! function_exists( 'wp_get_post_terms' ) ) {
	function wp_get_post_terms( $post_id = 0, $taxonomy = '', $args = array() ) {
		return array();
	}
}

if ( ! function_exists( 'wp_set_object_terms' ) ) {
	function wp_set_object_terms( $object_id, $terms, $taxonomy, $append = false ) {
		return array();
	}
}

if ( ! function_exists( 'wp_parse_args' ) ) {
	function wp_parse_args( $args, $defaults = array() ) {
		if ( is_object( $args ) ) {
			$args = get_object_vars( $args );
		}

		if ( is_array( $args ) && is_array( $defaults ) ) {
			return array_merge( $defaults, $args );
		}

		return $defaults;
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $text ): string {
		return trim( strip_tags( (string) $text ) );
	}
}

if ( ! function_exists( 'sanitize_textarea_field' ) ) {
	function sanitize_textarea_field( $text ): string {
		return trim( (string) $text );
	}
}

if ( ! function_exists( 'dbDelta' ) ) {
	function dbDelta( $queries ) {
		return true;
	}
}

if ( ! function_exists( 'post_type_exists' ) ) {
	function post_type_exists( $post_type ): bool {
		return isset( $GLOBALS['modpress_test_post_types'][ (string) $post_type ] );
	}
}

if ( ! function_exists( 'register_post_type' ) ) {
	function register_post_type( $post_type, $args = array() ) {
		$GLOBALS['modpress_test_post_types'][ (string) $post_type ] = $args;
		return true;
	}
}

if ( ! function_exists( 'register_post_meta' ) ) {
	function register_post_meta( $post_type, $meta_key, $args = array() ) {
		$GLOBALS['modpress_test_post_meta'][ (string) $post_type ][ (string) $meta_key ] = $args;
		return true;
	}
}

if ( ! isset( $GLOBALS['modpress_test_post_types'] ) ) {
	$GLOBALS['modpress_test_post_types'] = array();
}

if ( ! isset( $GLOBALS['modpress_test_post_meta'] ) ) {
	$GLOBALS['modpress_test_post_meta'] = array();
}

require_once dirname( __DIR__ ) . '/vendor/autoload.php';
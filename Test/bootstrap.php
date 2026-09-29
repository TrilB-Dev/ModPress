<?php

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . DIRECTORY_SEPARATOR );
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

if ( ! function_exists( 'sanitize_title' ) ) {
	function sanitize_title( $title, $fallback_title = '', $context = 'save' ): string {
		$slug = strtolower( trim( preg_replace( '/[^a-z0-9]+/', '-', (string) $title ), '-' ) );
		return '' !== $slug ? $slug : (string) $fallback_title;
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $text ): string {
		return trim( (string) $text );
	}
}

if ( ! function_exists( 'sanitize_textarea_field' ) ) {
	function sanitize_textarea_field( $text ): string {
		return trim( (string) $text );
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
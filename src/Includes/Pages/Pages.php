<?php

namespace WikiPress\Includes\Pages;

use WikiPress\API\API;
use WikiPress\Includes\Functions\Functions;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Pages {
    public static function list_pages( array $query_args = [] ): array {
        return API::list_pages( $query_args );
    }

    public static function get_by_id( int $page_id ): array {
        $page = API::get_page( $page_id );
        return $page
            ? Functions::rest_response( true, '', $page )
            : Functions::rest_response( false, __( 'Wiki page not found.', 'wikipress' ) );
    }

    public static function create_from_payload( array $payload ): array {
        $page = API::create_page( $payload );
        return self::mutation_response( $page );
    }

    public static function update_from_payload( int $page_id, array $payload ): array {
        return self::mutation_response( API::update_page( $page_id, $payload ) );
    }

    public static function delete_by_id( int $page_id, bool $force = false ): array {
        return self::mutation_response( API::delete_page( $page_id, $force ) );
    }

    private static function mutation_response( $result ): array {
        if ( is_wp_error( $result ) ) {
            return Functions::rest_response( false, $result->get_error_message() );
        }

        if ( false === $result ) {
            return Functions::rest_response( false, __( 'Wiki page mutation failed.', 'wikipress' ) );
        }

        return Functions::rest_response( true, '', is_array( $result ) ? $result : [ 'deleted' => (bool) $result ] );
    }
}

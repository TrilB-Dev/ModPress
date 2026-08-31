<?php

namespace ModPress\Includes\Core\WP;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Own the ModPress custom database schema.
 *
 * Extensions can add their own activation callbacks through Activator rather
 * than modifying the core schema registry.
 */
final class Database {
    /** @var array<string, callable> */
    private static array $registered_tables = [];

    /**
     * Register an extension table schema for the next installation/update.
     *
     * The callback receives the fully prefixed table name and charset/collation
     * string, and must return a dbDelta-compatible CREATE TABLE statement.
     *
     * @param string   $table    Unprefixed ModPress table suffix.
     * @param callable $schema   Schema callback.
     * @return bool Whether the table was registered.
     */
    public static function register_table( string $table, callable $schema ): bool {
        $table = sanitize_key( $table );
        if ( '' === $table || in_array( $table, [ 'settings', 'analytics' ], true ) ) {
            return false;
        }

        self::$registered_tables[ $table ] = $schema;
        return true;
    }

    /**
     * Install or update all ModPress-owned tables.
     *
     * @return void
     */
    public static function install(): void {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset = $wpdb->get_charset_collate();
        dbDelta( "CREATE TABLE {$wpdb->prefix}modpress_settings (
            setting_group varchar(100) NOT NULL,
            setting_value longtext NOT NULL,
            autoload varchar(20) NOT NULL DEFAULT 'yes',
            updated_at datetime NOT NULL,
            PRIMARY KEY  (setting_group)
        ) {$charset};" );

        dbDelta( "CREATE TABLE {$wpdb->prefix}modpress_analytics (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            post_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            viewed_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY post_id (post_id),
            KEY viewed_at (viewed_at),
            KEY post_viewed_at (post_id, viewed_at)
        ) {$charset};" );

        foreach ( self::$registered_tables as $table => $schema ) {
            $statement = call_user_func( $schema, self::table_name( $table ), $charset );
            if ( is_string( $statement ) && '' !== trim( $statement ) ) {
                dbDelta( $statement );
            }
        }

        update_option( 'modpress_db_version', defined( 'MODPRESS_VERSION' ) ? MODPRESS_VERSION : '1.0.0' );
    }

    /**
     * Return a prefixed ModPress table name.
     *
     * @param string $table Unprefixed table suffix.
     * @return string Full table name.
     */
    public static function table_name( string $table ): string {
        global $wpdb;
        return $wpdb->prefix . 'modpress_' . sanitize_key( $table );
    }
}
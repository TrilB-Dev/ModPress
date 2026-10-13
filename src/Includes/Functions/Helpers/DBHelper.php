<?php
/**
 * Helper for querying and modifying ModPress custom database tables.
 *
 * @package ModPress\Includes\Functions\Helpers
 * @since 1.0.0
 */
namespace ModPress\Includes\Functions\Helpers;
use ModPress\Includes\Core\WP\Database;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

final class DBHelper {
	/**
	 * Static table prefix used for all ModPress custom tables.
	 *
	 * @var string
	 */
	private static string $table_prefix = 'modpress_';

	/**
	 * Set the ModPress table prefix.
	 *
	 * @param string $prefix Prefix to use for custom tables.
	 * @return void
	 */
	public static function set_prefix( string $prefix ): void {
		$prefix = trim( (string) $prefix );
		$prefix = preg_replace( '/[^A-Za-z0-9_]/', '', $prefix );

		if ( '' === $prefix ) {
			self::$table_prefix = 'modpress_';
			return;
		}

		self::$table_prefix = rtrim( $prefix, '_' ) . '_';
	}

	/**
	 * Get the active table prefix.
	 *
	 * @return string
	 */
	public static function get_prefix(): string {
		return self::$table_prefix;
	}

	/**
	 * Register a core table schema for the next installation/update.
	 *
	 * @param string   $table  Unprefixed ModPress table suffix.
	 * @param callable $schema Schema callback.
	 * @return bool
	 */
	public static function register_core_table( string $table, callable $schema ): bool {
		return Database::register_core_table( $table, $schema );
	}

	/**
	 * Register a plugin table schema for the next installation/update.
	 *
	 * @param string   $table  Unprefixed ModPress table suffix.
	 * @param callable $schema Schema callback.
	 * @return bool
	 */
	public static function register_plugin_table( string $table, callable $schema ): bool {
		return Database::register_plugin_table( $table, $schema );
	}

	/**
	 * Get the registered core ModPress tables.
	 *
	 * @return array<string, callable>
	 */
	public static function get_core_tables(): array {
		return Database::get_core_tables();
	}

	/**
	 * Get the registered plugin ModPress tables.
	 *
	 * @return array<string, callable>
	 */
	public static function get_plugin_tables(): array {
		return Database::get_plugin_tables();
	}

	/**
	 * Build a fully qualified table name from a table suffix.
	 *
	 * Example: DBHelper::table_name( 'settings' ) => wp_modpress_settings
	 *
	 * @param string $table Table suffix only, not a full table name.
	 * @return string
	 */
	public static function table_name( string $table ): string {
		global $wpdb;

		$table = trim( (string) $table );
		$table = preg_replace( '/[^A-Za-z0-9_]/', '', $table );

		if ( '' === $table ) {
			return '';
		}

		return $wpdb->prefix . self::$table_prefix . $table;
	}

	/**
	 * Alias for table_name().
	 *
	 * @param string $table Table suffix.
	 * @return string
	 */
	public static function table( string $table ): string {
		return self::table_name( $table );
	}

	/**
	 * Create or update a single custom ModPress table from the registered schema.
	 *
	 * @param string $table Unprefixed table suffix.
	 * @return bool
	 */
	public static function create_table( string $table ): bool {
		return Database::create_table( $table );
	}

	/**
	 * Empty all rows from a custom ModPress table.
	 *
	 * @param string $table Unprefixed table suffix.
	 * @return bool
	 */
	public static function drop_table_contents( string $table ): bool {
		return Database::drop_table_contents( $table );
	}

	/**
	 * Alias for dropping all rows from table contents.
	 *
	 * @param string $table Unprefixed table suffix.
	 * @return bool
	 */
	public static function truncate_table( string $table ): bool {
		return Database::truncate_table( $table );
	}

	/**
	 * Delete a ModPress custom table.
	 *
	 * @param string $table Unprefixed table suffix.
	 * @return bool
	 */
	public static function delete_table( string $table ): bool {
		return Database::delete_table( $table );
	}

	/**
	 * Alias for dropping a table.
	 *
	 * @param string $table Unprefixed table suffix.
	 * @return bool
	 */
	public static function drop_table( string $table ): bool {
		return Database::drop_table( $table );
	}

	/**
	 * Install or update all registered ModPress tables.
	 *
	 * @return void
	 */
	public static function install(): void {
		Database::install();
	}

	/**
	 * Determine whether a custom ModPress table exists.
	 *
	 * @param string $table Table suffix.
	 * @return bool
	 */
	public static function table_exists( string $table ): bool {
		global $wpdb;

		$table_name = self::table_name( $table );
		if ( '' === $table_name ) {
			return false;
		}

		return $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === $table_name;
	}

	/**
	 * Execute a raw SQL query.
	 *
	 * @param string $sql SQL statement.
	 * @param array  $params Optional prepared statement placeholders.
	 * @return mixed
	 */
	public static function query( string $sql, array $params = array() ) {
		global $wpdb;

		if ( ! empty( $params ) ) {
			$sql = $wpdb->prepare( $sql, $params );
		}

		return $wpdb->query( $sql );
	}

	/**
	 * Fetch a single row from a custom table.
	 *
	 * @param string $table Table suffix.
	 * @param array  $where Where conditions. Example: array( 'id' => 2 ).
	 * @param string $columns Columns to select.
	 * @param string $order_by Optional ORDER BY clause.
	 * @return array<string, mixed>|null
	 */
	public static function get_row( string $table, array $where = array(), string $columns = '*', string $order_by = '' ) {
		global $wpdb;

		$query = 'SELECT ' . $columns . ' FROM ' . self::table_name( $table );
		$where_sql = self::build_where_clause( $where );
		$args      = self::build_where_args( $where );

		if ( '' !== $where_sql ) {
			$query .= ' WHERE ' . $where_sql;
		}

		if ( '' !== $order_by ) {
			$query .= ' ORDER BY ' . self::validate_order_by( $order_by );
		}

		$query .= ' LIMIT 1';

		if ( ! empty( $args ) ) {
			$query = $wpdb->prepare( $query, $args );
		}

		return $wpdb->get_row( $query, ARRAY_A );
	}

	/**
	 * Fetch multiple rows from a custom table.
	 *
	 * @param string $table Table suffix.
	 * @param array  $where Where conditions.
	 * @param string $columns Columns to select.
	 * @param string $order_by Optional ORDER BY clause.
	 * @param int    $limit Optional row limit.
	 * @param int    $offset Optional row offset.
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_results( string $table, array $where = array(), string $columns = '*', string $order_by = '', int $limit = 0, int $offset = 0 ): array {
		global $wpdb;

		$query = 'SELECT ' . $columns . ' FROM ' . self::table_name( $table );
		$where_sql = self::build_where_clause( $where );
		$args      = self::build_where_args( $where );

		if ( '' !== $where_sql ) {
			$query .= ' WHERE ' . $where_sql;
		}

		if ( '' !== $order_by ) {
			$query .= ' ORDER BY ' . self::validate_order_by( $order_by );
		}

		if ( $limit > 0 ) {
			$query .= ' LIMIT %d';
			$args[] = $limit;

			if ( $offset > 0 ) {
				$query .= ' OFFSET %d';
				$args[] = $offset;
			}
		}

		if ( ! empty( $args ) ) {
			$query = $wpdb->prepare( $query, $args );
		}

		return $wpdb->get_results( $query, ARRAY_A );
	}

	/**
	 * Fetch a single value from a custom table.
		 *
	 * @param string $table Table suffix.
	 * @param string $column Column to fetch.
	 * @param array  $where Where conditions.
	 * @param mixed  $default Default value if nothing is found.
	 * @return mixed
	 */
	public static function get_var( string $table, string $column, array $where = array(), $default = null ) {
		global $wpdb;

		$query = 'SELECT ' . self::escape_column( $column ) . ' FROM ' . self::table_name( $table );
		$where_sql = self::build_where_clause( $where );
		$args      = self::build_where_args( $where );

		if ( '' !== $where_sql ) {
			$query .= ' WHERE ' . $where_sql;
		}

		$query .= ' LIMIT 1';

		if ( ! empty( $args ) ) {
			$query = $wpdb->prepare( $query, $args );
		}

		$value = $wpdb->get_var( $query );
		return null === $value ? $default : $value;
	}

	/**
	 * Fetch all rows from a table using a simple where array.
	 *
	 * @param string $table Table suffix.
	 * @param array  $where Where conditions.
	 * @param string $columns Columns to select.
	 * @param string $order_by Optional ORDER BY clause.
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_all( string $table, array $where = array(), string $columns = '*', string $order_by = '' ): array {
		return self::get_results( $table, $where, $columns, $order_by );
	}

	/**
	 * Return the number of rows in a table matching the provided where conditions.
	 *
	 * @param string $table Table suffix.
	 * @param array  $where Where conditions.
	 * @return int
	 */
	public static function count( string $table, array $where = array() ): int {
		global $wpdb;

		$query = 'SELECT COUNT(*) FROM ' . self::table_name( $table );
		$where_sql = self::build_where_clause( $where );
		$args      = self::build_where_args( $where );

		if ( '' !== $where_sql ) {
			$query .= ' WHERE ' . $where_sql;
		}

		if ( ! empty( $args ) ) {
			$query = $wpdb->prepare( $query, $args );
		}

		return (int) $wpdb->get_var( $query );
	}

	/**
	 * Check whether a matching row exists.
	 *
	 * @param string $table Table suffix.
	 * @param array  $where Where conditions.
	 * @return bool
	 */
	public static function exists( string $table, array $where = array() ): bool {
		return self::count( $table, $where ) > 0;
	}

	/**
	 * Insert a row into a custom table.
	 *
	 * @param string $table Table suffix.
	 * @param array  $data Row data to insert.
	 * @param array  $formats Optional column value format array.
	 * @return int|false Inserted row id or false on failure.
	 */
	public static function insert( string $table, array $data, array $formats = array() ) {
		global $wpdb;

		$table_name = self::table_name( $table );
		if ( empty( $data ) || '' === $table_name ) {
			return false;
		}

		$columns = array();
		$values  = array();
		$placeholders = array();
		$formats = empty( $formats ) ? array_fill( 0, count( $data ), '%s' ) : $formats;

		foreach ( $data as $column => $value ) {
			$columns[] = self::escape_column( $column );
			$values[] = $value;
			$placeholders[] = $formats[ count( $placeholders ) ] ?? '%s';
		}

		$query = 'INSERT INTO ' . $table_name . ' (' . implode( ', ', $columns ) . ') VALUES (' . implode( ', ', $placeholders ) . ')';
		$prepared = $wpdb->prepare( $query, $values );
		$inserted = $wpdb->query( $prepared );

		return false !== $inserted ? (int) $wpdb->insert_id : false;
	}

	/**
	 * Update data in a custom table.
	 *
	 * @param string $table Table suffix.
	 * @param array  $data Row data to update.
	 * @param array  $where Update conditions.
	 * @param array  $formats Optional value formats for data columns.
	 * @param array  $where_formats Optional value formats for where columns.
	 * @return bool
	 */
	public static function update( string $table, array $data, array $where = array(), array $formats = array(), array $where_formats = array() ): bool {
		global $wpdb;

		$table_name = self::table_name( $table );
		if ( empty( $data ) || '' === $table_name ) {
			return false;
		}

		$set_values = array();
		$values     = array();
		$formats    = empty( $formats ) ? array_fill( 0, count( $data ), '%s' ) : $formats;

		foreach ( $data as $column => $value ) {
			$set_values[] = self::escape_column( $column ) . ' = ' . ( $formats[ count( $set_values ) ] ?? '%s' );
			$values[] = $value;
		}

		$where_sql = self::build_where_clause( $where, $where_formats );
		$where_args = self::build_where_args( $where, $where_formats );

		$query = 'UPDATE ' . $table_name . ' SET ' . implode( ', ', $set_values );
		if ( '' !== $where_sql ) {
			$query .= ' WHERE ' . $where_sql;
		}

		$prepared = $wpdb->prepare( $query, array_merge( $values, $where_args ) );
		return false !== $wpdb->query( $prepared );
	}

	/**
	 * Delete rows from a custom table.
	 *
	 * @param string $table Table suffix.
	 * @param array  $where Delete conditions.
	 * @param array  $where_formats Optional value formats for where conditions.
	 * @return bool
	 */
	public static function delete( string $table, array $where = array(), array $where_formats = array() ): bool {
		global $wpdb;

		$table_name = self::table_name( $table );
		if ( '' === $table_name ) {
			return false;
		}

		$where_sql = self::build_where_clause( $where, $where_formats );
		$where_args = self::build_where_args( $where, $where_formats );

		$query = 'DELETE FROM ' . $table_name;
		if ( '' !== $where_sql ) {
			$query .= ' WHERE ' . $where_sql;
		}

		if ( ! empty( $where_args ) ) {
			$query = $wpdb->prepare( $query, $where_args );
		}

		return false !== $wpdb->query( $query );
	}

	/**
	 * Insert a row, or update it if it already exists.
	 *
	 * @param string $table Table suffix.
	 * @param array  $data Row data.
	 * @param array  $where Unique conditions used to match an existing row.
	 * @return int|false
	 */
	public static function insert_or_update( string $table, array $data, array $where = array() ) {
		if ( empty( $where ) ) {
			return self::insert( $table, $data );
		}

		if ( self::exists( $table, $where ) ) {
			self::update( $table, $data, $where );
			return self::get_var( $table, 'id', $where );
		}

		return self::insert( $table, $data );
	}

	/**
	 * Get a row by id.
	 *
	 * @param string $table Table suffix.
	 * @param int    $id Row id.
	 * @param string $id_column Primary key column.
	 * @return array<string, mixed>|null
	 */
	public static function get_by_id( string $table, int $id, string $id_column = 'id' ) {
		return self::get_row( $table, array( $id_column => $id ) );
	}

	/**
	 * Validate and sanitize an ORDER BY clause.
	 *
	 * @param string $order_by Raw ORDER BY value.
	 * @return string
	 */
	private static function validate_order_by( string $order_by ): string {
		$order_by = trim( $order_by );
		if ( '' === $order_by ) {
			return '';
		}

		$parts = preg_split( '/\s*,\s*/', $order_by );
		$sanitized = array();

		foreach ( $parts as $part ) {
			if ( '' === $part ) {
				continue;
			}

			$matches = array();
			preg_match( '/^([A-Za-z0-9_\.]+)\s*(ASC|DESC)?$/i', trim( $part ), $matches );
			if ( empty( $matches ) ) {
				continue;
			}

			$sanitized[] = self::escape_column( $matches[1] ) . ( ! empty( $matches[2] ) ? ' ' . strtoupper( $matches[2] ) : '' );
		}

		return implode( ', ', $sanitized );
	}

	/**
	 * Build a WHERE clause from a flat where array.
	 *
	 * @param array $where Where conditions.
	 * @param array $formats Optional formats for each value.
	 * @return string
	 */
	private static function build_where_clause( array $where, array $formats = array() ): string {
		$clauses = array();
		$index = 0;

		foreach ( $where as $key => $value ) {
			$column = self::normalize_where_key( $key );
			if ( '' === $column ) {
				continue;
			}

			if ( is_array( $value ) ) {
				$placeholder = implode( ', ', array_fill( 0, count( $value ), '%s' ) );
				$clauses[] = self::escape_column( $column ) . ' IN (' . $placeholder . ')';
				continue;
			}

			$format = $formats[ $index ] ?? '%s';
			$clauses[] = self::escape_column( $column ) . ' = ' . $format;
			$index++;
		}

		return implode( ' AND ', $clauses );
	}

	/**
	 * Build the values for a WHERE clause.
	 *
	 * @param array $where Where conditions.
	 * @param array $formats Optional formats for each value.
	 * @return array<int, mixed>
	 */
	private static function build_where_args( array $where, array $formats = array() ): array {
		$args = array();
		$index = 0;

		foreach ( $where as $key => $value ) {
			if ( '' === self::normalize_where_key( $key ) ) {
				continue;
			}

			if ( is_array( $value ) ) {
				foreach ( $value as $item ) {
					$args[] = $item;
				}
				continue;
			}

			$args[] = $value;
			$index++;
		}

		return $args;
	}

	/**
	 * Normalize a where key such as `status` or `status__not` into a safe column name.
	 *
	 * @param string $key Raw where key.
	 * @return string
	 */
	private static function normalize_where_key( string $key ): string {
		$key = trim( (string) $key );
		if ( '' === $key ) {
			return '';
		}

		$parts = explode( '__', $key );
		return self::escape_column( $parts[0] );
	}

	/**
	 * Escape and normalize a column name.
	 *
	 * @param string $column Raw column name.
	 * @return string
	 */
	private static function escape_column( string $column ): string {
		$column = trim( (string) $column );
		$column = preg_replace( '/[^A-Za-z0-9_\.]/', '', $column );
		return '' === $column ? '*' : '`' . $column . '`';
	}
}
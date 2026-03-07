<?php
/**
 * Database migrator.
 *
 * @package AchttienVijftien\WpContentImporter
 */

namespace AchttienVijftien\WpContentImporter\Database;

/**
 * Database migrator for the content importer plugin.
 *
 * Handles table creation and provides table name helpers.
 */
class Migrator {

	/**
	 * Jobs table name without prefix.
	 *
	 * @var string
	 */
	public const JOBS_TABLE = 'content_importer_jobs';

	/**
	 * Rows table name without prefix.
	 *
	 * @var string
	 */
	public const ROWS_TABLE = 'content_importer_rows';

	/**
	 * Get the full jobs table name including the WordPress prefix.
	 *
	 * @return string The prefixed jobs table name.
	 */
	public static function jobs_table(): string {
		global $wpdb;
		return $wpdb->prefix . self::JOBS_TABLE;
	}

	/**
	 * Get the full rows table name including the WordPress prefix.
	 *
	 * @return string The prefixed rows table name.
	 */
	public static function rows_table(): string {
		global $wpdb;
		return $wpdb->prefix . self::ROWS_TABLE;
	}

	/**
	 * Current schema version.
	 *
	 * @var int
	 */
	private const SCHEMA_VERSION = 2;

	/**
	 * Run dbDelta when the schema version has changed.
	 *
	 * @return void
	 */
	public static function maybe_upgrade(): void {
		$installed = (int) get_option( 'wci_schema_version', 0 );

		if ( $installed >= self::SCHEMA_VERSION ) {
			return;
		}

		self::activate();
		update_option( 'wci_schema_version', self::SCHEMA_VERSION );
	}

	/**
	 * Create the database tables on plugin activation.
	 *
	 * @return void
	 */
	public static function activate(): void {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$jobs_table = self::jobs_table();
		$rows_table = self::rows_table();

		$sql = "CREATE TABLE {$jobs_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL DEFAULT '',
			status varchar(20) NOT NULL DEFAULT 'draft',
			post_type varchar(100) NOT NULL DEFAULT '',
			mode varchar(20) NOT NULL DEFAULT 'create',
			match_field varchar(255) DEFAULT NULL,
			mapping longtext DEFAULT NULL,
			total_rows int(11) NOT NULL DEFAULT 0,
			processed_rows int(11) NOT NULL DEFAULT 0,
			failed_rows int(11) NOT NULL DEFAULT 0,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY status (status)
		) {$charset_collate};

		CREATE TABLE {$rows_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			job_id bigint(20) unsigned NOT NULL,
			data longtext DEFAULT NULL,
			status varchar(20) NOT NULL DEFAULT 'pending',
			post_id bigint(20) unsigned DEFAULT NULL,
			error text DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY job_id (job_id),
			KEY status (status),
			KEY job_status (job_id, status)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}
}

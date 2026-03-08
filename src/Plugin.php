<?php
/**
 * Plugin main class.
 *
 * @package AchttienVijftien\WpContentImporter
 */

namespace AchttienVijftien\WpContentImporter;

use AchttienVijftien\WpContentImporter\Admin\AdminPage;
use AchttienVijftien\WpContentImporter\Admin\AjaxHandler;
use AchttienVijftien\WpContentImporter\Admin\FormHandler;
use AchttienVijftien\WpContentImporter\Database\Migrator;
use AchttienVijftien\WpContentImporter\Import\CronHandler;
use AchttienVijftien\WpContentImporter\Mapping\ModifierRegistry;
use AchttienVijftien\WpContentImporter\Mapping\Template;

/**
 * Main plugin bootstrap class. Implements the singleton pattern.
 */
class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * Get or create the singleton plugin instance.
	 *
	 * @return self The plugin instance.
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor. Registers activation hook, cron schedules, post types, and admin components.
	 */
	private function __construct() {
		$plugin_file = dirname( __DIR__ ) . '/wp-content-importer.php';

		register_activation_hook( $plugin_file, [ Migrator::class, 'activate' ] );

		add_action( 'admin_init', [ Migrator::class, 'maybe_upgrade' ] );
		add_filter( 'cron_schedules', [ CronHandler::class, 'register_interval' ] );
		add_action( 'init', [ Template::class, 'register_post_type' ] );

		ModifierRegistry::instance()->register_defaults();

		if ( is_admin() ) {
			new AdminPage();
			new AjaxHandler();
			new FormHandler();
		}

		new CronHandler();
	}
}

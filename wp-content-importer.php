<?php
/**
 * Plugin Name: WP Content Importer
 * Plugin URI: https://www.1815.nl
 * Description: Import CSV/XLS content into WordPress with configurable field mapping.
 * Version: 1.0.0
 * Author: 1815
 * Author URI: https://www.1815.nl
 * License: GPL-3.0-or-later
 *
 * @package AchttienVijftien\WpContentImporter
 */

namespace AchttienVijftien\WpContentImporter;

if ( file_exists( plugin_dir_path( __FILE__ ) . 'vendor/autoload.php' ) ) {
	require plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';
}

Plugin::instance();

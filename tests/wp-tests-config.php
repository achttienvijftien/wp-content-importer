<?php
/**
 * WordPress test config.
 *
 * @package AchttienVijftien\WpContentImporter
 */

define( 'DB_NAME', 'wp_tests' );
define( 'DB_USER', 'wp_test' );
define( 'DB_PASSWORD', 'wp_test' );
define( 'DB_HOST', 'localhost' );
define( 'DB_CHARSET', 'utf8' );
define( 'DB_COLLATE', '' );

$table_prefix = 'wptests_';

define( 'WP_TESTS_DOMAIN', 'example.org' );
define( 'WP_TESTS_EMAIL', 'admin@example.org' );
define( 'WP_TESTS_TITLE', 'Test Blog' );
define( 'WP_PHP_BINARY', 'php' );

define( 'ABSPATH', dirname( __DIR__, 4 ) . '/wp/' );

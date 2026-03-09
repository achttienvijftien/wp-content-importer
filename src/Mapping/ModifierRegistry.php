<?php
/**
 * Modifier registry.
 *
 * @package AchttienVijftien\WpContentImporter
 */

namespace AchttienVijftien\WpContentImporter\Mapping;

/**
 * Singleton class that manages modifier callables with a WordPress filter escape hatch.
 */
class ModifierRegistry {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Registered modifiers.
	 *
	 * @var array<string, callable>
	 */
	private array $modifiers = [];

	/**
	 * Modifier metadata (description, example).
	 *
	 * @var array<string, array{description: string, example: string}>
	 */
	private array $metadata = [];

	/**
	 * Get the singleton instance.
	 *
	 * @return self
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Register a modifier callable.
	 *
	 * @param string   $name     Modifier name.
	 * @param callable $callback Modifier callback.
	 *
	 * @return void
	 */
	public function register( string $name, callable $callback, string $description = '', string $example = '' ): void {
		$this->modifiers[ $name ] = $callback;

		if ( $description || $example ) {
			$this->metadata[ $name ] = [
				'description' => $description,
				'example'     => $example,
			];
		}
	}

	/**
	 * Get all registered modifier names with their metadata.
	 *
	 * @return array<string, array{description: string, example: string}>
	 */
	public function get_all(): array {
		$all = [];

		foreach ( array_keys( $this->modifiers ) as $name ) {
			$all[ $name ] = $this->metadata[ $name ] ?? [
				'description' => '',
				'example'     => '',
			];
		}

		return $all;
	}

	/**
	 * Check if a modifier is registered.
	 *
	 * @param string $name Modifier name.
	 *
	 * @return bool
	 */
	public function has( string $name ): bool {
		return isset( $this->modifiers[ $name ] );
	}

	/**
	 * Apply a modifier to a value.
	 *
	 * Runs the registered callable (if any), then passes the result through
	 * a WordPress filter for external customisation.
	 *
	 * @param string $name  Modifier name.
	 * @param string $value The input value.
	 * @param array  $args  Additional arguments for the modifier.
	 *
	 * @return string
	 */
	public function apply( string $name, string $value, array $args ): string {
		$result = $value;

		if ( isset( $this->modifiers[ $name ] ) ) {
			$result = (string) call_user_func( $this->modifiers[ $name ], $value, $args );
		}

		return (string) apply_filters( "wci_modifier_{$name}", $result, $value, $args );
	}

	/**
	 * Register the default built-in modifiers.
	 *
	 * @return void
	 */
	public function register_defaults(): void {
		$this->register( 'upper', fn( string $v ): string => mb_strtoupper( $v ), __( 'Uppercase', 'wp-content-importer' ), '{column|upper}' );
		$this->register( 'lower', fn( string $v ): string => mb_strtolower( $v ), __( 'Lowercase', 'wp-content-importer' ), '{column|lower}' );
		$this->register( 'capitalize', fn( string $v ): string => mb_strtoupper( mb_substr( $v, 0, 1 ) ) . mb_substr( $v, 1 ), __( 'Uppercase first letter', 'wp-content-importer' ), '{column|capitalize}' );
		$this->register( 'trim', fn( string $v ): string => trim( $v ), __( 'Strip whitespace', 'wp-content-importer' ), '{column|trim}' );
		$this->register( 'slug', fn( string $v ): string => sanitize_title( $v ), __( 'URL-friendly slug', 'wp-content-importer' ), '{column|slug}' );
		$this->register( 'striptags', fn( string $v ): string => wp_strip_all_tags( $v ), __( 'Remove HTML tags', 'wp-content-importer' ), '{column|striptags}' );

		$this->register(
			'date',
			function ( string $v, array $args ): string {
				$format    = $args[0] ?? 'Y-m-d';
				$timestamp = strtotime( $v );

				return false !== $timestamp ? gmdate( $format, $timestamp ) : $v;
			},
			__( 'Format a date string', 'wp-content-importer' ),
			"{column|date('Y-m-d')}"
		);

		$this->register(
			'number_format',
			function ( string $v, array $args ): string {
				$decimals  = (int) ( $args[0] ?? 0 );
				$dec_point = $args[1] ?? '.';
				$thousands = $args[2] ?? '';

				return is_numeric( $v ) ? number_format( (float) $v, $decimals, $dec_point, $thousands ) : $v;
			},
			__( 'Format a number', 'wp-content-importer' ),
			"{column|number_format(2, ',', '.')}"
		);

		$this->register(
			'replace',
			function ( string $v, array $args ): string {
				$search  = $args[0] ?? '';
				$replace = $args[1] ?? '';

				return str_replace( $search, $replace, $v );
			},
			__( 'Replace text', 'wp-content-importer' ),
			"{column|replace('a', 'b')}"
		);

		$this->register(
			'truncate',
			function ( string $v, array $args ): string {
				$length = (int) ( $args[0] ?? 100 );

				return mb_strlen( $v ) > $length ? mb_substr( $v, 0, $length ) . '...' : $v;
			},
			__( 'Truncate to length', 'wp-content-importer' ),
			'{column|truncate(100)}'
		);

		$this->register(
			'if',
			function ( string $v, array $args ): string {
				$match = $args[0] ?? '';
				$then  = $args[1] ?? $v;
				$else  = $args[2] ?? $v;

				return $v === $match ? $then : $else;
			},
			__( 'Map value conditionally', 'wp-content-importer' ),
			"{column|if('match', 'then', 'else')}"
		);
	}
}

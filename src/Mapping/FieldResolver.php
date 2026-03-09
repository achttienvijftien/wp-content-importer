<?php
/**
 * Field resolver.
 *
 * @package AchttienVijftien\WpContentImporter
 */

namespace AchttienVijftien\WpContentImporter\Mapping;

use AchttienVijftien\WpContentImporter\FieldProvider\AcfFieldProvider;
use AchttienVijftien\WpContentImporter\FieldProvider\CoreFieldProvider;
use AchttienVijftien\WpContentImporter\FieldProvider\FieldProviderInterface;
use AchttienVijftien\WpContentImporter\FieldProvider\RegisteredMetaProvider;
use AchttienVijftien\WpContentImporter\FieldProvider\TaxonomyFieldProvider;

/**
 * Resolves available fields for a post type by aggregating multiple field providers.
 */
class FieldResolver {

	/**
	 * Registered field providers.
	 *
	 * @var FieldProviderInterface[]
	 */
	private array $providers;

	/**
	 * Constructor.
	 *
	 * @param FieldProviderInterface[] $providers Array of field provider instances.
	 */
	public function __construct( array $providers = [] ) {
		$this->providers = $providers;
	}

	/**
	 * Create a FieldResolver with the default set of providers.
	 *
	 * @return self Configured FieldResolver instance.
	 */
	public static function create(): self {
		$providers = [
			new CoreFieldProvider(),
		];

		$acf_provider = new AcfFieldProvider();

		if ( AcfFieldProvider::is_available() ) {
			$providers[] = $acf_provider;
		}

		$providers[] = new TaxonomyFieldProvider();

		// Exclude ACF-managed meta keys from the generic meta provider.
		$exclude_prefixes = [ '_' ];
		$providers[]      = new RegisteredMetaProvider( $exclude_prefixes );

		return new self( $providers );
	}

	/**
	 * Resolve all fields available for a post type.
	 *
	 * @param string $post_type The post type slug.
	 *
	 * @return array[] Merged list of field definitions from all providers.
	 */
	public function resolve( string $post_type ): array {
		$fields = [];

		foreach ( $this->providers as $provider ) {
			$fields = array_merge( $fields, $provider->get_fields( $post_type ) );
		}

		return $fields;
	}
}

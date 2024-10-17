<?php

namespace MediaWiki\Extension\CommunityConfiguration\EmergencyShutdown;

use InvalidArgumentException;
use MediaWiki\Extension\CommunityConfiguration\Provider\IConfigurationProvider;
use MediaWiki\Extension\CommunityConfiguration\Schema\SchemaBuilder;
use stdClass;

class EmergencyDefaultsUpdater {

	private EmergencyDefaultsPathBuilder $pathBuilder;

	public function __construct( EmergencyDefaultsPathBuilder $pathBuilder ) {
		$this->pathBuilder = $pathBuilder;
	}

	/**
	 * Get directory that contains emergency defaults for a given extension
	 *
	 * @deprecated since 1.43, use EmergencyDefaultsPathBuilder instead
	 * @throws InvalidArgumentException if $extension is not loaded
	 * @param string $extension Extension name (pass the same as passed to eg. wfLoadExtension)
	 * @return string Filesystem path to the directory
	 */
	public function getDefaultsDirectory( string $extension ): string {
		wfDeprecated( __METHOD__, '1.43' );
		return $this->pathBuilder->getDefaultsDirectory( $extension );
	}

	/**
	 * Get file that contains emergency defaults for a given provider
	 *
	 * @deprecated since 1.43, use EmergencyDefaultsPathBuilder instead
	 * @throws InvalidArgumentException if $extension is not loaded
	 * @param IConfigurationProvider $provider
	 * @param string $extensionName Treat the provider as registered by $extensionName (pass
	 * extension name as passed to eg. wfLoadExtension)
	 * @return string Path to the file
	 */
	public function getDefaultsFileForProvider(
		IConfigurationProvider $provider,
		string $extensionName
	): string {
		wfDeprecated( __METHOD__, '1.43' );
		return $this->pathBuilder->getDefaultsFileForProvider( $provider, $extensionName );
	}

	/**
	 * Get emergency defaults from a provider
	 *
	 * This is the same as the defaults map, except it ignores dynamic defaults.
	 *
	 * @see SchemaBuilder::getDefaultsMap()
	 * @param IConfigurationProvider $provider
	 * @return stdClass
	 */
	public function getEmergencyDefaultsForProvider( IConfigurationProvider $provider ): stdClass {
		return $provider->getValidator()->getSchemaBuilder()->getDefaultsMap( null, false );
	}

	/**
	 * Compute emergency defaults for a given provider
	 *
	 * This does not store the results anywhere; this is left as a responsibility of the caller.
	 *
	 * Dynamic defaults are deliberately ignored; the purpose for emergency defaults is to
	 * provide a sane default while dealing with a system emergency. To make things simpler,
	 * defaults are the same for all wikis.
	 *
	 * @throws InvalidArgumentException if $provider does not support schemas
	 * @param IConfigurationProvider $provider
	 * @return string
	 */
	public function getSerializedDefaults( IConfigurationProvider $provider ): string {
		if ( !$provider->getValidator()->areSchemasSupported() ) {
			throw new InvalidArgumentException(
				'Provider ' . $provider->getId() . ' does not support schemas'
			);
		}

		return '<?php' . PHP_EOL
			. '// phpcs:ignoreFile' . PHP_EOL
			. '// This file was automatically generated. Please update it via '
			. 'extensions/CommunityConfiguration/UpdateEmergencyDefaults.php maintenance script' . PHP_EOL
			. 'return '
			. var_export( $this->getEmergencyDefaultsForProvider( $provider ), true )
			. ';' . PHP_EOL;
	}
}

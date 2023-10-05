<?php

namespace MediaWiki\Extension\CommunityConfiguration\Storage;

use InvalidArgumentException;
use MediaWiki\Config\Config;
use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\CommunityConfiguration\Validation\IValidator;
use Wikimedia\ObjectFactory\ObjectFactory;

/**
 * Create a configuration storage object
 * @see IConfigurationStore for further documentation
 */
class StorageFactory {

	/**
	 * @var string[]
	 * @internal for use in ServiceWiring only
	 */
	public const CONSTRUCTOR_OPTIONS = [
		'CommunityConfigurationStorages',
	];

	/** @var array ObjectFactory specs for validators, indexed by validator name */
	private array $storageSpecs;
	/** @var IValidator[] validators indexed by name */
	private array $storages = [];
	private ObjectFactory $objectFactory;
	private Config $mainConfig;

	/**
	 * @param ServiceOptions $options
	 * @param ObjectFactory $objectFactory
	 */
	public function __construct(
		ServiceOptions $options,
		ObjectFactory $objectFactory,
		Config $mainConfig
	) {
		$options->assertRequiredOptions( self::CONSTRUCTOR_OPTIONS );
		$this->storageSpecs = $options->get( 'CommunityConfigurationStorages' );

		$this->objectFactory = $objectFactory;
		$this->mainConfig = $mainConfig;
	}

	/**
	 * @param string $name
	 * // TODO Use Uris?
	 * @param string|null $storageLocation
	 * @return IConfigurationStore
	 */
	public function newStorage( string $name, string $providerName, ?string $storageLocation ): IConfigurationStore {
		if ( !array_key_exists( $name, $this->storageSpecs ) ) {
			throw new InvalidArgumentException( "Storage $name is not supported" );
		}
		if ( !array_key_exists( $name, $this->storages ) ) {
			$this->storages[$name] = $this->objectFactory->createObject(
				$this->storageSpecs[$name],
				[
					'assertClass' => IConfigurationStore::class,
					'extraArgs' => [
						$this->mainConfig,
						$providerName,
						$storageLocation
					]
				],
			);
		}
		return $this->storages[$name];
	}

	/**
	 * Return a list of supported storage backends
	 *
	 * @return string[] List of storage names (supported by newStorage)
	 */
	public function getSupportedKeys(): array {
		return array_keys( $this->storageSpecs );
	}
}

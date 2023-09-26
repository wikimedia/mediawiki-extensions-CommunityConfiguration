<?php

namespace MediaWiki\Extension\CommunityConfiguration\Storage;

use InvalidArgumentException;
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

	/**
	 * @param ServiceOptions $options
	 * @param ObjectFactory $objectFactory
	 */
	public function __construct(
		ServiceOptions $options,
		ObjectFactory $objectFactory
	) {
		$options->assertRequiredOptions( self::CONSTRUCTOR_OPTIONS );
		$this->storageSpecs = $options->get( 'CommunityConfigurationStorages' );

		$this->objectFactory = $objectFactory;
	}

	/**
	 * @param string $name
	 * @return IConfigurationStore
	 */
	public function newStorage( string $name ): IConfigurationStore {
		if ( !array_key_exists( $name, $this->storageSpecs ) ) {
			throw new InvalidArgumentException( "Storage $name is not supported" );
		}
		if ( !array_key_exists( $name, $this->storages ) ) {
			$this->storages[$name] = $this->objectFactory->createObject(
				$this->storageSpecs[$name],
				[ 'assertClass' => IConfigurationStore::class ],
			);
		}
		return $this->storages[$name];
	}
}

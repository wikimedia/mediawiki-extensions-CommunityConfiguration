<?php

namespace MediaWiki\Extension\CommunityConfiguration\Store;

use InvalidArgumentException;
use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\CommunityConfiguration\Utils;
use MediaWiki\Registration\ExtensionRegistry;
use Wikimedia\ObjectFactory\ObjectFactory;

/**
 * Create a configuration store object
 * @see IConfigurationStore for further documentation
 */
class StoreFactory {

	/**
	 * @internal for use in ServiceWiring only
	 */
	public const CONSTRUCTOR_OPTIONS = [
		'CommunityConfigurationStores',
	];

	/** @var array ObjectFactory specs for validators, indexed by validator name */
	private array $storeSpecs;
	/** @var IConfigurationStore[] validators indexed by name */
	private array $stores = [];
	private ObjectFactory $objectFactory;

	public function __construct(
		ServiceOptions $options,
		ObjectFactory $objectFactory,
		ExtensionRegistry $extensionRegistry
	) {
		$options->assertRequiredOptions( self::CONSTRUCTOR_OPTIONS );
		$this->storeSpecs = Utils::getMergedAttribute(
			$options, $extensionRegistry,
			'CommunityConfigurationStores'
		);

		$this->objectFactory = $objectFactory;
	}

	public function newStore(
		string $name,
		string $type,
		array $storeArgs,
		array $storeOptions = []
	): IConfigurationStore {
		if ( !array_key_exists( $type, $this->storeSpecs ) ) {
			throw new InvalidArgumentException( "Store $type is not supported" );
		}
		$storeKey = $name . '_' . $type;
		if ( !array_key_exists( $storeKey, $this->stores ) ) {
			$this->stores[$storeKey] = $this->objectFactory->createObject(
				$this->storeSpecs[$type],
				[
					'assertClass' => IConfigurationStore::class,
					'extraArgs' => $storeArgs,
				],
			);
			$this->stores[$storeKey]->setOptions( $storeOptions );
		}
		return $this->stores[$storeKey];
	}

	/**
	 * Return a list of supported store backends
	 *
	 * @return string[] List of store names (supported by newStore)
	 */
	public function getSupportedKeys(): array {
		return array_keys( $this->storeSpecs );
	}
}

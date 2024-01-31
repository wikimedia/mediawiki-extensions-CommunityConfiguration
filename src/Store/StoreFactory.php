<?php

namespace MediaWiki\Extension\CommunityConfiguration\Store;

use InvalidArgumentException;
use MediaWiki\Config\ServiceOptions;
use Wikimedia\ObjectFactory\ObjectFactory;

/**
 * Create a configuration store object
 * @see IConfigurationStore for further documentation
 */
class StoreFactory {

	/**
	 * @var string[]
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

	/**
	 * @param ServiceOptions $options
	 * @param ObjectFactory $objectFactory
	 */
	public function __construct(
		ServiceOptions $options,
		ObjectFactory $objectFactory
	) {
		$options->assertRequiredOptions( self::CONSTRUCTOR_OPTIONS );
		$this->storeSpecs = $options->get( 'CommunityConfigurationStores' );

		$this->objectFactory = $objectFactory;
	}

	/**
	 * @param string $name
	 * @param array $storeArgs
	 * @return IConfigurationStore
	 */
	public function newStore( string $name, array $storeArgs ): IConfigurationStore {
		if ( !array_key_exists( $name, $this->storeSpecs ) ) {
			throw new InvalidArgumentException( "Store $name is not supported" );
		}
		if ( !array_key_exists( $name, $this->stores ) ) {
			$this->stores[$name] = $this->objectFactory->createObject(
				$this->storeSpecs[$name],
				[
					'assertClass' => IConfigurationStore::class,
					'extraArgs' => $storeArgs,
				],
			);
		}
		return $this->stores[$name];
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

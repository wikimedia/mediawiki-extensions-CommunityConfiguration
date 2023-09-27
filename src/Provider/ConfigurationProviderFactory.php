<?php

namespace MediaWiki\Extension\CommunityConfiguration\Provider;

use InvalidArgumentException;
use LogicException;
use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\CommunityConfiguration\Storage\StorageFactory;
use MediaWiki\Extension\CommunityConfiguration\Validation\ValidatorFactory;
use MediaWiki\MediaWikiServices;

/**
 * Create a configuration provider
 * @see IConfigurationProvider for further documentation
 */
class ConfigurationProviderFactory {

	/**
	 * @var string[]
	 * @internal for use in ServiceWiring only
	 */
	public const CONSTRUCTOR_OPTIONS = [
		'CommunityConfigurationProviders',
	];

	private array $providerSpecs;
	private array $providers = [];
	private StorageFactory $storageFactory;
	private ValidatorFactory $validatorFactory;
	private MediaWikiServices $services;

	/**
	 * @param ServiceOptions $options
	 * @param ValidatorFactory $validatorFactory
	 * @param MediaWikiServices $services
	 */
	public function __construct(
		ServiceOptions $options,
		StorageFactory $storageFactory,
		ValidatorFactory $validatorFactory,
		MediaWikiServices $services
	) {
		$options->assertRequiredOptions( self::CONSTRUCTOR_OPTIONS );
		$this->providerSpecs = $options->get( 'CommunityConfigurationProviders' );

		$this->storageFactory = $storageFactory;
		$this->validatorFactory = $validatorFactory;
		$this->services = $services;
	}

	/**
	 * Unconditionally construct a provider
	 *
	 * @param string $name
	 * @return IConfigurationProvider
	 */
	private function constructProvider( string $name ): IConfigurationProvider {
		$spec = $this->providerSpecs[$name];
		$ctorArgs = [
			$this->storageFactory->newStorage( $spec['storage'] ),
			$this->validatorFactory->newValidator( $spec['validator'] )
		];

		foreach ( $spec['services'] ?? [] as $serviceName ) {
			$ctorArgs[] = $this->services->getService( $serviceName );
		}
		$ctorArgs = array_merge( $ctorArgs, $spec['args'] ?? [] );

		$className = $spec['type'];
		$provider = new $className(...$ctorArgs);
		if ( !$provider instanceof IConfigurationProvider ) {
			throw new LogicException( "$className is not an instance of IConfigurationProvider" );
		}
		return $provider;
	}

	public function newProvider( string $name ): IConfigurationProvider {
		if ( !array_key_exists( $name, $this->providerSpecs ) ) {
			throw new InvalidArgumentException( "Provider $name is not supported" );
		}
		if ( !array_key_exists( $name, $this->providers ) ) {
			$this->providers[$name] = $this->constructProvider( $name );
		}
		return $this->providers[$name];
	}

	/**
	 * Return a list of supported providers
	 *
	 * @return string[] List of storage names (supported by newProvider)
	 */
	public function getSupportedKeys(): array {
		return array_keys( $this->providerSpecs );
	}
}
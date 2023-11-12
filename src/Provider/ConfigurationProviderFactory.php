<?php

namespace MediaWiki\Extension\CommunityConfiguration\Provider;

use InvalidArgumentException;
use LogicException;
use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\CommunityConfiguration\Store\StoreFactory;
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
	private StoreFactory $storeFactory;
	private ValidatorFactory $validatorFactory;
	private MediaWikiServices $services;

	/**
	 * @param ServiceOptions $options
	 * @param ValidatorFactory $validatorFactory
	 * @param MediaWikiServices $services
	 */
	public function __construct(
		ServiceOptions    $options,
		StoreFactory      $storeFactory,
		ValidatorFactory  $validatorFactory,
		MediaWikiServices $services
	) {
		$options->assertRequiredOptions( self::CONSTRUCTOR_OPTIONS );
		$this->providerSpecs = $options->get( 'CommunityConfigurationProviders' );

		$this->storeFactory = $storeFactory;
		$this->validatorFactory = $validatorFactory;
		$this->services = $services;
	}


	private function getConstructType( array $spec, string $constructName ) {
		return is_string( $spec[ $constructName ] ) ? $spec[ $constructName ] : ( is_array( $spec[ $constructName ] ) ?
			$spec[ $constructName ]['type'] : null );
	}

	/**
	 * Unconditionally construct a provider
	 *
	 * @param string $name
	 * @return IConfigurationProvider
	 */
	private function constructProvider( string $name ): IConfigurationProvider {
		$spec = $this->providerSpecs[$name];
		$storeType = $this->getConstructType( $spec, 'store' );
		$validatorType = $this->getConstructType( $spec, 'validator' );
		if ( $storeType === null ) {
			throw new InvalidArgumentException(
				"Wrong type for \"store\" property for \"$name\" provider. Allowed types are: string, object"
			);
		}
		if ( $validatorType === null ) {
			throw new InvalidArgumentException(
				"Wrong type for \"validator\" property for \"$name\" provider. Allowed types are: string, object"
			);
		}
		$storeArgs = is_string( $spec['store'] ) ?  [ null ] : $spec['store']['args'];
		$validatorArgs = is_string( $spec['validator'] ) ?  [ null ] : $spec['validator']['args'];

		array_unshift( $storeArgs, $name );

		$ctorArgs = [
			$this->storeFactory->newStore( $storeType, ...$storeArgs ),
			$this->validatorFactory->newValidator( $validatorType, ...$validatorArgs )
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
	 * @return string[] List of provider names (supported by newProvider)
	 */
	public function getSupportedKeys(): array {
		// TODO remove array_filter once all provider specs are supported
		return array_filter( array_keys( $this->providerSpecs ), function( $providerName ) {
			// HACK prefix with underscore provider names which are not yet supported
			return !str_starts_with( $providerName, '_');
		} );
	}
}

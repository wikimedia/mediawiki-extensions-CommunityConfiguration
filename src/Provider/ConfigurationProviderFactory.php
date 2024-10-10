<?php

namespace MediaWiki\Extension\CommunityConfiguration\Provider;

use InvalidArgumentException;
use LogicException;
use MediaWiki\Config\Config;
use MediaWiki\Extension\CommunityConfiguration\Hooks\HookRunner;
use MediaWiki\Extension\CommunityConfiguration\Store\StoreFactory;
use MediaWiki\Extension\CommunityConfiguration\Utils;
use MediaWiki\Extension\CommunityConfiguration\Validation\ValidatorFactory;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use MediaWiki\Registration\ExtensionRegistry;

/**
 * Create a configuration provider
 * @see IConfigurationProvider for further documentation
 */
class ConfigurationProviderFactory {

	/** @var string */
	private const DEFAULT_PROVIDER_TYPE = 'data';

	/** Lazy loaded in initList */
	private ?array $providerSpecs = null;
	private ?array $classSpecs = null;
	private array $providers = [];
	private StoreFactory $storeFactory;
	private ValidatorFactory $validatorFactory;
	/** Used to create the services associated to a provider */
	private MediaWikiServices $services;
	private HookRunner $hookRunner;
	private Config $config;
	private ExtensionRegistry $extensionRegistry;

	public function __construct(
		StoreFactory $storeFactory,
		ValidatorFactory $validatorFactory,
		Config $config,
		ExtensionRegistry $extensionRegistry,
		HookRunner $hookRunner,
		MediaWikiServices $services
	) {
		$this->storeFactory = $storeFactory;
		$this->validatorFactory = $validatorFactory;
		$this->config = $config;
		$this->extensionRegistry = $extensionRegistry;
		$this->services = $services;
		$this->hookRunner = $hookRunner;
	}

	/**
	 * @param array $spec
	 * @param string $constructName
	 * @return mixed|string|null
	 */
	private function getConstructType( array $spec, string $constructName ) {
		return is_string( $spec[ $constructName ] ) ? $spec[ $constructName ] : ( is_array( $spec[ $constructName ] ) ?
			$spec[ $constructName ]['type'] : null );
	}

	/**
	 * @param array $spec
	 * @param string $constructName
	 * @return mixed|string|null
	 */
	private function getConstructArgs( array $spec, string $constructName ) {
		return is_string( $spec[ $constructName ] ) ? $spec[ $constructName ] : ( is_array( $spec[ $constructName ] ) ?
			( $spec[ $constructName ]['args'] ?? [] ) : [] );
	}

	private function getConstructOptions( array $spec, string $constructName ): array {
		if ( !is_array( $spec[$constructName] ) ) {
			return [];
		}
		return $spec[$constructName]['options'] ?? [];
	}

	private function getProviderClassSpec( string $className ): array {
		if ( !array_key_exists( $className, $this->classSpecs ?? [] ) ) {
			throw new InvalidArgumentException( "Provider class $className is not supported" );
		}
		// @phan-suppress-next-line PhanTypeArraySuspiciousNullable
		return $this->classSpecs[$className];
	}

	/**
	 * Unconditionally construct a provider
	 *
	 * @param string $providerId The provider's key as set in extension.json
	 * @return IConfigurationProvider
	 * @throws InvalidArgumentException when the definition of provider is invalid
	 */
	private function constructProvider( string $providerId ): IConfigurationProvider {
		if ( !array_key_exists( $providerId, $this->providerSpecs ) ) {
			throw new InvalidArgumentException( "Provider $providerId is not supported" );
		}
		$spec = $this->providerSpecs[$providerId];
		$storeType = $this->getConstructType( $spec, 'store' );

		$validatorType = $this->getConstructType( $spec, 'validator' );
		if ( $storeType === null ) {
			throw new InvalidArgumentException(
				"Wrong type for \"store\" property for \"$providerId\" provider. Allowed types are: string, object"
			);
		}
		if ( $validatorType === null ) {
			throw new InvalidArgumentException(
				"Wrong type for \"validator\" property for \"$providerId\" provider. Allowed types are: string, object"
			);
		}
		$storeArgs = $this->getConstructArgs( $spec, 'store' );
		$validatorArgs = $this->getConstructArgs( $spec, 'validator' );

		$store = $this->storeFactory->newStore( $providerId, $storeType, $storeArgs );
		$store->setOptions( $this->getConstructOptions( $spec, 'store' ) );
		$ctorArgs = [
			$providerId,
			$spec['options'] ?? [],
			$store,
			$this->validatorFactory->newValidator( $providerId, $validatorType, $validatorArgs ),
		];

		$classSpec = $this->getProviderClassSpec( $spec['type'] ?? self::DEFAULT_PROVIDER_TYPE );

		foreach ( $spec['services'] ?? [] as $serviceName ) {
			$ctorArgs[] = $this->services->getService( $serviceName );
		}
		$ctorArgs = array_merge( $ctorArgs, $spec['args'] ?? [] );

		$className = $classSpec['class'];
		$provider = new $className( ...$ctorArgs );
		if ( !$provider instanceof IConfigurationProvider ) {
			throw new LogicException( "$className is not an instance of IConfigurationProvider" );
		}
		$provider->setLogger( LoggerFactory::getInstance( 'CommunityConfiguration' ) );
		return $provider;
	}

	/**
	 * @param string $providerId The provider's key as set in extension.json
	 * @return IConfigurationProvider
	 * @throws InvalidArgumentException when provider $name is not registered
	 */
	public function newProvider( string $providerId ): IConfigurationProvider {
		$this->initList();
		if ( !array_key_exists( $providerId, $this->providerSpecs ) ) {
			throw new InvalidArgumentException( "Provider $providerId is not supported" );
		}
		if ( !array_key_exists( $providerId, $this->providers ) ) {
			$this->providers[$providerId] = $this->constructProvider( $providerId );
		}
		return $this->providers[$providerId];
	}

	/**
	 * Return a list of supported provider names
	 *
	 * @return string[] List of provider names (supported by newProvider)
	 */
	public function getSupportedKeys(): array {
		$this->initList();
		return array_keys( $this->providerSpecs );
	}

	/**
	 * Build the list of provider specs by reading CommunityConfigurationProviders from
	 * main config and give a chance to extensions to modify it by running _initList hook.
	 */
	private function initList() {
		if ( is_array( $this->providerSpecs ) && is_array( $this->classSpecs ) ) {
			return;
		}
		$this->providerSpecs = Utils::getMergedAttribute(
			$this->config, $this->extensionRegistry,
			'CommunityConfigurationProviders'
		);
		$this->classSpecs = Utils::getMergedAttribute(
			$this->config, $this->extensionRegistry,
			'CommunityConfigurationProviderClasses'
		);
		// This hook can be used to disable unwanted providers
		// or conditionally register providers.
		$this->hookRunner->onCommunityConfigurationProvider_initList( $this->providerSpecs );
	}
}

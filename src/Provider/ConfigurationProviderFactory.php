<?php

namespace MediaWiki\Extension\CommunityConfiguration\Provider;

use InvalidArgumentException;
use LogicException;
use MediaWiki\Config\Config;
use MediaWiki\Extension\CommunityConfiguration\Hooks\HookRunner;
use MediaWiki\Extension\CommunityConfiguration\Store\StoreFactory;
use MediaWiki\Extension\CommunityConfiguration\Utils;
use MediaWiki\Extension\CommunityConfiguration\Validation\ValidatorFactory;
use MediaWiki\Registration\ExtensionRegistry;
use Psr\Log\LoggerInterface;
use Wikimedia\ObjectFactory\ObjectFactory;

/**
 * Create a configuration provider
 * @see IConfigurationProvider for further documentation
 */
class ConfigurationProviderFactory {

	private const DEFAULT_PROVIDER_TYPE = 'data';

	/** Lazy loaded in initList */
	private ?array $providerSpecs = null;
	private ?array $classSpecs = null;
	private array $providers = [];
	private LoggerInterface $logger;
	private StoreFactory $storeFactory;
	private ValidatorFactory $validatorFactory;
	private ProviderServicesContainer $providerServicesContainer;
	private Config $config;
	private ExtensionRegistry $extensionRegistry;
	private ObjectFactory $objectFactory;
	private HookRunner $hookRunner;

	public function __construct(
		LoggerInterface $logger,
		StoreFactory $storeFactory,
		ValidatorFactory $validatorFactory,
		ProviderServicesContainer $providerServicesContainer,
		Config $config,
		ExtensionRegistry $extensionRegistry,
		ObjectFactory $objectFactory,
		HookRunner $hookRunner
	) {
		$this->logger = $logger;
		$this->storeFactory = $storeFactory;
		$this->validatorFactory = $validatorFactory;
		$this->providerServicesContainer = $providerServicesContainer;
		$this->config = $config;
		$this->extensionRegistry = $extensionRegistry;
		$this->objectFactory = $objectFactory;
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
	 * Return provider specs
	 *
	 * @internal Mostly useful for tests
	 * @param string $providerId
	 * @return array
	 * @throws InvalidArgumentException when the provider does not exist
	 */
	public function getProviderSpec( string $providerId ): array {
		$this->initList();
		if ( !array_key_exists( $providerId, $this->providerSpecs ) ) {
			throw new InvalidArgumentException( "Provider $providerId is not supported" );
		}
		return $this->providerSpecs[$providerId];
	}

	/**
	 * Unconditionally construct a provider
	 *
	 * @param string $providerId The provider's key as set in extension.json
	 * @return IConfigurationProvider
	 * @throws InvalidArgumentException when the definition of provider is invalid
	 */
	private function constructProvider( string $providerId ): IConfigurationProvider {
		$providerSpec = $this->getProviderSpec( $providerId );
		$storeType = $this->getConstructType( $providerSpec, 'store' );

		$validatorType = $this->getConstructType( $providerSpec, 'validator' );
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

		$store = $this->storeFactory->newStore(
			$providerId, $storeType,
			$this->getConstructArgs( $providerSpec, 'store' ),
			$this->getConstructOptions( $providerSpec, 'store' )
		);
		$validator = $this->validatorFactory->newValidator(
			$providerId, $validatorType,
			$this->getConstructArgs( $providerSpec, 'validator' )
		);

		$classSpec = $this->getProviderClassSpec( $providerSpec['type'] ?? self::DEFAULT_PROVIDER_TYPE );
		$className = $classSpec['class'] ?? null;
		$supportsServices = false;
		// NOTE: This does not work if something else than `class` (like a `factory` callable) is
		// used in the specs. As of writing, no one does that, and this is essentially code we
		// add to make CI happy, so this shouldn't be a problem, but I'm noting it here for
		// completeness.
		if ( $className ) {
			$classReflection = new \ReflectionClass( $classSpec['class'] );
			$classCtor = $classReflection->getConstructor();

			if ( $classCtor->getDeclaringClass()->getName() === AbstractProvider::class ) {
				// AbstractProvider supports both signatures, but we prefer the newer one
				$supportsServices = true;
			} else {
				// If the first param is the services container, the new signature is in use.
				$firstParam = $classCtor->getParameters()[0];
				$firstParamType = $firstParam->getType();
				$supportsServices = $firstParamType instanceof \ReflectionNamedType
					&& !$firstParamType->isBuiltin()
					&& $firstParamType->getName() === ProviderServicesContainer::class;
			}
		}

		$extraArgs = [
			$providerId,
			$providerSpec['options'] ?? [],
			$store,
			$validator,
		];
		if ( $supportsServices ) {
			array_unshift( $extraArgs, $this->providerServicesContainer );
		}

		$provider = $this->objectFactory->createObject(
			$classSpec,
			[
				'assertClass' => IConfigurationProvider::class,
				'extraArgs' => $extraArgs,
			]
		);

		if ( !$provider instanceof IConfigurationProvider ) {
			// @codeCoverageIgnoreStart
			// should be impossible to happen, but it makes type-hinting possible
			throw new LogicException(
				'ObjectFactory asserted IConfigurationProvider, but returned something else'
			);
		}
		// @codeCoverageIgnoreEnd
		$provider->setLogger( $this->logger );
		return $provider;
	}

	/**
	 * @param string $providerId The provider's key as set in extension.json
	 * @return IConfigurationProvider
	 * @throws InvalidArgumentException when the requested provider is not registered (caller can
	 * check isProviderSupported to avoid an exception)
	 */
	public function newProvider( string $providerId ): IConfigurationProvider {
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
	 * Return a list of supported provider names that have an UI
	 *
	 * @see IConfigurationProvider::OPTION_EXCLUDE_FROM_UI
	 * @return string[] List of provider IDs (that can be passed to newProvider)
	 */
	public function getSupportedKeysWithUI(): array {
		$allProviders = $this->getSupportedKeys();
		return array_filter( $allProviders, function ( string $providerId ) {
			// Semantically, the correct way would be constructing the provider via
			// newProvider( $providerId) and then calling
			// getOptionValue( IConfigurationProvider::OPTION_EXCLUDE_FROM_UI ),
			// however, that requires actually constructing the full provider.
			// Optimize by reading the specs directly, which is cheaper.
			$options = $this->getProviderSpec( $providerId )['options'] ?? [];
			return !( $options[ IConfigurationProvider::OPTION_EXCLUDE_FROM_UI ] ?? false );
		} );
	}

	/**
	 * Is a provider supported?
	 *
	 * @param string $providerId
	 * @return bool
	 */
	public function isProviderSupported( string $providerId ): bool {
		return in_array( $providerId, $this->getSupportedKeys() );
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

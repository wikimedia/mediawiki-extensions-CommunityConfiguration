<?php

namespace MediaWiki\Extension\CommunityConfiguration\Provider;

use InvalidArgumentException;
use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\CommunityConfiguration\Storage\StaticStorage;
use MediaWiki\Extension\CommunityConfiguration\Validation\ValidatorFactory;
use Wikimedia\ObjectFactory\ObjectFactory;

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
	private ValidatorFactory $validatorFactory;
	private ObjectFactory $objectFactory;

	/**
	 * @param ServiceOptions $options
	 * @param ObjectFactory $objectFactory
	 */
	public function __construct(
		ServiceOptions $options,
		ValidatorFactory $validatorFactory,
		ObjectFactory $objectFactory
	) {
		$options->assertRequiredOptions( self::CONSTRUCTOR_OPTIONS );
		$this->providerSpecs = $options->get( 'CommunityConfigurationProviders' );

		$this->validatorFactory = $validatorFactory;
		$this->objectFactory = $objectFactory;
	}

	/**
	 * Unconditionally construct a provider
	 *
	 * @param string $name
	 * @return IConfigurationProvider
	 */
	private function constructProvider( string $name ): IConfigurationProvider {
		return new DataConfigurationProvider(
			new StaticStorage(),
			$this->validatorFactory->newValidator( 'noop' )
		);

		// TODO: This is not going to work, because DataConfigurationProvider needs
		// IConfigurationStore and IValidator first, before all other additional services.
		$spec = $this->providerSpecs[$name];
		$objectFactorySpec = [
			'class' => $spec['type']
		];
		foreach ( [ 'services', 'args' ] as $property ) {
			if ( array_key_exists( $property, $spec ) ) {
				$objectFactorySpec[$property] = $spec[$property];
			}
		}

		$provider = $this->objectFactory->createObject(
			$objectFactorySpec,
			[ 'assertClass' => IConfigurationProvider::class ]
		);
		if ( !$provider instanceof IConfigurationProvider ) {
			// This is here for phan reasons
			throw new \LogicException( 'ObjectFactory did not assert class' );
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
}
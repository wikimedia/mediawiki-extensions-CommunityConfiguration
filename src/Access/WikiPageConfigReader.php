<?php

namespace MediaWiki\Extension\CommunityConfiguration\Access;

use BagOStuff;
use LogicException;
use MediaWiki\Config\Config;
use MediaWiki\Config\ConfigException;
use MediaWiki\Extension\CommunityConfiguration\Provider\ConfigurationProviderFactory;
use MediaWiki\Extension\CommunityConfiguration\Provider\WikiPageConfigProvider;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Wikimedia\LightweightObjectStore\ExpirationAwareness;

class WikiPageConfigReader implements Config {
	use LoggerAwareTrait;

	private BagOStuff $cache;
	private ConfigurationProviderFactory $providerFactory;
	private Config $fallbackConfig;

	public function __construct(
		BagOStuff $cache,
		ConfigurationProviderFactory $providerFactory,
		Config $fallbackConfig
	) {
		$this->cache = $cache;
		$this->providerFactory = $providerFactory;
		$this->fallbackConfig = $fallbackConfig;

		$this->setLogger( new NullLogger() );
	}

	private function addWikiPageConfigProviderKeysToMap(
		WikiPageConfigProvider $provider,
		array &$map
	): void {
		$supportedConfigKeys = $provider->getSupportedConfigVariableNames();
		foreach ( $supportedConfigKeys as $configKey ) {
			if ( isset( $map[$configKey] ) ) {
				throw new ConfigException(
					'Config variable ' . $configKey
					. ' is registered by multiple CommunityConfiguration providers.'
				);
			}
			$map[$configKey] = $provider->getId();
		}
	}

	/**
	 * Calculate the config key => configuration provider map
	 *
	 * @return string[]
	 */
	private function computeVariableToProviderMap(): array {
		$map = [];
		$providerKeys = $this->providerFactory->getSupportedKeys();
		foreach ( $providerKeys as $providerKey ) {
			$provider = $this->providerFactory->newProvider( $providerKey );
			if ( $provider instanceof WikiPageConfigProvider ) {
				$this->addWikiPageConfigProviderKeysToMap( $provider, $map );
			} else {
				// TODO: Add some support for other providers
				$this->logger->warning(
					__CLASS__ . ' skipped {provider}, because '
					. 'getSupportedConfigVariableNames() returned null.',
					[ 'provider' => $provider->getId() ]
				);
			}
		}
		return $map;
	}

	/**
	 * Get the cached variable to configuration provider map
	 *
	 * This is used to determine which config key can be handled by which configuration provider.
	 *
	 * @return string[] Config key => provider key
	 */
	private function getVariableToProviderMap(): array {
		return $this->cache->getWithSetCallback(
			$this->cache->makeKey( __CLASS__,
				'VariableToProviderMap'
			),
			ExpirationAwareness::TTL_DAY,
			function () {
				return $this->computeVariableToProviderMap();
			}
		);
	}

	/**
	 * Create a configuration provider from given key and ensure it is a WikiPageConfigProvider
	 *
	 * @param string $providerKey
	 * @return WikiPageConfigProvider
	 */
	private function getWikiPageConfigProviderByName( string $providerKey ): WikiPageConfigProvider {
		$provider = $this->providerFactory->newProvider( $providerKey );
		if ( !$provider instanceof WikiPageConfigProvider ) {
			throw new LogicException(
				$providerKey . ' is expected to be a WikiPageConfigProvider'
			);
		}
		return $provider;
	}

	/**
	 * Get Config instance to handle requests for $name config key
	 *
	 * @param string $name
	 * @return Config
	 */
	private function getConfigByVariableName( string $name ): Config {
		$map = $this->getVariableToProviderMap();
		if ( isset( $map[$name] ) ) {
			return $this->getWikiPageConfigProviderByName( $map[$name] );
		} else {
			return $this->fallbackConfig;
		}
	}

	/**
	 * @inheritDoc
	 */
	public function get( $name ) {
		return $this->getConfigByVariableName( $name )->get( $name );
	}

	/**
	 * @inheritDoc
	 */
	public function has( $name ) {
		return $this->getConfigByVariableName( $name )->has( $name );
	}
}

<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\CommunityConfiguration\Access;

use LogicException;
use MediaWiki\Config\Config;
use MediaWiki\Config\ConfigException;
use MediaWiki\Extension\CommunityConfiguration\Provider\ConfigurationProviderFactory;
use MediaWiki\Extension\CommunityConfiguration\Provider\MediaWikiConfigProvider;
use Psr\Log\LoggerInterface;
use Wikimedia\LightweightObjectStore\ExpirationAwareness;
use Wikimedia\ObjectCache\BagOStuff;

/**
 * Service exclusive for configuration that belongs to CommunityConfiguration.
 */
class MediaWikiConfigReader implements Config {

	private BagOStuff $cache;
	private ConfigurationProviderFactory $providerFactory;
	private LoggerInterface $logger;

	public function __construct(
		BagOStuff $cache,
		ConfigurationProviderFactory $providerFactory,
		LoggerInterface $logger
	) {
		$this->cache = $cache;
		$this->providerFactory = $providerFactory;
		$this->logger = $logger;
	}

	private function addMediaWikiConfigProviderKeysToMap(
		MediaWikiConfigProvider $provider,
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
			if ( $provider instanceof MediaWikiConfigProvider ) {
				$this->addMediaWikiConfigProviderKeysToMap( $provider, $map );
			} else {
				// TODO: Add some support for other providers
				$this->logger->debug(
					__CLASS__ . ' skipped {provider}, because '
					. 'it is not a MediaWikiConfigProvider.',
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
			$this->computeVariableToProviderMap( ... )
		);
	}

	/**
	 * Create a configuration provider from given key and ensure it is a MediaWikiConfigProvider
	 */
	private function getMediaWikiConfigProviderByName( string $providerKey ): MediaWikiConfigProvider {
		$provider = $this->providerFactory->newProvider( $providerKey );
		if ( !$provider instanceof MediaWikiConfigProvider ) {
			throw new LogicException(
				$providerKey . ' is expected to be a MediaWikiConfigProvider'
			);
		}
		return $provider;
	}

	/**
	 * Get Config instance to handle requests for $name config key
	 */
	private function getConfigByVariableName( string $name ): Config {
		$map = $this->getVariableToProviderMap();
		if ( !isset( $map[$name] ) ) {
			throw new ConfigException(
				'Config variable ' . $name . ' not found in community configuration.' .
				'Should be requested via MediaWikiConfigRouter instead.'
			);
		}
		return $this->getMediaWikiConfigProviderByName( $map[$name] );
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
	public function has( $name ): bool {
		$map = $this->getVariableToProviderMap();
		if ( !isset( $map[$name] ) ) {
			return false;
		}
		$hasConfigValue = $this->getMediaWikiConfigProviderByName( $map[$name] )->has( $name );
		if ( !is_bool( $hasConfigValue ) ) {
			$this->logger->error(
				__METHOD__ . ' returned non-boolean value for "{configName}"',
				[
					'configName' => $name,
					'exception' => new \RuntimeException,
				]
			);
			$hasConfigValue = (bool)$hasConfigValue;
		}
		return $hasConfigValue;
	}
}

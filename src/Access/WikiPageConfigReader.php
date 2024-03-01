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

	/**
	 * @return string[]
	 */
	private function getVariableToProviderMap(): array {
		return $this->cache->getWithSetCallback(
			$this->cache->makeKey( __CLASS__,
				'VariableToProviderMap'
			),
			ExpirationAwareness::TTL_DAY,
			function () {
				$map = [];
				foreach ( $this->providerFactory->getSupportedKeys() as $providerKey ) {
					$provider = $this->providerFactory->newProvider( $providerKey );
					if ( $provider instanceof WikiPageConfigProvider ) {
						$supportedKeys = $provider->getSupportedConfigVariableNames();
						if ( $supportedKeys !== null ) {
							foreach ( $supportedKeys as $supportedKey ) {
								if ( isset( $map[$supportedKey] ) ) {
									throw new ConfigException(
										'Config variable ' . $supportedKey
										. ' is registered by multiple CommunityConfiguration providers.'
									);
								}
								$map[$supportedKey] = $providerKey;
							}
						} else {
							// TODO: When getSupportedConfigVariableNames() returns null, all
							// configuration keys are supported. Instead of skipping, consult all
							// of them?
							$this->logger->warning(
								__CLASS__ . ' skipped {provider}, because '
									. 'getSupportedConfigVariableNames() returned null.',
								[ 'provider' => $provider->getName() ]
							);
						}
					}
				}
				return $map;
			}
		);
	}

	private function getWikiPageConfigProvider( string $providerKey ): WikiPageConfigProvider {
		$provider = $this->providerFactory->newProvider( $providerKey );
		if ( !$provider instanceof WikiPageConfigProvider ) {
			throw new LogicException(
				$providerKey . ' is expected to be a WikiPageConfigProvider'
			);
		}
		return $provider;
	}

	/**
	 * @inheritDoc
	 */
	public function get( $name ) {
		$map = $this->getVariableToProviderMap();
		if ( isset( $map[$name] ) ) {
			return $this->getWikiPageConfigProvider( $map[$name] )->get( $name );
		} else {
			return $this->fallbackConfig->get( $name );
		}
	}

	/**
	 * @inheritDoc
	 */
	public function has( $name ) {
		$map = $this->getVariableToProviderMap();
		if ( isset( $map[$name] ) ) {
			return $this->getWikiPageConfigProvider( $map[$name] )->has( $name );
		} else {
			return $this->fallbackConfig->has( $name );
		}
	}
}

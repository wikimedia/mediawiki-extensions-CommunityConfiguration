<?php

namespace MediaWiki\Extension\CommunityConfiguration\Tests;

use MediaWiki\Extension\CommunityConfiguration\CommunityConfigurationServices;
use MediaWiki\MediaWikiServices;

/**
 * Trait for helpers in tests that make use of CommunityConfiguration
 */
trait CommunityConfigurationTestHelpers {

	private array $overriddenProviders = [];

	/**
	 * Stub from MediaWikiIntegrationTestCase
	 *
	 * @see \MediaWikiIntegrationTestCase::overrideConfigValue()
	 * @param string $key
	 * @param mixed $value
	 */
	abstract protected function overrideConfigValue( string $key, $value );

	/**
	 * Stub from MediaWikiIntegrationTestCase
	 *
	 * @see \MediaWikiIntegrationTestCase::getServiceContainer()
	 * @return MediaWikiServices
	 */
	abstract protected function getServiceContainer();

	/**
	 * Override configuration for a provider
	 *
	 * @param mixed $newConfig
	 * @param string $providerId
	 * @return void
	 */
	public function overrideProviderConfig( $newConfig, string $providerId ): void {
		$providerSpec = CommunityConfigurationServices::wrap( $this->getServiceContainer() )
			->getConfigurationProviderFactory()
			->getProviderSpec( $providerId );

		$providerSpec['store'] = [
			'type' => 'static',
			'args' => [ $newConfig ],
		];
		$this->overriddenProviders[$providerId] = $providerSpec;
		$this->overrideConfigValue( 'CommunityConfigurationProviders', $this->overriddenProviders );
	}
}

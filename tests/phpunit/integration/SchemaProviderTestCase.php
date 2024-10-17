<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\CommunityConfiguration\Tests;

use MediaWiki\Extension\CommunityConfiguration\CommunityConfigurationServices;
use MediaWiki\Extension\CommunityConfiguration\Provider\IConfigurationProvider;
use MediaWikiIntegrationTestCase;

abstract class SchemaProviderTestCase extends MediaWikiIntegrationTestCase {

	/**
	 * If the provider can be toggled conditionally,
	 * then use the setUp method to explicitly enable it for this test class.
	 */
	abstract protected function getProviderId(): string;

	abstract protected function getExtensionName(): string;

	final public function testSchemaDefaultValues(): void {
		$provider = $this->getProvider();
		$ccServices = CommunityConfigurationServices::wrap( $this->getServiceContainer() );
		$updater = $ccServices->getEmergencyDefaultsUpdater();
		$pathBuilder = $ccServices->getEmergencyDefaultsPathBuilder();

		$actualDefaults = $updater->getEmergencyDefaultsForProvider( $provider );
		$path = $pathBuilder->getDefaultsFileForProvider( $provider, $this->getExtensionName() );

		// phpcs:ignore Generic.Files.LineLength
		$this->assertFileExists( $path, "Please generate the emergency defaults file $path with the CommunityConfiguration UpdateEmergencyDefaults maintenance script, and check it into your repository." );

		$storedEmergencyDefaults = require $path;
		$this->assertEquals( $storedEmergencyDefaults, $actualDefaults );
	}

	final protected function getProvider(): IConfigurationProvider {
		return CommunityConfigurationServices::wrap( $this->getServiceContainer() )
			->getConfigurationProviderFactory()
			->newProvider( $this->getProviderId() );
	}
}

<?php
declare( strict_types = 1 );

namespace MediaWiki\Extension\CommunityConfigurationExample\Tests\Integration;

use MediaWiki\Extension\CommunityConfiguration\CommunityConfigurationServices;
use MediaWiki\Extension\CommunityConfiguration\Provider\IVersionedConfigurationProvider;
use MediaWikiIntegrationTestCase;

/**
 * @group Database
 * @coversNothing
 */
class ExampleSchemaVersionsTest extends MediaWikiIntegrationTestCase {

	public function setUp(): void {
		parent::setUp();
		$this->markTestSkippedIfExtensionNotLoaded( 'CommunityConfigurationExample' );
	}

	public function testWriteOldConfig(): void {
		$pageStatus = $this->editPage( 'MediaWiki:CommunityConfigurationExample.json', '{
	"$version": "1.0.0",
	"CCExample_OnOff": "on"
}' );
		$this->assertStatusGood( $pageStatus );
	}

	/**
	 * The unconverted load returns the configuration exactly as it is stored, under the schema
	 * version it was stored with, while the converted load migrates it to the latest version.
	 */
	public function testLoadingOldConfigUnconverted(): void {
		$pageStatus = $this->editPage( 'MediaWiki:CommunityConfigurationExample.json', '{
	"$version": "1.0.0",
	"CCExample_OnOff": "on"
}' );
		$this->assertStatusGood( $pageStatus );

		$provider = CommunityConfigurationServices::wrap( $this->getServiceContainer() )
			->getConfigurationProviderFactory()
			->newProvider( 'CommunityConfigurationExample' );
		$this->assertInstanceOf( IVersionedConfigurationProvider::class, $provider );

		foreach ( [ 'loadValidConfigurationUnconverted', 'loadValidConfigurationUncachedUnconverted' ]
			as $method
		) {
			$status = $provider->$method();
			$this->assertStatusOK( $status, $method );
			$this->assertSame(
				'on',
				$status->getValue()->CCExample_OnOff,
				$method . ' must return the value as stored under schema 1.0.0, not as converted'
			);
		}

		$convertedStatus = $provider->loadValidConfigurationConvertedToLatest();
		$this->assertStatusOK( $convertedStatus );
		$this->assertTrue(
			$convertedStatus->getValue()->CCExample_OnOff,
			'the converted load must return the value as defined in the most recent schema'
		);
	}

	public function testLoadingOldConfig(): void {
		$pageStatus = $this->editPage( 'MediaWiki:CommunityConfigurationExample.json', '{
	"$version": "1.0.0",
	"CCExample_OnOff": "on"
}' );
		$this->assertStatusGood( $pageStatus );
		$configReader = $this->getServiceContainer()->getService( 'CommunityConfiguration.MediaWikiConfigReader' );

		$actualConfigValue = $configReader->get( 'CCExample_OnOff' );

		$this->assertSame(
			true,
			$actualConfigValue,
			// phpcs:ignore Generic.Files.LineLength.TooLong
			'loaded config value must be boolean `true` as defined in the most recent schema, not the orginally saved `\'on\'` string.'
		);
	}
}

<?php

namespace MediaWiki\Extension\CommunityConfiguration\Tests;

use MediaWiki\Extension\CommunityConfiguration\CommunityConfigurationServices;
use MediaWikiIntegrationTestCase;

/**
 * @covers \MediaWiki\Extension\CommunityConfiguration\EmergencyShutdown\EmergencyDefaultsUpdater
 */
class EmergencyDefaultsUpdaterIntegrationTest extends MediaWikiIntegrationTestCase {

	public function testSerializeDeserializeOK() {
		$this->overrideConfigValue( 'CommunityConfigurationProviders', [
			'foo' => [
				'store' => [
					'type' => 'wikipage',
					'args' => [ 'MediaWiki:Foo.json' ],
				],
				'validator' => [
					'type' => 'jsonschema',
					'args' => [
						JsonSchemaForTesting::class,
					],
				],
			],
		] );

		$ccServices = CommunityConfigurationServices::wrap( $this->getServiceContainer() );
		$providerFactory = $ccServices->getConfigurationProviderFactory();
		$updater = $ccServices->getEmergencyDefaultsUpdater();

		$provider = $providerFactory->newProvider( 'foo' );
		$tempFile = $this->getServiceContainer()->getTempFSFileFactory()->newTempFSFile(
			__CLASS__, 'php'
		);

		file_put_contents( $tempFile->getPath(), $updater->getSerializedDefaults( $provider ) );
		$this->assertEquals(
			(object)[
				'NumberWithDefault' => 0,
				'Mentors' => (object)[],
			],
			require_once $tempFile->getPath()
		);
	}
}

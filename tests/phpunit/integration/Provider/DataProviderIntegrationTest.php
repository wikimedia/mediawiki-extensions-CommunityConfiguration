<?php

namespace MediaWiki\Extension\CommunityConfiguration\Tests;

use MediaWiki\Extension\CommunityConfiguration\CommunityConfigurationServices;
use MediaWikiIntegrationTestCase;

/**
 * @covers \MediaWiki\Extension\CommunityConfiguration\Provider\DataProvider
 * @covers \MediaWiki\Extension\CommunityConfiguration\Provider\AbstractProvider
 * @group Database
 */
class DataProviderIntegrationTest extends MediaWikiIntegrationTestCase {

	private const PROVIDER_ID = 'foo';

	protected function setUp(): void {
		parent::setUp();

		$this->overrideConfigValue( 'CommunityConfigurationProviders', [
			self::PROVIDER_ID => [
				'store' => [
					'type' => 'wikipage',
					'args' => [ 'MediaWiki:Foo.json' ],
				],
				'validator' => [
					'type' => 'jsonschema',
					'args' => [ JsonSchemaForTesting::class ],
				],
				'type' => 'data',
			],
		] );
	}

	public function testLoadStoreLoad() {
		$authority = $this->getTestSysop()->getAuthority();
		$provider = CommunityConfigurationServices::wrap( $this->getServiceContainer() )
			->getConfigurationProviderFactory()
			->newProvider( self::PROVIDER_ID );

		// assert loadValidConfiguration() returns empty config initially
		$result = $provider->loadValidConfiguration();
		$this->assertStatusOK( $result );
		$this->assertStatusValue( (object)[ 'NumberWithDefault' => 0 ], $result );

		// assert storing valid configuration makes loadValidConfiguration to return it
		$storeStatus = $provider->storeValidConfiguration( (object)[ 'NumberWithDefault' => 42 ], $authority );
		$this->assertStatusOK( $storeStatus );

		$result = $provider->loadValidConfiguration();
		$this->assertStatusOK( $result );
		$this->assertStatusValue( (object)[
			'NumberWithDefault' => 42,
		], $result );

		// assert storing invalid config does not affect loadValidConfiguration()
		$storeStatus = $provider->storeValidConfiguration( (object)[ 'NumberWithDefault' => 'test' ], $authority );
		$this->assertStatusNotOK( $storeStatus );

		$result = $provider->loadValidConfiguration();
		$this->assertStatusOK( $result );
		$this->assertStatusValue( (object)[
			'NumberWithDefault' => 42,
		], $result );
	}

	public function testStoreNoPermissions() {
		$authority = $this->getTestUser()->getAuthority();
		$provider = CommunityConfigurationServices::wrap( $this->getServiceContainer() )
			->getConfigurationProviderFactory()
			->newProvider( self::PROVIDER_ID );

		$storeStatus = $provider->storeValidConfiguration( (object)[ 'NumberWithDefault' => 42 ], $authority );
		$this->assertStatusError( 'sitejsonprotected', $storeStatus );
	}

	public function testStoreBypassPermissions() {
		$authority = $this->getTestUser()->getAuthority();
		$provider = CommunityConfigurationServices::wrap( $this->getServiceContainer() )
			->getConfigurationProviderFactory()
			->newProvider( self::PROVIDER_ID );

		$storeStatus = $provider->alwaysStoreValidConfiguration( (object)[ 'NumberWithDefault' => 42 ], $authority );
		$this->assertStatusOK( $storeStatus );
	}
}

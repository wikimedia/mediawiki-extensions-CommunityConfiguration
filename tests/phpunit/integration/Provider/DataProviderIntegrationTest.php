<?php

namespace MediaWiki\Extension\CommunityConfiguration\Tests;

use MediaWiki\Extension\CommunityConfiguration\CommunityConfigurationServices;
use MediaWiki\Json\FormatJson;
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
		$this->assertStatusValue( (object)[
			'NumberWithDefault' => 0,
			'Mentors' => (object)[],
		], $result );

		// assert storing valid configuration makes loadValidConfiguration to return it
		$storeStatus = $provider->storeValidConfiguration( (object)[ 'NumberWithDefault' => 42 ], $authority );
		$this->assertStatusOK( $storeStatus );

		$result = $provider->loadValidConfiguration();
		$this->assertStatusOK( $result );
		$this->assertStatusValue( (object)[
			'NumberWithDefault' => 42,
			'Mentors' => (object)[],
		], $result );

		// assert storing invalid config does not affect loadValidConfiguration()
		$storeStatus = $provider->storeValidConfiguration( (object)[ 'NumberWithDefault' => 'test' ], $authority );
		$this->assertStatusNotOK( $storeStatus );

		$result = $provider->loadValidConfiguration();
		$this->assertStatusOK( $result );
		$this->assertStatusValue( (object)[
			'NumberWithDefault' => 42,
			'Mentors' => (object)[],
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

	public function testNormalizeStoredConfig(): void {
		$this->overrideConfigValue( 'CommunityConfigurationProviders', [] );
		$page = $this->getNonexistingTestPage( 'MediaWiki:Foo.json' );
		$pageSaveStatus = $this->editPage( $page, FormatJson::encode( (object)[
			'NumberWithDefault' => 23,
			'Mentors' => [],
		] ) );
		$this->assertStatusGood( $pageSaveStatus );
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
		$provider = CommunityConfigurationServices::wrap( $this->getServiceContainer() )
			->getConfigurationProviderFactory()
			->newProvider( self::PROVIDER_ID );
		$result = $provider->loadValidConfigurationUncached();
		$this->assertStatusOK( $result );
		$this->assertStatusValue( (object)[
			'NumberWithDefault' => 23,
			'Mentors' => (object)[],
		], $result );
	}

	public function testNormalizeConfigBeforeWriting(): void {
		$provider = CommunityConfigurationServices::wrap( $this->getServiceContainer() )
			->getConfigurationProviderFactory()
			->newProvider( self::PROVIDER_ID );
		$storeConfigStatus = $provider->storeValidConfiguration( (object)[
			'NumberWithDefault' => 24,
			'Mentors' => [],
		], $this->getTestSysop()->getAuthority() );
		$this->assertStatusGood( $storeConfigStatus );

		$loadConfigStatus = $provider->getStore()->loadConfigurationUncached();
		$this->assertStatusGood( $loadConfigStatus );
		$this->assertStatusValue( (object)[
			'NumberWithDefault' => 24,
			'Mentors' => (object)[],
		], $loadConfigStatus );
	}
}

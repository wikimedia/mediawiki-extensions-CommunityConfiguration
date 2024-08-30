<?php

namespace MediaWiki\Extension\CommunityConfiguration\Tests;

use MediaWiki\Extension\CommunityConfiguration\CommunityConfigurationServices;
use MediaWiki\Extension\CommunityConfiguration\Store\WikiPageStore;
use MediaWiki\Json\FormatJson;
use MediaWikiIntegrationTestCase;
use StatusValue;
use stdClass;

/**
 * @group Database
 * @covers \MediaWiki\Extension\CommunityConfiguration\Store\WikiPageStore
 */
class WikiPageStoreIntegrationTest extends MediaWikiIntegrationTestCase {
	private const CONFIG_PAGE_TITLE = 'MediaWiki:Foo.json';
	private const PROVIDER_ID = 'foo';

	protected function setUp(): void {
		parent::setUp();
		$this->overrideConfigValue( 'CommunityConfigurationProviders', [
			self::PROVIDER_ID => [
				'store' => [
					'type' => 'wikipage',
					'args' => [ self::CONFIG_PAGE_TITLE ],
				],
				'validator' => [
					'type' => 'noop',
				],
			],
		] );
	}

	public static function provideDataIsMutable() {
		return [
			'simple data' => [
				(object)[
					'a' => 1,
					'b' => 2,
				],
				static function ( StatusValue $statusValue ) {
					$data = $statusValue->getValue();
					unset( $data->b );
				},
			],
			'recursive data' => [
				(object)[
					'a' => 1,
					'b' => (object)[ 'a' => 1, 'b' => 2 ],
				],
				static function ( StatusValue $statusValue ) {
					$data = $statusValue->getValue();
					unset( $data->b->a );
				},
			],
			'StatusValue' => [
				(object)[
					'a' => 1,
					'b' => 2,
				],
				static function ( StatusValue $statusValue ) {
					$statusValue->setResult( false );
				},
			],
		];
	}

	/**
	 * @param stdClass $originalConfig
	 * @param callable $manipulateStatus
	 * @dataProvider provideDataIsMutable
	 */
	public function testDataIsMutableOK( stdClass $originalConfig, callable $manipulateStatus ) {
		$this->editPage( self::CONFIG_PAGE_TITLE, FormatJson::encode( $originalConfig ) );

		$store = CommunityConfigurationServices::wrap( $this->getServiceContainer() )
			->getConfigurationProviderFactory()
			->newProvider( self::PROVIDER_ID )
			->getStore();

		// assert loading works correctly
		$loaderStatus = $store->loadConfiguration();
		$this->assertStatusOK( $loaderStatus );
		$this->assertStatusValue(
			$originalConfig,
			$loaderStatus,
			'WikiPageStore does not return data correctly'
		);

		// manipulating $loaderStatus should not have side effects
		$manipulateStatus( $loaderStatus );

		// assert loading again produces the same result
		$loaderStatus = $store->loadConfiguration();
		$this->assertStatusOK( $loaderStatus );
		$this->assertStatusValue(
			$originalConfig,
			$loaderStatus,
			'WikiPageStore class allows callers to corrupt its cache on success'
		);
	}

	public function testStatusIsMutableFail() {
		$this->editPage( 'NotJsonContent', 'this is not JSON' );
		$this->overrideConfigValue( 'CommunityConfigurationProviders', [
			self::PROVIDER_ID => [
				'store' => [
					'type' => 'wikipage',
					'args' => [ 'NotJsonContent' ],
				],
				'validator' => [
					'type' => 'noop',
				],
			],
		] );

		$store = CommunityConfigurationServices::wrap( $this->getServiceContainer() )
			->getConfigurationProviderFactory()
			->newProvider( self::PROVIDER_ID )
			->getStore();

		// assert loading fails
		$status = $store->loadConfiguration();
		$this->assertStatusNotOK( $status );
		$this->assertStatusValue( null, $status );

		// manipulating the $status should not have any side effects
		$status->setResult( true, (object)[ 'a' => 42 ] );

		// assert loading produces the same result
		$status = $store->loadConfiguration();
		$this->assertStatusNotOK( $status );
		$this->assertStatusValue( null, $status );
	}

	public function testNonexistentPage() {
		// ensure CONFIG_PAGE_TITLE does not exist
		$this->getNonexistingTestPage( self::CONFIG_PAGE_TITLE );
		$store = CommunityConfigurationServices::wrap( $this->getServiceContainer() )
			->getConfigurationProviderFactory()
			->newProvider( self::PROVIDER_ID )
			->getStore();

		$status = $store->loadConfiguration();
		$this->assertStatusOK( $status );
		$this->assertStatusValue( (object)[], $status );
	}

	public function testNoVersion() {
		$this->editPage( self::CONFIG_PAGE_TITLE, FormatJson::encode( [
			'Foo' => 42,
		] ) );

		$store = CommunityConfigurationServices::wrap( $this->getServiceContainer() )
			->getConfigurationProviderFactory()
			->newProvider( self::PROVIDER_ID )
			->getStore();

		$this->assertNull( $store->getVersion() );
	}

	public function testGetVersionAfterLoad() {
		$this->editPage( self::CONFIG_PAGE_TITLE, FormatJson::encode( [
			'Foo' => 42,
			WikiPageStore::VERSION_FIELD_NAME => '2.0.0',
		] ) );

		$provider = CommunityConfigurationServices::wrap( $this->getServiceContainer() )
			->getConfigurationProviderFactory()
			->newProvider( self::PROVIDER_ID );
		$store = $provider->getStore();

		// First, load the configuration itself...
		$loadStatus = $store->loadConfiguration();
		$this->assertStatusOK( $loadStatus );
		$this->assertStatusValue( (object)[ 'Foo' => 42 ], $loadStatus );

		// ...then, load the version, which should still work.
		$this->assertSame( '2.0.0', $store->getVersion() );
	}

	public function testNonObject() {
		$provider = CommunityConfigurationServices::wrap( $this->getServiceContainer() )
			->getConfigurationProviderFactory()
			->newProvider( self::PROVIDER_ID );
		$store = $provider->getStore();

		$this->assertStatusOK( $store->storeConfiguration(
			[ 'Foo' => 42 ],
			null,
			$this->getTestSysop()->getAuthority()
		) );

		$loadStatus = $store->loadConfiguration();
		$this->assertStatusOK( $loadStatus );
		$this->assertStatusValue( (object)[ 'Foo' => 42 ], $loadStatus );
	}

	public function testNoPermissions() {
		$provider = CommunityConfigurationServices::wrap( $this->getServiceContainer() )
			->getConfigurationProviderFactory()
			->newProvider( self::PROVIDER_ID );
		$store = $provider->getStore();

		$status = $store->storeConfiguration(
			[ 'Foo' => 42 ],
			null,
			$this->getTestUser()->getAuthority()
		);
		$this->assertStatusError( 'sitejsonprotected', $status );
	}

	public function testPermissionBypass() {
		$provider = CommunityConfigurationServices::wrap( $this->getServiceContainer() )
			->getConfigurationProviderFactory()
			->newProvider( self::PROVIDER_ID );
		$store = $provider->getStore();

		$this->assertStatusOK( $store->alwaysStoreConfiguration(
			[ 'Foo' => 42 ],
			null,
			$this->getTestUser()->getAuthority()
		) );
	}
}

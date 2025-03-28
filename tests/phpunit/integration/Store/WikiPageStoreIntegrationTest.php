<?php

declare( strict_types = 1 );

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

	public static function provideDataIsMutable(): iterable {
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
	public function testDataIsMutableOK( stdClass $originalConfig, callable $manipulateStatus ): void {
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

	public function testStatusIsMutableFail(): void {
		if ( $this->getDefaultWikitextNS() !== NS_MAIN ) {
			$this->markTestSkipped( 'NS_MAIN is non-wikitext, skipping.' );
		}

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

	public function testNonexistentPage(): void {
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

	public function testNoVersion(): void {
		$this->editPage( self::CONFIG_PAGE_TITLE, FormatJson::encode( [
			'Foo' => 42,
		] ) );

		$store = CommunityConfigurationServices::wrap( $this->getServiceContainer() )
			->getConfigurationProviderFactory()
			->newProvider( self::PROVIDER_ID )
			->getStore();

		$this->assertNull( $store->getVersion() );
	}

	/**
	 * A page whose version field cannot be a version fails the load with a readable error
	 * instead of a TypeError, on a real revision.
	 *
	 * JsonValidateSave would normally reject such a page on save, so it is cleared here. That
	 * is not an artificial setup: XML import inserts revisions through RevisionStore directly,
	 * bypassing PageUpdater and therefore the hook, and a version field can also become
	 * unreadable long after it was saved.
	 */
	public function testLoadVersionedConfigurationWithMalformedVersion(): void {
		$this->clearHook( 'JsonValidateSave' );
		$this->editPage( self::CONFIG_PAGE_TITLE, FormatJson::encode( [
			'Foo' => 42,
			WikiPageStore::VERSION_FIELD_NAME => 1.1,
		] ) );

		$store = CommunityConfigurationServices::wrap( $this->getServiceContainer() )
			->getConfigurationProviderFactory()
			->newProvider( self::PROVIDER_ID )
			->getStore();

		foreach ( [ 'loadVersionedConfiguration', 'loadVersionedConfigurationUncached' ] as $method ) {
			$this->assertStatusError(
				'communityconfiguration-malformed-schema-version',
				$store->$method(),
				$method
			);
		}
	}

	public function testLoadVersionedConfiguration(): void {
		$this->editPage( self::CONFIG_PAGE_TITLE, FormatJson::encode( [
			'Foo' => 42,
			WikiPageStore::VERSION_FIELD_NAME => '2.0.0',
		] ) );

		$store = CommunityConfigurationServices::wrap( $this->getServiceContainer() )
			->getConfigurationProviderFactory()
			->newProvider( self::PROVIDER_ID )
			->getStore();

		foreach ( [ 'loadVersionedConfiguration', 'loadVersionedConfigurationUncached' ] as $method ) {
			$status = $store->$method();
			$this->assertStatusOK( $status, $method );
			$versionedConfiguration = $status->getValue();
			$this->assertEquals( (object)[ 'Foo' => 42 ], $versionedConfiguration->getData(), $method );
			$this->assertSame( '2.0.0', $versionedConfiguration->getVersion(), $method );
		}
	}

	/**
	 * An uncached load reports the data and the version of one and the same revision
	 *
	 * Obtaining the two through separate calls -- loadConfigurationUncached() for the data and
	 * getVersion() for the version, say -- would pair the new revision's data with the version
	 * still held in the caches primed below. Loading them together cannot.
	 *
	 * Note the cached load is deliberately not asserted on here: serving the primed revision
	 * for a while after an edit is expected (see WikiPageStoreEventIngressTest), and doing so
	 * does not break the pairing, as both halves then describe that older revision.
	 */
	public function testUncachedVersionedLoadSeesOneRevisionAsAWhole(): void {
		$this->editPage( self::CONFIG_PAGE_TITLE, FormatJson::encode( [
			'Foo' => 42,
			WikiPageStore::VERSION_FIELD_NAME => '1.0.0',
		] ) );

		$store = CommunityConfigurationServices::wrap( $this->getServiceContainer() )
			->getConfigurationProviderFactory()
			->newProvider( self::PROVIDER_ID )
			->getStore();

		// prime both the in-process and the WAN cache with the first revision
		$status = $store->loadVersionedConfiguration();
		$this->assertStatusOK( $status );
		$this->assertSame( '1.0.0', $status->getValue()->getVersion() );

		$this->editPage( self::CONFIG_PAGE_TITLE, FormatJson::encode( [
			'Foo' => 43,
			WikiPageStore::VERSION_FIELD_NAME => '2.0.0',
		] ) );

		$status = $store->loadVersionedConfigurationUncached();
		$this->assertStatusOK( $status );
		$versionedConfiguration = $status->getValue();
		$this->assertEquals( (object)[ 'Foo' => 43 ], $versionedConfiguration->getData() );
		$this->assertSame( '2.0.0', $versionedConfiguration->getVersion() );
	}

	public function testGetVersionAfterLoad(): void {
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

	public function testNonObject(): void {
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

	public function testNoPermissions(): void {
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

	public function testPermissionBypass(): void {
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

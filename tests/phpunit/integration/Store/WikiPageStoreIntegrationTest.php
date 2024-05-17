<?php

namespace MediaWiki\Extension\CommunityConfiguration\Tests;

use FormatJson;
use MediaWiki\Extension\CommunityConfiguration\CommunityConfigurationServices;
use MediaWiki\Extension\CommunityConfiguration\Store\WikiPageStore;
use MediaWikiIntegrationTestCase;

/**
 * @covers \MediaWiki\Extension\CommunityConfiguration\Store\WikiPageStore
 * @group Database
 */
class WikiPageStoreIntegrationTest extends MediaWikiIntegrationTestCase {

	private const PROVIDER_ID = 'foo';
	private const PAGE_TITLE = 'MediaWiki:Foo.json';

	protected function setUp(): void {
		parent::setUp();

		$this->setMwGlobals( [
			'wgCommunityConfigurationProviders' => [
				self::PROVIDER_ID => [
					'store' => [
						'type' => 'wikipage',
						'args' => [ self::PAGE_TITLE ],
					],
					'validator' => [
						'type' => 'noop',
					]
				],
			]
		] );
	}

	public function testNoVersion() {
		$this->editPage( self::PAGE_TITLE, FormatJson::encode( [
			'Foo' => 42,
		] ) );

		$store = CommunityConfigurationServices::wrap( $this->getServiceContainer() )
			->getConfigurationProviderFactory()
			->newProvider( self::PROVIDER_ID )
			->getStore();

		$this->assertNull( $store->getVersion() );
	}

	public function testGetVersionAfterLoad() {
		$this->editPage( self::PAGE_TITLE, FormatJson::encode( [
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
}

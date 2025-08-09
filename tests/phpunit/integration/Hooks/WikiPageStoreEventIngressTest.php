<?php

namespace MediaWiki\Extension\CommunityConfiguration\Tests;

use MediaWiki\Extension\CommunityConfiguration\CommunityConfigurationServices;
use MediaWiki\Json\FormatJson;
use MediaWiki\Title\Title;
use MediaWikiIntegrationTestCase;

/**
 * @covers \MediaWiki\Extension\CommunityConfiguration\Hooks\WikiPageStoreEventIngress
 * @group Database
 */
class WikiPageStoreEventIngressTest extends MediaWikiIntegrationTestCase {

	protected function setUp(): void {
		parent::setUp();

		$this->overrideConfigValue( 'CommunityConfigurationProviders', [
			'foo' => [
				'store' => [
					'type' => 'wikipage',
					'args' => [ 'MediaWiki:Foo.json' ],
				],
				'validator' => [
					'type' => 'noop',
				],
			],
		] );
	}

	public function testManualSaveInvalidates() {
		$wanCache = $this->getServiceContainer()->getMainWANObjectCache();

		$provider = CommunityConfigurationServices::wrap( $this->getServiceContainer() )
			->getConfigurationProviderFactory()
			->newProvider( 'foo' );

		$mockWallClock = 1549343530.0;
		$wanCache->setMockTime( $mockWallClock );

		$this->assertStatusOK( $this->editPage( 'MediaWiki:Foo.json', FormatJson::encode( [
			'Number' => 42,
		] ) ), 'Failed to create MediaWiki:Foo.json' );
		$status = $provider->loadValidConfiguration();
		$this->assertStatusOK( $status );
		$this->assertStatusValue( (object)[
			'Number' => 42,
		], $status );

		$mockWallClock += 1;
		$this->assertStatusOK( $this->editPage( 'MediaWiki:Foo.json', FormatJson::encode( [
			'Number' => 43,
		] ) ), 'Failed to edit MediaWiki:Foo.json' );
		$status = $provider->loadValidConfiguration();
		$this->assertStatusOK( $status, 'Failed to load configuration' );
		$this->assertStatusValue( (object)[
			'Number' => 43,
		], $status, 'Failed to invalidate cache' );
	}

	public function testPageDeletion() {
		// This is necessary to ensure loadValidConfiguration at the end of the test does not
		// treat the cache key as volatile and sees the effect of deletePage and cache invalidation.
		$mockWallClock = 1549343530.0;
		$this->getServiceContainer()->getMainWANObjectCache()->setMockTime( $mockWallClock );

		$provider = CommunityConfigurationServices::wrap( $this->getServiceContainer() )
			->getConfigurationProviderFactory()
			->newProvider( 'foo' );
		$provider->storeValidConfiguration(
			(object)[ 'Foo' => 42 ],
			$this->getTestSysop()->getAuthority()
		);

		// 1. Ensure the cache is populated...
		$this->assertStatusValue(
			(object)[ 'Foo' => 42 ],
			$provider->loadValidConfiguration()
		);

		// 2. ...and that deletion invalidated it.
		$this->deletePage( Title::newFromText( 'MediaWiki:Foo.json' ) );
		$mockWallClock += 10;
		$this->assertStatusValue(
			(object)[],
			$provider->loadValidConfiguration()
		);
	}
}

<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\CommunityConfiguration\Tests;

use MediaWiki\Extension\CommunityConfiguration\CommunityConfigurationServices;
use MediaWiki\Extension\CommunityConfiguration\Provider\WikiPageConfigProvider;
use MediaWiki\Extension\CommunityConfiguration\Store\WikiPageStore;
use MediaWiki\Tests\Unit\FakeQqxMessageLocalizer;
use MediaWiki\Title\Title;
use MediaWikiIntegrationTestCase;
use WikiPage;

/**
 * @covers \MediaWiki\Extension\CommunityConfiguration\Provider\WikiPageConfigProvider
 * @group Database
 */
class WikiPageConfigProviderIntegrationTest extends MediaWikiIntegrationTestCase {
	private const PROVIDER_ID = 'CommunityFeatureOverrides';
	private const CONFIG_PAGE_TITLE = 'MediaWiki:CommunityFeatureOverrides.json';

	protected function setUp(): void {
		parent::setUp();

		$this->setMwGlobals( [
			'wgCommunityConfigurationProviders' => [
				self::PROVIDER_ID => [
					'store' => [
						'type' => 'wikipage',
						'args' => [ self::CONFIG_PAGE_TITLE ],
					],
					'validator' => [
						'type' => 'jsonschema',
						'args' => [ JsonConfigSchemaForTesting::class ],
					],
					'type' => 'mw-config',
				],
			],
		] );
	}

	public function testWikiPageIntegration(): void {
		$provider = CommunityConfigurationServices::wrap( $this->getServiceContainer() )
			->getConfigurationProviderFactory()
			->newProvider( self::PROVIDER_ID );
		$this->assertInstanceOf( WikiPageConfigProvider::class, $provider );

		$this->assertSame( self::PROVIDER_ID, $provider->getId() );
		$this->assertSame(
			'(communityconfiguration-communityfeatureoverrides-title)',
			$provider->getName( new FakeQqxMessageLocalizer() )->plain()
		);

		$configPage = $this->getMaybeExistingPage( self::CONFIG_PAGE_TITLE );
		$this->assertFalse( $configPage->exists() );

		$sysopAuthority = $this->getTestSysop()->getAuthority();
		$customRegex = '.*';
		$provider->storeValidConfiguration(
			(object)[
				'FeatureEnabled' => true,
				'FeatureActivationRegex' => $customRegex,
			],
			$sysopAuthority
		);

		$configPage->clear();
		$this->assertTrue( $configPage->exists() );

		$this->assertEquals( (object)[
			'FeatureEnabled' => true,
			'FeatureActivationRegex' => $customRegex,
			WikiPageStore::VERSION_FIELD_NAME => '1.0.0',
		], $configPage->getContent()->getData()->value );
	}

	public function testAccessingDefaultConfig(): void {
		$provider = CommunityConfigurationServices::wrap( $this->getServiceContainer() )
			->getConfigurationProviderFactory()
			->newProvider( self::PROVIDER_ID );
		if ( !( $provider instanceof WikiPageConfigProvider ) ) {
			$this->fail( 'Provider should be an instance of WikiPageConfigProvider' );
		}

		$this->assertTrue(
			$provider->has( 'FeatureEnabled' ),
			'FeatureEnabled is not set, but has a default, so `->has()` should return true'
		);
		$this->assertFalse( $provider->get( 'FeatureEnabled' ), 'Default value is used when the key is not set' );

		$this->assertFalse(
			$provider->has( 'FeatureActivationRegex' ),
			'FeatureActivationRegex is not set and has no default, so `->has()` should return false'
		);

		$this->assertSame(
			[
				'FeatureEnabled',
				'FeatureActivationRegex',
			],
			$provider->getSupportedConfigVariableNames(),
			'all supported config variables should be returned, even if unset without a default'
		);
	}

	public function testAccessingCustomConfig(): void {
		$provider = CommunityConfigurationServices::wrap( $this->getServiceContainer() )
			->getConfigurationProviderFactory()
			->newProvider( self::PROVIDER_ID );
		if ( !( $provider instanceof WikiPageConfigProvider ) ) {
			$this->fail( 'Provider should be an instance of WikiPageConfigProvider' );
		}

		$sysopAuthority = $this->getTestSysop()->getAuthority();
		$customRegex = '.*';
		$provider->storeValidConfiguration(
			(object)[
				'FeatureEnabled' => true,
				'FeatureActivationRegex' => $customRegex,
			],
			$sysopAuthority
		);

		$this->assertTrue(
			$provider->get( 'FeatureEnabled' ),
			'Overridden value should be returned when it exists, not the default'
		);
		$this->assertTrue(
			$provider->has( 'FeatureActivationRegex' ),
			'FeatureActivationRegex is now set, so `->has()` should return true'
		);
		$this->assertSame( $customRegex, $provider->get( 'FeatureActivationRegex' ) );
	}

	private function getMaybeExistingPage( $title ): WikiPage {
		if ( !$title instanceof Title ) {
			$title = Title::newFromText( $title );
		}
		return $this->getServiceContainer()->getWikiPageFactory()->newFromTitle( $title );
	}

}

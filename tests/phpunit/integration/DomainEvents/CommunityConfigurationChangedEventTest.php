<?php

namespace MediaWiki\Extension\CommunityConfiguration\Tests;

use MediaWiki\Extension\CommunityConfiguration\CommunityConfigurationServices;
use MediaWiki\Extension\CommunityConfiguration\DomainEvents\CommunityConfigurationChangedEvent;
use MediaWiki\Tests\ExpectCallbackTrait;
use MediaWikiIntegrationTestCase;

/**
 * @group Database
 * @covers \MediaWiki\Extension\CommunityConfiguration\DomainEvents\CommunityConfigurationChangedEvent
 */
class CommunityConfigurationChangedEventTest extends MediaWikiIntegrationTestCase {
	use ExpectCallbackTrait;

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

	public function testDomainEventOnCCEdit() {
		$authority = $this->getTestSysop()->getAuthority();
		$provider = CommunityConfigurationServices::wrap( $this->getServiceContainer() )
			->getConfigurationProviderFactory()
			->newProvider( self::PROVIDER_ID );

		$this->expectDomainEvent(
			CommunityConfigurationChangedEvent::TYPE, 1,
			function ( CommunityConfigurationChangedEvent $event ) use ( $provider ) {
				$this->assertSame( $provider, $event->getProvider() );
			}
		);
		$provider->storeValidConfiguration( [ 'NumberWithDefault' => 10 ], $authority );
	}

	public function testNoDomainEventOnRegularEdit() {
		$authority = $this->getTestSysop()->getAuthority();

		// editing a regular page should NOT fire the CC event
		$this->expectDomainEvent( CommunityConfigurationChangedEvent::TYPE, 0 );
		$this->editPage( 'Sandbox', 'foo 123', $authority );
	}

	public function testNoDomainEventOnManualEdit() {
		$authority = $this->getTestSysop()->getAuthority();

		// manually editing the CC page should NOT fire any event
		$this->expectDomainEvent( CommunityConfigurationChangedEvent::TYPE, 0 );
		$this->editPage( 'MediaWiki:Foo.json', '{}', $authority );
	}
}

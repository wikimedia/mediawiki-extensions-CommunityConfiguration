<?php

namespace MediaWiki\Extension\CommunityConfiguration\Tests;

use MediaWiki\Extension\CommunityConfiguration\CommunityConfigurationServices;
use MediaWiki\MainConfigNames;
use MediaWikiIntegrationTestCase;

/**
 * @covers \MediaWiki\Extension\CommunityConfiguration\Access\MediaWikiConfigRouter
 */
class MediaWikiConfigRouterIntegrationTest extends MediaWikiIntegrationTestCase {

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
				'type' => 'mw-config',
			],
		] );
	}

	public function testGetFromCommunityConfig() {
		$this->assertSame(
			0,
			CommunityConfigurationServices::wrap( $this->getServiceContainer() )
				->getMediaWikiConfigRouter()
				->get( 'NumberWithDefault' )
		);
	}

	public function testGetFromMainConfig() {
		$this->assertSame(
			$this->getServiceContainer()->getMainConfig()->get( MainConfigNames::DBname ),
			CommunityConfigurationServices::wrap( $this->getServiceContainer() )
				->getMediaWikiConfigRouter()
				->get( MainConfigNames::DBname )
		);
	}
}

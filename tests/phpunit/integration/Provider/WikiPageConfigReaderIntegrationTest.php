<?php

namespace MediaWiki\Extension\CommunityConfiguration\Tests;

use MediaWiki\Extension\CommunityConfiguration\CommunityConfigurationServices;
use MediaWikiIntegrationTestCase;
use stdClass;

/**
 * @covers \MediaWiki\Extension\CommunityConfiguration\Access\WikiPageConfigReader
 * @group Database
 */
class WikiPageConfigReaderIntegrationTest extends MediaWikiIntegrationTestCase {

	private const PROVIDER_ID = 'foo';

	protected function setUp(): void {
		parent::setUp();

		$this->setMwGlobals( [
			'wgCommunityConfigurationProviders' => [
				self::PROVIDER_ID => [
					'store' => [
						'type' => 'wikipage',
						'args' => [ 'MediaWiki:Foo.json' ],
					],
					'validator' => [
						'type' => 'jsonschema',
						'args' => [ JsonSchemaForTesting::class ]
					],
					'type' => 'mw-config',
				],
			],
		] );
	}

	public function testStoreGet() {
		$authority = $this->getTestSysop()->getAuthority();

		$ccServices = CommunityConfigurationServices::wrap( $this->getServiceContainer() );
		$provider = $ccServices->getConfigurationProviderFactory()
			->newProvider( self::PROVIDER_ID );
		$reader = $ccServices->getWikiPageConfigReader();

		// when nothing is configured, defaults are returned (and if there are no defaults,
		// the variable is omitted).
		$this->assertFalse( $reader->has( 'Foo' ) );
		$this->assertTrue( $reader->has( 'NumberWithDefault' ) );
		$this->assertSame( 0, $reader->get( 'NumberWithDefault' ) );

		// after changing the value, the new ones are returned
		$config = new stdClass();
		$config->Foo = 21;
		$config->NumberWithDefault = 42;
		$provider->storeValidConfiguration( $config, $authority );

		$this->assertTrue( $reader->has( 'Foo' ) );
		$this->assertSame( 21, $reader->get( 'Foo' ) );
		$this->assertTrue( $reader->has( 'NumberWithDefault' ) );
		$this->assertSame( 42, $reader->get( 'NumberWithDefault' ) );
	}
}

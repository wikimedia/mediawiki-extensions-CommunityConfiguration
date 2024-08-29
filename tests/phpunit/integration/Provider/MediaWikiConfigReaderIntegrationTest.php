<?php

namespace MediaWiki\Extension\CommunityConfiguration\Tests;

use MediaWiki\Config\ConfigException;
use MediaWiki\Extension\CommunityConfiguration\CommunityConfigurationServices;
use MediaWiki\MainConfigNames;
use MediaWikiIntegrationTestCase;
use stdClass;

/**
 * @covers \MediaWiki\Extension\CommunityConfiguration\Access\MediaWikiConfigReader
 * @group Database
 */
class MediaWikiConfigReaderIntegrationTest extends MediaWikiIntegrationTestCase {

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

	public function testStoreGet() {
		$authority = $this->getTestSysop()->getAuthority();

		$ccServices = CommunityConfigurationServices::wrap( $this->getServiceContainer() );
		$provider = $ccServices->getConfigurationProviderFactory()
			->newProvider( self::PROVIDER_ID );
		$reader = $ccServices->getMediaWikiConfigReader();

		// when nothing is configured, defaults are returned (and if there are no defaults,
		// the variable is omitted).
		$this->assertFalse( $reader->has( 'Number' ) );
		$this->assertTrue( $reader->has( 'NumberWithDefault' ) );
		$this->assertSame( 0, $reader->get( 'NumberWithDefault' ) );

		// after changing the value, the new ones are returned
		$config = new stdClass();
		$config->NumberWithDefault = 42;
		$provider->storeValidConfiguration( $config, $authority );

		$this->assertTrue( $reader->has( 'NumberWithDefault' ) );
		$this->assertSame( 42, $reader->get( 'NumberWithDefault' ) );
	}

	public function testCoreVariable() {
		$ccServices = CommunityConfigurationServices::wrap( $this->getServiceContainer() );
		$reader = $ccServices->getMediaWikiConfigReader();

		// variable that is not in the schema is processed by GlobalVarConfig, which should have
		// DBname for example
		$this->assertTrue( $reader->has( MainConfigNames::DBname ) );
		$this->assertSame(
			$this->getDb()->getDBname(),
			$reader->get( MainConfigNames::DBname )
		);
	}

	public function testMultipleRegistration() {
		$this->overrideConfigValue( 'CommunityConfigurationProviders', [
			'foo' => [
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
			'bar' => [
				'store' => [
					'type' => 'wikipage',
					'args' => [ 'MediaWiki:Bar.json' ],
				],
				'validator' => [
					'type' => 'jsonschema',
					'args' => [ JsonSchemaForTesting::class ],
				],
				'type' => 'mw-config',
			],
		] );

		$this->expectException( ConfigException::class );
		$this->expectExceptionMessage( 'is registered by multiple CommunityConfiguration providers' );

		CommunityConfigurationServices::wrap( $this->getServiceContainer() )
			->getMediaWikiConfigReader()
			->get( 'Number' );
	}
}

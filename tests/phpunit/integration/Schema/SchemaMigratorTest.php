<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\CommunityConfiguration\Tests;

use MediaWiki\Extension\CommunityConfiguration\CommunityConfigurationServices;
use MediaWiki\Json\FormatJson;
use MediaWikiIntegrationTestCase;
use stdClass;

/**
 * @group Database
 * @covers \MediaWiki\Extension\CommunityConfiguration\Schema\SchemaMigrator
 */
class SchemaMigratorTest extends MediaWikiIntegrationTestCase {

	private const PROVIDER_ID = 'foo';
	private const CONFIG_PAGE_TITLE = 'MediaWiki:Foo.json';

	protected function setUp(): void {
		parent::setUp();

		$this->overrideConfigValue( 'CommunityConfigurationProviders', [
			self::PROVIDER_ID => [
				'store' => [
					'type' => 'wikipage',
					'args' => [ self::CONFIG_PAGE_TITLE ],
				],
				'validator' => [
					'type' => 'jsonschema',
					'args' => [ JsonSchemaForTesting::class ],
				],
				'type' => 'mw-config',
			],
		] );
	}

	/**
	 * @param stdClass $originalConfig
	 * @dataProvider provideDataConvertDataToVersion
	 */
	public function testConvertDataToVersion( stdClass $originalConfig ) {
		$this->editPage( self::CONFIG_PAGE_TITLE, FormatJson::encode( $originalConfig ) );
		$ccServices = CommunityConfigurationServices::wrap( $this->getServiceContainer() );
		$provider = $ccServices->getConfigurationProviderFactory()
			->newProvider( self::PROVIDER_ID );
		$schemaMigrator = $ccServices->getSchemaMigrator();
		$conversionStatus = $schemaMigrator->convertDataToVersion(
			$provider,
			$provider->getValidator()->getSchemaVersion()
		);
		$this->assertStatusGood( $conversionStatus );
	}

	public static function provideDataConvertDataToVersion(): array {
		return [
			'simple data' => [
				(object)[
					'$version' => '1.0.0',
					'NumberWithDefault' => 123,
				],
			],
		];
	}

}

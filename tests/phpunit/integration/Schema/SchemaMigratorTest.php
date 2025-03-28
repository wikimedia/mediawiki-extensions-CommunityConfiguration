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
		$conversionStatus = $schemaMigrator->loadAndConvertProviderDataToVersion(
			$provider,
			$provider->getValidator()->getSchemaVersion()
		);
		$this->assertStatusGood( $conversionStatus );
	}

	/**
	 * A conversion that cannot be carried out must come back as a fatal StatusValue, rather
	 * than letting the LogicException or ReflectionException escape to the caller.
	 *
	 * @dataProvider provideUnconvertibleVersions
	 */
	public function testConvertDataToVersionReportsFailureAsStatus(
		string $currentVersion,
		string $targetVersion,
		string $expectedMessage
	) {
		$ccServices = CommunityConfigurationServices::wrap( $this->getServiceContainer() );
		$provider = $ccServices->getConfigurationProviderFactory()
			->newProvider( self::PROVIDER_ID );

		$conversionStatus = $ccServices->getSchemaMigrator()->convertDataToVersion(
			$provider->getValidator(),
			(object)[ 'NumberWithDefault' => 123 ],
			$currentVersion,
			$targetVersion
		);

		// SchemaMigrator reports the exception through a RawMessage, which presents itself as
		// the 'rawmessage' message with the text as its sole parameter.
		$this->assertStatusError( 'rawmessage', $conversionStatus );
		$this->assertSame(
			[ $expectedMessage ],
			$conversionStatus->getMessages()[0]->getParams()
		);
	}

	public static function provideUnconvertibleVersions(): array {
		// As built by JsonSchemaReader::getSchemaId()
		$schemaId = str_replace( '\\', '/', JsonSchemaForTesting::class )
			. '/' . JsonSchemaForTesting::VERSION;
		// As built by JsonSchemaVersionManager::getVersionForSchema()
		$missingVersionClass = __NAMESPACE__ . '\\Migrations\\JsonSchemaForTesting_0_9_0';

		return [
			// ReflectionException: there is no Schema class for the version to convert from
			'unknown version to convert from' => [
				'0.9.0',
				JsonSchemaForTesting::VERSION,
				'Class "' . $missingVersionClass . '" does not exist',
			],
			// LogicException: JsonSchemaForTesting declares no SCHEMA_NEXT_VERSION
			'no newer version linked' => [
				JsonSchemaForTesting::VERSION,
				'1.0.1',
				$schemaId . ' does not have a next/previous version linked.',
			],
			// LogicException: JsonSchemaForTesting declares no SCHEMA_PREVIOUS_VERSION
			'no older version linked' => [
				JsonSchemaForTesting::VERSION,
				'0.9.9',
				$schemaId . ' does not have a next/previous version linked.',
			],
		];
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

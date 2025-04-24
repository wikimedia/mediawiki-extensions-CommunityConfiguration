<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\CommunityConfiguration\Tests;

use MediaWiki\Extension\CommunityConfiguration\CommunityConfigurationServices;
use MediaWiki\Extension\CommunityConfiguration\Provider\IConfigurationProvider;
use MediaWiki\Extension\CommunityConfiguration\Schema\ISchemaConverter;
use MediaWikiIntegrationTestCase;

abstract class SchemaProviderTestCase extends MediaWikiIntegrationTestCase {

	/**
	 * If the provider can be toggled conditionally,
	 * then use the setUp method to explicitly enable it for this test class.
	 */
	abstract protected function getProviderId(): string;

	abstract protected function getExtensionName(): string;

	final public function testSchemaDefaultValues(): void {
		$provider = $this->getProvider();
		$ccServices = CommunityConfigurationServices::wrap( $this->getServiceContainer() );
		$updater = $ccServices->getEmergencyDefaultsUpdater();
		$pathBuilder = $ccServices->getEmergencyDefaultsPathBuilder();

		$actualDefaults = $updater->getEmergencyDefaultsForProvider( $provider );
		$path = $pathBuilder->getDefaultsFileForProvider( $provider, $this->getExtensionName() );

		// phpcs:ignore Generic.Files.LineLength
		$this->assertFileExists( $path, "Please generate the emergency defaults file $path with the CommunityConfiguration UpdateEmergencyDefaults maintenance script, and check it into your repository." );

		$storedEmergencyDefaults = require $path;
		$this->assertEquals( $storedEmergencyDefaults, $actualDefaults );
	}

	final public function testAssertEveryToplevelPropertyHasDefault(): void {
		$defaults = $this->getProvider()->getValidator()->getSchemaBuilder()->getDefaultsMap();
		$rootProperties = $this->getProvider()->getValidator()->getSchemaBuilder()->getRootProperties();
		foreach ( $rootProperties as $propertyName => $propertySchema ) {
			$propertyDefaultValue = $defaults->$propertyName;
			if ( $propertyDefaultValue === null && !$this->isNullAPlausibleSchemaValue( $propertySchema ) ) {
				$this->fail( "Missing default value for $propertyName" );
			}
		}
		// everything seems alright
		$this->addToAssertionCount( 1 );
	}

	private function isNullAPlausibleSchemaValue( array $propSchema ): bool {
		if ( $propSchema['type'] === 'null' ) {
			return true;
		}
		if ( is_array( $propSchema['type'] ) && in_array( 'null', $propSchema['type'], true ) ) {
			return true;
		}
		return false;
	}

	final public function testSchemaHasVersion(): void {
		$schema = $this->getProvider()->getValidator()->getSchemaBuilder()->getSchemaReader();
		$version = $schema->getVersion();

		$this->assertNotNull( $version, 'Schema ' . $schema->getSchemaId() . ' must have a VERSION constant' );
	}

	final public function testSchemaHasConvertersAndMigrations(): void {
		$schemaBuilder = $this->getProvider()->getValidator()->getSchemaBuilder();

		$schema = $schemaBuilder->getSchemaReader();
		$prevVersion = $schema->getPreviousVersion();
		$converterId = $schema->getSchemaConverterId();
		while ( $prevVersion ) {
			$this->assertNotNull(
				$converterId,
				'Schema ' . $schema->getSchemaId() . ' must have a SCHEMA_CONVERTER constant'
			);
			$res = $this->getServiceContainer()->getObjectFactory()->createObject( [
				'class' => $converterId,
			] );

			$this->assertInstanceOf( ISchemaConverter::class, $res );

			$schema = $schemaBuilder->getVersionManager()->getVersionForSchema( $prevVersion );
			$prevVersion = $schema->getPreviousVersion();
			$converterId = $schema->getSchemaConverterId();
		}
		$this->assertNull( $converterId, 'The initial version of a schema must not have a converter.' );
	}

	final protected function getProvider(): IConfigurationProvider {
		return CommunityConfigurationServices::wrap( $this->getServiceContainer() )
			->getConfigurationProviderFactory()
			->newProvider( $this->getProviderId() );
	}
}

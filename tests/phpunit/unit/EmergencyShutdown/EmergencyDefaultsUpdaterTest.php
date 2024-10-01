<?php

namespace MediaWiki\Extension\CommunityConfiguration\Tests;

use InvalidArgumentException;
use MediaWiki\Extension\CommunityConfiguration\EmergencyShutdown\EmergencyDefaultsUpdater;
use MediaWiki\Extension\CommunityConfiguration\Provider\IConfigurationProvider;
use MediaWiki\Extension\CommunityConfiguration\Schema\SchemaBuilder;
use MediaWiki\Extension\CommunityConfiguration\Validation\IValidator;
use MediaWiki\Registration\ExtensionRegistry;
use MediaWikiUnitTestCase;

/**
 * @covers \MediaWiki\Extension\CommunityConfiguration\EmergencyShutdown\EmergencyDefaultsUpdater
 */
class EmergencyDefaultsUpdaterTest extends MediaWikiUnitTestCase {

	public function testDefaultsDirectoryUnrecognized() {
		$registryMock = $this->createNoOpMock( ExtensionRegistry::class, [ 'isLoaded' ] );
		$registryMock->expects( $this->once() )
			->method( 'isLoaded' )
			->with( 'NonexistentExtension' )
			->willReturn( false );

		$updater = new EmergencyDefaultsUpdater( $registryMock );

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Extension NonexistentExtension is not loaded' );
		$updater->getDefaultsDirectory( 'NonexistentExtension' );
	}

	public function testDefaultsDirectoryOK() {
		$registryMock = $this->createNoOpMock( ExtensionRegistry::class, [
			'isLoaded',
			'getAllThings',
		] );
		$registryMock->expects( $this->once() )
			->method( 'isLoaded' )
			->with( 'CommunityConfigurationExample' )
			->willReturn( true );
		$registryMock->expects( $this->once() )
			->method( 'getAllThings' )
			->willReturn( [
				'CommunityConfigurationExample' => [
					'path' => '/var/www/html/w/extensions/CommunityConfigurationExample/extension.json',
				],
			] );

		$updater = new EmergencyDefaultsUpdater( $registryMock );
		$this->assertEquals(
			'/var/www/html/w/extensions/CommunityConfigurationExample/CommunityConfigurationFallbacks',
			$updater->getDefaultsDirectory( 'CommunityConfigurationExample' )
		);
	}

	public function testDefaultsFileOK() {
		$registryMock = $this->createNoOpMock( ExtensionRegistry::class, [
			'isLoaded',
			'getAllThings',
		] );
		$registryMock->expects( $this->once() )
			->method( 'isLoaded' )
			->with( 'CommunityConfigurationExample' )
			->willReturn( true );
		$registryMock->expects( $this->once() )
			->method( 'getAllThings' )
			->willReturn( [
				'CommunityConfigurationExample' => [
					'path' => '/var/www/html/w/extensions/CommunityConfigurationExample/extension.json',
				],
			] );

		$providerMock = $this->createNoOpMock( IConfigurationProvider::class, [ 'getId' ] );
		$providerMock->expects( $this->once() )
			->method( 'getId' )
			->willReturn( 'foo' );

		$updater = new EmergencyDefaultsUpdater( $registryMock );
		$this->assertEquals(
			'/var/www/html/w/extensions/CommunityConfigurationExample/CommunityConfigurationFallbacks/foo.php',
			$updater->getDefaultsFileForProvider( $providerMock, 'CommunityConfigurationExample' )
		);
	}

	public function testSerializeDefaultsNoSchema() {
		$validatorMock = $this->createNoOpMock( IValidator::class, [ 'areSchemasSupported' ] );
		$validatorMock->expects( $this->atLeastOnce() )
			->method( 'areSchemasSupported' )
			->willReturn( false );

		$providerMock = $this->createNoOpMock( IConfigurationProvider::class, [ 'getId', 'getValidator' ] );
		$providerMock->expects( $this->atLeastOnce() )
			->method( 'getId' )
			->willReturn( 'foo' );
		$providerMock->expects( $this->atLeastOnce() )
			->method( 'getValidator' )
			->willReturn( $validatorMock );

		$updater = new EmergencyDefaultsUpdater( $this->createNoOpMock( ExtensionRegistry::class ) );

		$this->expectException( InvalidArgumentException::class );
		$updater->getSerializedDefaults( $providerMock );
	}

	public function testSerializeDefaults() {
		$config = (object)[
			'Number' => 42,
			'Array' => [ 0, 1, 2 ],
			'Object' => (object)[
				'A' => 1,
			],
		];

		$builderMock = $this->createNoOpMock( SchemaBuilder::class, [ 'getDefaultsMap' ] );
		$builderMock->expects( $this->atLeastOnce() )
			->method( 'getDefaultsMap' )
			->willReturn( $config );

		$validatorMock = $this->createNoOpMock( IValidator::class, [ 'areSchemasSupported', 'getSchemaBuilder' ] );
		$validatorMock->expects( $this->atLeastOnce() )
			->method( 'areSchemasSupported' )
			->willReturn( true );
		$validatorMock->expects( $this->atLeastOnce() )
			->method( 'getSchemaBuilder' )
			->willReturn( $builderMock );

		$providerMock = $this->createNoOpMock( IConfigurationProvider::class, [ 'getValidator' ] );
		$providerMock->expects( $this->atLeastOnce() )
			->method( 'getValidator' )
			->willReturn( $validatorMock );

		$updater = new EmergencyDefaultsUpdater( $this->createNoOpMock( ExtensionRegistry::class ) );
		$this->assertStringContainsString(
			var_export( $config, true ),
			$updater->getSerializedDefaults( $providerMock )
		);
	}
}

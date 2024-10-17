<?php

namespace MediaWiki\Extension\CommunityConfiguration\Tests;

use InvalidArgumentException;
use MediaWiki\Extension\CommunityConfiguration\EmergencyShutdown\EmergencyDefaultsPathBuilder;
use MediaWiki\Extension\CommunityConfiguration\Provider\IConfigurationProvider;
use MediaWiki\Registration\ExtensionRegistry;
use MediaWikiUnitTestCase;

/**
 * @covers \MediaWiki\Extension\CommunityConfiguration\EmergencyShutdown\EmergencyDefaultsPathBuilder
 */
class EmergencyDefaultsPathBuilderTest extends MediaWikiUnitTestCase {
	public function testDefaultsDirectoryUnrecognized() {
		$registryMock = $this->createNoOpMock( ExtensionRegistry::class, [ 'isLoaded' ] );
		$registryMock->expects( $this->once() )
			->method( 'isLoaded' )
			->with( 'NonexistentExtension' )
			->willReturn( false );

		$utils = new EmergencyDefaultsPathBuilder( $registryMock );

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Extension NonexistentExtension is not loaded' );
		$utils->getDefaultsDirectory( 'NonexistentExtension' );
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

		$utils = new EmergencyDefaultsPathBuilder( $registryMock );
		$this->assertEquals(
			'/var/www/html/w/extensions/CommunityConfigurationExample/CommunityConfigurationFallbacks',
			$utils->getDefaultsDirectory( 'CommunityConfigurationExample' )
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

		$updater = new EmergencyDefaultsPathBuilder( $registryMock );
		$this->assertEquals(
			'/var/www/html/w/extensions/CommunityConfigurationExample/CommunityConfigurationFallbacks/foo.php',
			$updater->getDefaultsFileForProvider( $providerMock, 'CommunityConfigurationExample' )
		);
	}
}

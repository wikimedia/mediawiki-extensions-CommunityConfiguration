<?php

namespace MediaWiki\Extension\CommunityConfiguration\Tests;

use InvalidArgumentException;
use MediaWiki\Extension\CommunityConfiguration\EmergencyShutdown\EmergencyDefaultsPathBuilder;
use MediaWiki\Extension\CommunityConfiguration\EmergencyShutdown\EmergencyDefaultsUpdater;
use MediaWiki\Extension\CommunityConfiguration\Provider\IConfigurationProvider;
use MediaWiki\Extension\CommunityConfiguration\Schema\SchemaBuilder;
use MediaWiki\Extension\CommunityConfiguration\Validation\IValidator;
use MediaWikiUnitTestCase;

/**
 * @covers \MediaWiki\Extension\CommunityConfiguration\EmergencyShutdown\EmergencyDefaultsUpdater
 */
class EmergencyDefaultsUpdaterTest extends MediaWikiUnitTestCase {

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

		$updater = new EmergencyDefaultsUpdater(
			$this->createNoOpMock( EmergencyDefaultsPathBuilder::class )
		);

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
			->with( null, false )
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

		$updater = new EmergencyDefaultsUpdater(
			$this->createNoOpMock( EmergencyDefaultsPathBuilder::class )
		);
		$this->assertStringContainsString(
			var_export( $config, true ),
			$updater->getSerializedDefaults( $providerMock )
		);
	}
}

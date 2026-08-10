<?php

namespace MediaWiki\Extension\CommunityConfiguration\Tests;

use MediaWiki\Config\HashConfig;
use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\CommunityConfiguration\Controls\ControlRegistry;
use MediaWiki\ResourceLoader\ResourceLoader;
use MediaWikiUnitTestCase;
use Psr\Log\NullLogger;

/**
 * @covers \MediaWiki\Extension\CommunityConfiguration\Controls\ControlRegistry
 */
class ControlRegistryTest extends MediaWikiUnitTestCase {

	private const VALID_SPEC = [
		ControlRegistry::MODULE => 'ext.test.lookup',
		ControlRegistry::COMPONENT => 'LookupControl',
	];

	private function getRegistry( array $specs, array $registeredModules = [ 'ext.test.lookup' ] ): ControlRegistry {
		$resourceLoader = $this->createMock( ResourceLoader::class );
		$resourceLoader->method( 'isModuleRegistered' )
			->willReturnCallback( static fn ( $name ) => in_array( $name, $registeredModules, true ) );

		return new ControlRegistry(
			new ServiceOptions(
				ControlRegistry::CONSTRUCTOR_OPTIONS,
				new HashConfig( [ 'CommunityConfigurationControls' => $specs ] )
			),
			$resourceLoader,
			new NullLogger()
		);
	}

	public function testGetControls() {
		$registry = $this->getRegistry( [
			'Test.Lookup' => self::VALID_SPEC,
			'Test.Unused' => self::VALID_SPEC,
		] );

		// Only the control that the caller asks for comes back. An unused registration costs
		// nothing, because the editor never loads its module.
		$this->assertSame(
			[ 'Test.Lookup' => self::VALID_SPEC ],
			$registry->getControls( [ 'Test.Lookup' ] )
		);
	}

	public function testGetControlsDeduplicates() {
		$registry = $this->getRegistry( [ 'Test.Lookup' => self::VALID_SPEC ] );

		$this->assertSame(
			[ 'Test.Lookup' => self::VALID_SPEC ],
			$registry->getControls( [ 'Test.Lookup', 'Test.Lookup' ] )
		);
	}

	public function testGetControlsWithNoNames() {
		$this->assertSame(
			[],
			$this->getRegistry( [ 'Test.Lookup' => self::VALID_SPEC ] )->getControls( [] )
		);
	}

	public static function provideUnusableControl(): iterable {
		yield 'no extension registers it' => [ [], 'Test.Lookup' ];
		yield 'name has no extension part' => [
			[ 'lookup' => self::VALID_SPEC ],
			'lookup',
		];
		yield 'name has too many parts' => [
			[ 'Test.Sub.Lookup' => self::VALID_SPEC ],
			'Test.Sub.Lookup',
		];
		yield 'no module' => [
			[ 'Test.Lookup' => [ ControlRegistry::COMPONENT => 'LookupControl' ] ],
			'Test.Lookup',
		];
		yield 'no component' => [
			[ 'Test.Lookup' => [ ControlRegistry::MODULE => 'ext.test.lookup' ] ],
			'Test.Lookup',
		];
		yield 'empty module' => [
			[ 'Test.Lookup' => [
				ControlRegistry::MODULE => '',
				ControlRegistry::COMPONENT => 'LookupControl',
			] ],
			'Test.Lookup',
		];
		yield 'module is not a string' => [
			[ 'Test.Lookup' => [
				ControlRegistry::MODULE => [ 'ext.test.lookup' ],
				ControlRegistry::COMPONENT => 'LookupControl',
			] ],
			'Test.Lookup',
		];
	}

	/**
	 * @param array $specs
	 * @param string $name
	 * @dataProvider provideUnusableControl
	 */
	public function testGetControlsDropsUnusableControl( array $specs, string $name ) {
		// The editor then falls back to a control of the data schema, instead of failing.
		$this->assertSame( [], $this->getRegistry( $specs )->getControls( [ $name ] ) );
	}

	public function testGetControlsDropsUnregisteredModule() {
		// An extension can register a control while its module registers only under some
		// conditions, for example only when CommunityConfiguration is installed.
		$registry = $this->getRegistry( [ 'Test.Lookup' => self::VALID_SPEC ], [] );

		$this->assertSame( [], $registry->getControls( [ 'Test.Lookup' ] ) );
	}
}

<?php

namespace MediaWiki\Extension\CommunityConfiguration\Tests;

use InvalidArgumentException;
use MediaWiki\Extension\CommunityConfiguration\CommunityConfigurationServices;
use MediaWiki\Extension\CommunityConfiguration\Store\IConfigurationStore;
use MediaWikiIntegrationTestCase;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \MediaWiki\Extension\CommunityConfiguration\Store\StoreFactory
 */
class StoreFactoryTest extends MediaWikiIntegrationTestCase {

	private ?array $storeSpecs = null;

	private function getExtraArgsForStore( string $name ): array {
		switch ( $name ) {
			case 'wikipage':
				return [ 'MediaWiki:Foo.json' ];
			case 'static':
				return [ (object)[] ];
			default:
				return [];
		}
	}

	private function getStoreSpecs(): array {
		if ( !$this->storeSpecs ) {
			$this->storeSpecs = $this->getServiceContainer()->getMainConfig()
				->get( 'CommunityConfigurationStores' );
		}
		return $this->storeSpecs;
	}

	public function testConstructStore() {
		$factory = CommunityConfigurationServices::wrap( $this->getServiceContainer() )
			->getStoreFactory();

		foreach ( $this->getStoreSpecs() as $storeName => $_ ) {
			$this->assertInstanceOf(
				IConfigurationStore::class,
				$factory->newStore(
					$storeName, $storeName,
					$this->getExtraArgsForStore( $storeName )
				)
			);

			// Ensure multiple stores of the same name can be instanciated
			$this->assertInstanceOf(
				IConfigurationStore::class,
				$factory->newStore(
					$storeName . '-second', $storeName,
					$this->getExtraArgsForStore( $storeName )
				)
			);
		}
	}

	public function testNonexistentStore() {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Store nonexistent is not supported' );

		CommunityConfigurationServices::wrap( $this->getServiceContainer() )
			->getStoreFactory()
			->newStore( 'nonexistent', 'nonexistent', [] );
	}

	public function testGetSupportedKeys() {
		$this->assertSame(
			array_keys( $this->getStoreSpecs() ),
			CommunityConfigurationServices::wrap( $this->getServiceContainer() )
				->getStoreFactory()
				->getSupportedKeys()
		);
	}

	public function testStoreWithOptions() {
		$store = CommunityConfigurationServices::wrap( $this->getServiceContainer() )
			->getStoreFactory()
			->newStore(
				'static', 'static',
				$this->getExtraArgsForStore( 'static' ),
				[ 'foo' => 123 ]
			);

		$this->assertSame(
			123,
			TestingAccessWrapper::newFromObject( $store )->getOption( 'foo' )
		);
	}
}

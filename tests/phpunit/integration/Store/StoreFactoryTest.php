<?php

namespace MediaWiki\Extension\CommunityConfiguration\Tests;

use InvalidArgumentException;
use MediaWiki\Extension\CommunityConfiguration\CommunityConfigurationServices;
use MediaWiki\Extension\CommunityConfiguration\Store\IConfigurationStore;
use MediaWikiIntegrationTestCase;

/**
 * @coversDefaultClass \MediaWiki\Extension\CommunityConfiguration\Store\StoreFactory
 */
class StoreFactoryTest extends MediaWikiIntegrationTestCase {

	private ?array $storeSpecs = null;

	private const STORE_EXTRA_ARGS = [
		'wikipage' => [ 'MediaWiki:Foo.json' ],
		'static' => [ [] ]
	];

	private function getStoreSpecs(): array {
		if ( !$this->storeSpecs ) {
			$this->storeSpecs = $this->getServiceContainer()->getMainConfig()
				->get( 'CommunityConfigurationStores' );
		}
		return $this->storeSpecs;
	}

	/**
	 * @covers ::newStore
	 * @return void
	 */
	public function testConstructStore() {
		$factory = CommunityConfigurationServices::wrap( $this->getServiceContainer() )
			->getStoreFactory();

		foreach ( $this->getStoreSpecs() as $storeName => $_ ) {
			$this->assertInstanceOf(
				IConfigurationStore::class,
				$factory->newStore(
					$storeName, $storeName,
					self::STORE_EXTRA_ARGS[$storeName] ?? []
				)
			);

			// Ensure multiple stores of the same name can be instanciated
			$this->assertInstanceOf(
				IConfigurationStore::class,
				$factory->newStore(
					$storeName . '-second', $storeName,
					self::STORE_EXTRA_ARGS[$storeName] ?? []
				)
			);
		}
	}

	/**
	 * @covers ::newStore
	 * @return void
	 */
	public function testNonexistentStore() {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Store nonexistent is not supported' );

		CommunityConfigurationServices::wrap( $this->getServiceContainer() )
			->getStoreFactory()
			->newStore( 'nonexistent', 'nonexistent', [] );
	}

	/**
	 * @covers ::getSupportedKeys
	 * @return void
	 */
	public function testGetSupportedKeys() {
		$this->assertSame(
			array_keys( $this->getStoreSpecs() ),
			CommunityConfigurationServices::wrap( $this->getServiceContainer() )
				->getStoreFactory()
				->getSupportedKeys()
		);
	}
}

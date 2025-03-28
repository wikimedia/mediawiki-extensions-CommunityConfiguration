<?php

namespace MediaWiki\Extension\CommunityConfiguration\Tests;

use MediaWiki\Extension\CommunityConfiguration\Store\AbstractStore;
use MediaWiki\Extension\CommunityConfiguration\Store\VersionedConfiguration;
use MediaWikiUnitTestCase;
use StatusValue;

/**
 * @covers \MediaWiki\Extension\CommunityConfiguration\Store\AbstractStore
 * @covers \MediaWiki\Extension\CommunityConfiguration\Store\VersionedConfiguration
 */
class AbstractStoreTest extends MediaWikiUnitTestCase {

	public static function provideVersionedLoadMethods(): array {
		return [
			'cached' => [ 'loadVersionedConfiguration', 'loadConfiguration' ],
			'uncached' => [ 'loadVersionedConfigurationUncached', 'loadConfigurationUncached' ],
		];
	}

	/**
	 * The default implementation reports no version for stores that do not track one.
	 *
	 * @dataProvider provideVersionedLoadMethods
	 */
	public function testLoadVersionedConfiguration( string $loadMethod, string $delegatedMethod ) {
		$config = (object)[ 'Foo' => 42 ];
		$store = $this->getMockForAbstractClass( AbstractStore::class );
		$store->expects( $this->once() )
			->method( $delegatedMethod )
			->willReturn( StatusValue::newGood( $config ) );

		$statusValue = $store->$loadMethod();
		$this->assertStatusOK( $statusValue );
		$versionedConfiguration = $statusValue->getValue();
		$this->assertInstanceOf( VersionedConfiguration::class, $versionedConfiguration );
		$this->assertSame( $config, $versionedConfiguration->getData() );
		$this->assertNull( $versionedConfiguration->getVersion() );
	}

	/**
	 * A failed load is passed through as-is, without being wrapped: there is no configuration
	 * to pair a version with. Third-party stores rely on this, as they inherit the default
	 * implementation unchanged.
	 *
	 * @dataProvider provideVersionedLoadMethods
	 */
	public function testLoadVersionedConfigurationOnFailedLoad(
		string $loadMethod,
		string $delegatedMethod
	) {
		$store = $this->getMockForAbstractClass( AbstractStore::class );
		$store->expects( $this->once() )
			->method( $delegatedMethod )
			->willReturn( StatusValue::newFatal( 'communityconfiguration-test-load-error' ) );

		$statusValue = $store->$loadMethod();
		$this->assertStatusError( 'communityconfiguration-test-load-error', $statusValue );
		$this->assertNotInstanceOf( VersionedConfiguration::class, $statusValue->getValue() );
	}
}

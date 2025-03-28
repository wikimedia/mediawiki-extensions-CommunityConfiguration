<?php

namespace MediaWiki\Extension\CommunityConfiguration\Tests;

use LogicException;
use MediaWiki\Extension\CommunityConfiguration\Store\StaticStore;
use MediaWiki\Extension\CommunityConfiguration\Store\VersionedConfiguration;
use MediaWiki\Permissions\UltimateAuthority;
use MediaWiki\User\UserIdentityValue;
use MediaWikiUnitTestCase;
use StatusValue;
use stdClass;

/**
 * @covers \MediaWiki\Extension\CommunityConfiguration\Store\StaticStore
 * @covers \MediaWiki\Extension\CommunityConfiguration\Store\AbstractStore
 * @covers \MediaWiki\Extension\CommunityConfiguration\Store\VersionedConfiguration
 */
class StaticStoreTest extends MediaWikiUnitTestCase {

	private function assertStoreStatusOK( stdClass $expectedValue, StatusValue $statusValue ) {
		$this->assertStatusOK( $statusValue );
		$this->assertStatusValue( $expectedValue, $statusValue );
	}

	public function testStore() {
		$config = (object)[ 'Number' => 42, 'String' => 'foo' ];
		$store = new StaticStore( $config );

		$this->assertStoreStatusOK( $config, $store->loadConfiguration() );
		$this->assertStoreStatusOK( $config, $store->loadConfigurationUncached() );

		$this->assertNull( $store->getInfoPageLinkTarget() );
	}

	public static function provideVersionedLoadMethods(): array {
		return [
			'cached' => [ 'loadVersionedConfiguration' ],
			'uncached' => [ 'loadVersionedConfigurationUncached' ],
		];
	}

	/**
	 * A store that does not track schema versions still answers the versioned load methods,
	 * through the default AbstractStore implementation, reporting no version.
	 *
	 * @dataProvider provideVersionedLoadMethods
	 */
	public function testLoadVersionedConfiguration( string $loadMethod ) {
		$config = (object)[ 'Number' => 42, 'String' => 'foo' ];
		$store = new StaticStore( $config );

		$statusValue = $store->$loadMethod();
		$this->assertStatusOK( $statusValue );
		$versionedConfiguration = $statusValue->getValue();
		$this->assertInstanceOf( VersionedConfiguration::class, $versionedConfiguration );
		$this->assertEquals( $config, $versionedConfiguration->getData() );
		$this->assertNull( $versionedConfiguration->getVersion() );
		$this->assertNull( $store->getVersion() );
	}

	public function testNoChanges() {
		$this->expectException( LogicException::class );
		$this->expectExceptionMessage( 'Static store cannot be edited' );

		( new StaticStore( (object)[] ) )->storeConfiguration(
			(object)[ 'Foo' => 1 ],
			null,
			new UltimateAuthority( new UserIdentityValue( 1, 'Admin' ) )
		);
	}

}

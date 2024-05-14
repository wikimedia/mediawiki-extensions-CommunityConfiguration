<?php

namespace MediaWiki\Extension\CommunityConfiguration\Tests;

use LogicException;
use MediaWiki\Extension\CommunityConfiguration\Store\StaticStore;
use MediaWiki\Permissions\UltimateAuthority;
use MediaWiki\User\UserIdentityValue;
use MediaWikiUnitTestCase;
use StatusValue;
use stdClass;

/**
 * @covers \MediaWiki\Extension\CommunityConfiguration\Store\StaticStore
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

	public function testNoChanges() {
		$this->expectException( LogicException::class );
		$this->expectExceptionMessage( 'Static store cannot be edited' );

		( new StaticStore( (object)[] ) )->storeConfiguration(
			(object)[ 'Foo' => 1 ],
			new UltimateAuthority( new UserIdentityValue( 1, 'Admin' ) )
		);
	}
}

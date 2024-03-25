<?php

namespace MediaWiki\Extension\CommunityConfiguration\Tests;

use MediaWiki\Extension\CommunityConfiguration\Store\ICustomReadConstants;
use MediaWiki\Extension\CommunityConfiguration\Store\WikiPage\Loader;
use MediaWiki\Extension\CommunityConfiguration\Store\WikiPage\Writer;
use MediaWiki\Extension\CommunityConfiguration\Store\WikiPageStore;
use MediaWiki\Permissions\UltimateAuthority;
use MediaWiki\Status\Status;
use MediaWiki\Title\Title;
use MediaWiki\Title\TitleFactory;
use MediaWiki\User\UserIdentityValue;
use MediaWikiUnitTestCase;
use StatusValue;

/**
 * @coversDefaultClass \MediaWiki\Extension\CommunityConfiguration\Store\WikiPageStore
 */
class WikiPageStoreTest extends MediaWikiUnitTestCase {

	/**
	 * @covers ::getConfigurationTitle
	 */
	public function testGetConfigurationTitle() {
		$titleMock = $this->createNoOpMock( Title::class );
		$titleFactoryMock = $this->createMock( TitleFactory::class );
		$titleFactoryMock->expects( $this->once() )
			->method( 'newFromTextThrow' )
			->with( 'MediaWiki:Foo.json' )
			->willReturn( $titleMock );

		$store = new WikiPageStore(
			'MediaWiki:Foo.json',
			$titleFactoryMock,
			$this->createNoOpMock( Loader::class ),
			$this->createNoOpMock( Writer::class )
		);
		$this->assertSame( $titleMock, $store->getConfigurationTitle() );
		$this->assertSame( $titleMock, $store->getConfigurationTitle() );
	}

	/**
	 * @covers ::loadConfigurationUncached
	 */
	public function testLoadConfigurationUncached() {
		$titleMock = $this->createNoOpMock( Title::class );
		$titleFactoryMock = $this->createMock( TitleFactory::class );
		$titleFactoryMock->expects( $this->once() )
			->method( 'newFromTextThrow' )
			->with( 'MediaWiki:Foo.json' )
			->willReturn( $titleMock );

		$statusValue = StatusValue::newGood();
		$loaderMock = $this->createMock( Loader::class );
		$loaderMock->expects( $this->once() )
			->method( 'load' )
			->with( $titleMock, ICustomReadConstants::READ_UNCACHED )
			->willReturn( $statusValue );

		$store = new WikiPageStore(
			'MediaWiki:Foo.json',
			$titleFactoryMock,
			$loaderMock,
			$this->createNoOpMock( Writer::class )
		);
		$this->assertSame( $statusValue, $store->loadConfigurationUncached() );
	}

	/**
	 * @covers ::loadConfiguration
	 */
	public function testLoadConfiguration() {
		$titleMock = $this->createNoOpMock( Title::class );
		$titleFactoryMock = $this->createMock( TitleFactory::class );
		$titleFactoryMock->expects( $this->once() )
			->method( 'newFromTextThrow' )
			->with( 'MediaWiki:Foo.json' )
			->willReturn( $titleMock );

		$statusValue = StatusValue::newGood();
		$loaderMock = $this->createMock( Loader::class );
		$loaderMock->expects( $this->once() )
			->method( 'load' )
			->with( $titleMock, 0 )
			->willReturn( $statusValue );

		$store = new WikiPageStore(
			'MediaWiki:Foo.json',
			$titleFactoryMock,
			$loaderMock,
			$this->createNoOpMock( Writer::class )
		);
		$this->assertSame( $statusValue, $store->loadConfiguration() );
	}

	/**
	 * @covers ::storeConfiguration
	 */
	public function testStoreConfiguration() {
		$newConfig = [ 'Foo' => 42, 'Bar' => 123 ];
		$authority = new UltimateAuthority( new UserIdentityValue( 1, 'Admin' ) );
		$summary = 'foo';

		$titleMock = $this->createNoOpMock( Title::class );
		$titleFactoryMock = $this->createMock( TitleFactory::class );
		$titleFactoryMock->expects( $this->once() )
			->method( 'newFromTextThrow' )
			->with( 'MediaWiki:Foo.json' )
			->willReturn( $titleMock );

		$statusValue = StatusValue::newGood();
		$statusMock = $this->createMock( Status::class );
		$statusMock->expects( $this->once() )
			->method( 'getStatusValue' )
			->willReturn( $statusValue );
		$writerMock = $this->createMock( Writer::class );
		$writerMock->expects( $this->once() )
			->method( 'save' )
			->with( $titleMock, $newConfig, $authority, $summary )
			->willReturn( $statusMock );

		$loaderMock = $this->createMock( Loader::class );
		$loaderMock->expects( $this->once() )
			->method( 'invalidate' )
			->with( $titleMock );

		$store = new WikiPageStore(
			'MediaWiki:Foo.json',
			$titleFactoryMock,
			$loaderMock,
			$writerMock
		);
		$this->assertSame(
			$statusValue,
			$store->doStoreConfiguration( $newConfig, $authority, $summary )
		);
	}
}

<?php

namespace MediaWiki\Extension\CommunityConfiguration\Tests;

use FormatJson;
use HashBagOStuff;
use JsonContent;
use LogicException;
use MediaWiki\Extension\CommunityConfiguration\Store\WikiPage\Writer;
use MediaWiki\Extension\CommunityConfiguration\Store\WikiPageStore;
use MediaWiki\Permissions\UltimateAuthority;
use MediaWiki\Revision\RevisionLookup;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Status\Status;
use MediaWiki\Title\Title;
use MediaWiki\Title\TitleFactory;
use MediaWiki\User\UserIdentityValue;
use MediaWikiUnitTestCase;
use StatusValue;
use WANObjectCache;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \MediaWiki\Extension\CommunityConfiguration\Store\WikiPageStore
 * @covers \MediaWiki\Extension\CommunityConfiguration\Store\AbstractJsonStore
 */
class WikiPageStoreTest extends MediaWikiUnitTestCase {

	/**
	 * @var TitleFactory
	 */
	private $titleFactoryMock;

	/**
	 * @var WANObjectCache
	 */
	private $cache;

	/**
	 * @var RevisionLookup
	 */
	private $revisionLookupMock;

	/**
	 * @var Writer
	 */
	private $writerMock;

	/**
	 * TODO: Review and update existing tests to utilize the centralized setup from this `setUp` method.
	 * Will be done as part of technical debt introduced by changes to the test setup,
	 * ensuring all tests leverage the consistent setup.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->titleFactoryMock = $this->createMock( TitleFactory::class );
		$this->cache = new WANObjectCache( [ 'cache' => new HashBagOStuff() ] );
		$this->revisionLookupMock = $this->createNoOpMock( RevisionLookup::class );
		$this->writerMock = $this->createNoOpMock( Writer::class );
	}

	private function getRevisionLookupMock( Title $title, $config ) {
		$revisionRecordMock = $this->createMock( RevisionRecord::class );
		$revisionRecordMock->expects( $this->once() )
			->method( 'getContent' )
			->willReturn( new JsonContent( FormatJson::encode( $config ) ) );
		$revisionLookupMock = $this->createMock( RevisionLookup::class );
		$revisionLookupMock->expects( $this->once() )
			->method( 'getRevisionByTitle' )
			->with( $title )
			->willReturn( $revisionRecordMock );
		return $revisionLookupMock;
	}

	public function testGetConfigurationTitle() {
		$titleMock = $this->createNoOpMock( Title::class );
		$titleFactoryMock = $this->createMock( TitleFactory::class );
		$titleFactoryMock->expects( $this->once() )
			->method( 'newFromTextThrow' )
			->with( 'MediaWiki:Foo.json' )
			->willReturn( $titleMock );

		$store = new WikiPageStore(
			'MediaWiki:Foo.json',
			new WANObjectCache( [ 'cache' => new HashBagOStuff() ] ),
			$titleFactoryMock,
			$this->createNoOpMock( RevisionLookup::class ),
			$this->createNoOpMock( Writer::class )
		);
		$this->assertSame( $titleMock, $store->getConfigurationTitle() );
		$this->assertSame( $titleMock, $store->getConfigurationTitle() );
	}

	public function testLoadConfigurationUncached() {
		$titleMock = $this->createMock( Title::class );
		$titleMock->expects( $this->once() )
			->method( 'isExternal' )
			->willReturn( false );
		$titleFactoryMock = $this->createMock( TitleFactory::class );
		$titleFactoryMock->expects( $this->once() )
			->method( 'newFromTextThrow' )
			->with( 'MediaWiki:Foo.json' )
			->willReturn( $titleMock );

		$store = new WikiPageStore(
			'MediaWiki:Foo.json',
			new WANObjectCache( [ 'cache' => new HashBagOStuff() ] ),
			$titleFactoryMock,
			$this->getRevisionLookupMock( $titleMock, [ 'Foo' => 42 ] ),
			$this->createNoOpMock( Writer::class )
		);
		$statusValue = $store->loadConfigurationUncached();
		$this->assertStatusOK( $statusValue );
		$this->assertStatusValue( (object)[ 'Foo' => 42 ], $statusValue );
	}

	public function testLoadConfiguration() {
		$titleMock = $this->createMock( Title::class );
		$titleMock->expects( $this->once() )
			->method( 'isExternal' )
			->willReturn( false );
		$titleFactoryMock = $this->createMock( TitleFactory::class );
		$titleFactoryMock->expects( $this->once() )
			->method( 'newFromTextThrow' )
			->with( 'MediaWiki:Foo.json' )
			->willReturn( $titleMock );

		$store = new WikiPageStore(
			'MediaWiki:Foo.json',
			new WANObjectCache( [ 'cache' => new HashBagOStuff() ] ),
			$titleFactoryMock,
			$this->getRevisionLookupMock( $titleMock, [ 'Foo' => 42 ] ),
			$this->createNoOpMock( Writer::class )
		);

		// this should be a cache miss
		$statusValue = $store->loadConfiguration();
		$this->assertStatusOK( $statusValue );
		$this->assertStatusValue( (object)[ 'Foo' => 42 ], $statusValue );

		// this hits in-process cache (asserted by expects( $this->once() ) above)
		$statusValue = $store->loadConfiguration();
		$this->assertStatusOK( $statusValue );
		$this->assertStatusValue( (object)[ 'Foo' => 42 ], $statusValue );

		// verify WAN cache works as well (asserted by expects( $this->once() ) above)
		TestingAccessWrapper::newFromObject( $store )->inProcessCache->clear();
		$statusValue = $store->loadConfiguration();
		$this->assertStatusOK( $statusValue );
		$this->assertStatusValue( (object)[ 'Foo' => 42 ], $statusValue );
	}

	public function testInProcessCaching() {
		$titleMock = $this->createMock( Title::class );
		$titleMock->expects( $this->once() )
			->method( 'isExternal' )
			->willReturn( false );
		$titleFactoryMock = $this->createMock( TitleFactory::class );
		$titleFactoryMock->expects( $this->once() )
			->method( 'newFromTextThrow' )
			->with( 'MediaWiki:Foo.json' )
			->willReturn( $titleMock );

		$wanBagOStuff = new HashBagOStuff();
		$store = new WikiPageStore(
			'MediaWiki:Foo.json',
			new WANObjectCache( [ 'cache' => $wanBagOStuff ] ),
			$titleFactoryMock,
			$this->getRevisionLookupMock( $titleMock, [ 'Foo' => 42 ] ),
			$this->createNoOpMock( Writer::class )
		);

		// this should be a cache miss
		$statusValue = $store->loadConfiguration();
		$this->assertStatusOK( $statusValue );
		$this->assertStatusValue( (object)[ 'Foo' => 42 ], $statusValue );

		// clear WAN cache, but keep in-process cache intact; assert in-process caching works
		$wanBagOStuff->clear();
		$statusValue = $store->loadConfiguration();
		$this->assertStatusOK( $statusValue );
		$this->assertStatusValue( (object)[ 'Foo' => 42 ], $statusValue );
	}

	public function testStoreConfiguration() {
		$newConfig = [ 'Foo' => 42, 'Bar' => 123 ];
		$authority = new UltimateAuthority( new UserIdentityValue( 1, 'Admin' ) );
		$summary = 'foo';

		$titleMock = $this->createMock( Title::class );
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

		$store = new WikiPageStore(
			'MediaWiki:Foo.json',
			new WANObjectCache( [ 'cache' => new HashBagOStuff() ] ),
			$titleFactoryMock,
			$this->createNoOpMock( RevisionLookup::class ),
			$writerMock
		);
		$this->assertSame(
			$statusValue,
			$store->storeConfiguration( $newConfig, null, $authority, $summary )
		);
	}

	public function testWithExternalPage() {
		$titleMock = $this->createMock( Title::class );
		$titleMock->expects( $this->once() )->method( 'isExternal' )->willReturn( true );
		$titleFactoryMock = $this->createMock( TitleFactory::class );
		$titleFactoryMock->expects( $this->once() )
			->method( 'newFromTextThrow' )
			->with( 'mw:MediaWiki:Foo.json' )
			->willReturn( $titleMock );

		$store =
			new WikiPageStore( 'mw:MediaWiki:Foo.json',
				new WANObjectCache( [ 'cache' => new HashBagOStuff() ] ), $titleFactoryMock,
				$this->createNoOpMock( RevisionLookup::class ),
				$this->createNoOpMock( Writer::class ) );

		$this->expectException( LogicException::class );
		$this->expectExceptionMessage( 'Config page should not be external' );
		$store->loadConfiguration();
	}

	public function testGetVersion() {
		$titleMock = $this->createMock( Title::class );
		$titleMock->expects( $this->once() )
			->method( 'isExternal' )
			->willReturn( false );
		$titleFactoryMock = $this->createMock( TitleFactory::class );
		$titleFactoryMock->expects( $this->once() )
			->method( 'newFromTextThrow' )
			->with( 'MediaWiki:Foo.json' )
			->willReturn( $titleMock );

		$store = new WikiPageStore(
			'MediaWiki:Foo.json',
			new WANObjectCache( [ 'cache' => new HashBagOStuff() ] ),
			$titleFactoryMock,
			$this->getRevisionLookupMock( $titleMock, [
				'Foo' => 42,
				WikiPageStore::VERSION_FIELD_NAME => '2.0.0',
			] ),
			$this->createNoOpMock( Writer::class )
		);

		$this->assertSame( '2.0.0', $store->getVersion() );
	}
}

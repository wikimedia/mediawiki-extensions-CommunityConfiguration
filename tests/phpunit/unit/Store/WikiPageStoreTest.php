<?php

namespace MediaWiki\Extension\CommunityConfiguration\Tests;

use LogicException;
use MediaWiki\Content\JsonContent;
use MediaWiki\Extension\CommunityConfiguration\Store\VersionedConfiguration;
use MediaWiki\Extension\CommunityConfiguration\Store\WikiPage\Writer;
use MediaWiki\Extension\CommunityConfiguration\Store\WikiPageStore;
use MediaWiki\Json\FormatJson;
use MediaWiki\Permissions\UltimateAuthority;
use MediaWiki\Revision\RevisionLookup;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Status\Status;
use MediaWiki\Title\Title;
use MediaWiki\Title\TitleFactory;
use MediaWiki\User\UserIdentityValue;
use MediaWikiUnitTestCase;
use StatusValue;
use Wikimedia\ObjectCache\HashBagOStuff;
use Wikimedia\ObjectCache\WANObjectCache;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \MediaWiki\Extension\CommunityConfiguration\Store\WikiPageStore
 * @covers \MediaWiki\Extension\CommunityConfiguration\Store\AbstractJsonStore
 * @covers \MediaWiki\Extension\CommunityConfiguration\Store\AbstractStore
 * @covers \MediaWiki\Extension\CommunityConfiguration\Store\VersionedConfiguration
 */
class WikiPageStoreTest extends MediaWikiUnitTestCase {

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

	/**
	 * @param mixed $config Configuration the revision lookup will serve
	 * @return WikiPageStore
	 */
	private function newStoreWithConfig( $config ): WikiPageStore {
		$titleMock = $this->createMock( Title::class );
		$titleMock->method( 'isExternal' )->willReturn( false );
		$titleFactoryMock = $this->createMock( TitleFactory::class );
		$titleFactoryMock->expects( $this->once() )
			->method( 'newFromTextThrow' )
			->with( 'MediaWiki:Foo.json' )
			->willReturn( $titleMock );

		return new WikiPageStore(
			'MediaWiki:Foo.json',
			new WANObjectCache( [ 'cache' => new HashBagOStuff() ] ),
			$titleFactoryMock,
			$this->getRevisionLookupMock( $titleMock, $config ),
			$this->createNoOpMock( Writer::class ),
			false
		);
	}

	/**
	 * A store whose configuration page exists, but does not hold JSON content
	 *
	 * @return WikiPageStore
	 */
	private function newStoreWithNonJsonContent(): WikiPageStore {
		$titleMock = $this->createMock( Title::class );
		$titleMock->method( 'isExternal' )->willReturn( false );
		$titleFactoryMock = $this->createMock( TitleFactory::class );
		$titleFactoryMock->expects( $this->once() )
			->method( 'newFromTextThrow' )
			->with( 'MediaWiki:Foo.json' )
			->willReturn( $titleMock );

		$revisionRecordMock = $this->createMock( RevisionRecord::class );
		$revisionRecordMock->expects( $this->once() )
			->method( 'getContent' )
			->willReturn( null );
		$revisionLookupMock = $this->createMock( RevisionLookup::class );
		$revisionLookupMock->expects( $this->once() )
			->method( 'getRevisionByTitle' )
			->with( $titleMock )
			->willReturn( $revisionRecordMock );

		return new WikiPageStore(
			'MediaWiki:Foo.json',
			new WANObjectCache( [ 'cache' => new HashBagOStuff() ] ),
			$titleFactoryMock,
			$revisionLookupMock,
			$this->createNoOpMock( Writer::class ),
			false
		);
	}

	public static function provideUnversionedLoadMethods(): array {
		return [
			'cached' => [ 'loadConfiguration' ],
			'uncached' => [ 'loadConfigurationUncached' ],
		];
	}

	public static function provideVersionedLoadMethods(): array {
		return [
			'cached' => [ 'loadVersionedConfiguration' ],
			'uncached' => [ 'loadVersionedConfigurationUncached' ],
		];
	}

	/**
	 * The plain load methods hide the version field; it is metadata about the configuration,
	 * not part of it, and callers validating the result against a schema must not see it.
	 *
	 * @dataProvider provideUnversionedLoadMethods
	 */
	public function testLoadConfigurationStripsVersion( string $loadMethod ) {
		$store = $this->newStoreWithConfig( [
			'Foo' => 42,
			WikiPageStore::VERSION_FIELD_NAME => '2.0.0',
		] );

		$statusValue = $store->$loadMethod();
		$this->assertStatusOK( $statusValue );
		$this->assertStatusValue( (object)[ 'Foo' => 42 ], $statusValue );
	}

	/**
	 * @dataProvider provideVersionedLoadMethods
	 */
	public function testLoadVersionedConfiguration( string $loadMethod ) {
		$store = $this->newStoreWithConfig( [
			'Foo' => 42,
			WikiPageStore::VERSION_FIELD_NAME => '2.0.0',
		] );

		$statusValue = $store->$loadMethod();
		$this->assertStatusOK( $statusValue );
		$versionedConfiguration = $statusValue->getValue();
		$this->assertInstanceOf( VersionedConfiguration::class, $versionedConfiguration );
		$this->assertEquals( (object)[ 'Foo' => 42 ], $versionedConfiguration->getData() );
		$this->assertSame( '2.0.0', $versionedConfiguration->getVersion() );
	}

	/**
	 * @dataProvider provideVersionedLoadMethods
	 */
	public function testLoadVersionedConfigurationWithoutVersion( string $loadMethod ) {
		$store = $this->newStoreWithConfig( [ 'Foo' => 42 ] );

		$statusValue = $store->$loadMethod();
		$this->assertStatusOK( $statusValue );
		$versionedConfiguration = $statusValue->getValue();
		$this->assertInstanceOf( VersionedConfiguration::class, $versionedConfiguration );
		$this->assertEquals( (object)[ 'Foo' => 42 ], $versionedConfiguration->getData() );
		$this->assertNull( $versionedConfiguration->getVersion() );
	}

	/**
	 * A configuration whose top level is not an object cannot carry a version field.
	 *
	 * @dataProvider provideVersionedLoadMethods
	 */
	public function testLoadVersionedConfigurationWithNonObjectConfig( string $loadMethod ) {
		$store = $this->newStoreWithConfig( [ 1, 2, 3 ] );

		$statusValue = $store->$loadMethod();
		$this->assertStatusOK( $statusValue );
		$versionedConfiguration = $statusValue->getValue();
		$this->assertInstanceOf( VersionedConfiguration::class, $versionedConfiguration );
		$this->assertSame( [ 1, 2, 3 ], $versionedConfiguration->getData() );
		$this->assertNull( $versionedConfiguration->getVersion() );
	}

	/**
	 * A version field holding something that cannot be a version fails the load, rather than
	 * being treated as unversioned: falling back to the most recent schema is exactly what
	 * recording the stored version exists to prevent.
	 *
	 * @dataProvider provideVersionedLoadMethods
	 */
	public function testLoadVersionedConfigurationWithMalformedVersion( string $loadMethod ) {
		$store = $this->newStoreWithConfig( [
			'Foo' => 42,
			WikiPageStore::VERSION_FIELD_NAME => 1.1,
		] );

		$this->assertStatusError(
			'communityconfiguration-malformed-schema-version',
			$store->$loadMethod()
		);
	}

	/**
	 * An explicitly null version is indistinguishable from an absent one, as it has always
	 * been, and does not count as malformed.
	 *
	 * @dataProvider provideVersionedLoadMethods
	 */
	public function testLoadVersionedConfigurationWithNullVersion( string $loadMethod ) {
		$store = $this->newStoreWithConfig( [
			'Foo' => 42,
			WikiPageStore::VERSION_FIELD_NAME => null,
		] );

		$statusValue = $store->$loadMethod();
		$this->assertStatusOK( $statusValue );
		$this->assertNull( $statusValue->getValue()->getVersion() );
	}

	/**
	 * A failed load is passed through as-is, without being wrapped: there is no configuration
	 * to pair a version with.
	 *
	 * @dataProvider provideVersionedLoadMethods
	 */
	public function testLoadVersionedConfigurationOnFailedLoad( string $loadMethod ) {
		$store = $this->newStoreWithNonJsonContent();

		$statusValue = $store->$loadMethod();
		$this->assertStatusNotOK( $statusValue );
		$this->assertStatusValue( null, $statusValue );
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
			$this->createNoOpMock( Writer::class ),
			false
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
			$this->createNoOpMock( Writer::class ),
			false
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
			$this->createNoOpMock( Writer::class ),
			false
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
			$this->createNoOpMock( Writer::class ),
			false
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
			$writerMock,
			false
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
				$this->createNoOpMock( Writer::class ),
				false
			);

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
			$this->createNoOpMock( Writer::class ),
			false
		);

		$this->assertSame( '2.0.0', $store->getVersion() );
	}

	public function testWithStorageDisabled() {
		$store = new WikiPageStore(
			'MediaWiki:Foo.json',
			new WANObjectCache( [ 'cache' => new HashBagOStuff() ] ),
			$this->createNoOpMock( TitleFactory::class ),
			$this->createNoOpMock( RevisionLookup::class ),
			$this->createNoOpMock( Writer::class ),
			true
		);

		$status = $store->loadConfigurationUncached();
		$this->assertStatusOK( $status );
		$this->assertStatusValue( (object)[], $status );
	}

	public static function provideVersionFieldValues(): array {
		return [
			'well-formed' => [ '1.0.0', true ],
			'multi-digit parts' => [ '10.20.30', true ],
			'two parts only' => [ '1.0', false ],
			'four parts' => [ '1.0.0.0', false ],
			'prerelease suffix' => [ '1.0.0-beta', false ],
			'not a version at all' => [ 'latest', false ],
			'empty string' => [ '', false ],
			'integer' => [ 1, false ],
			'float' => [ 1.1, false ],
			'boolean' => [ true, false ],
			// Callers treat null as "unversioned" before consulting the predicate; see
			// readVersionField() and alwaysStoreConfiguration().
			'null' => [ null, false ],
			'list' => [ [ '1.0.0' ], false ],
			'object' => [ (object)[ 'version' => '1.0.0' ], false ],
		];
	}

	/**
	 * @dataProvider provideVersionFieldValues
	 * @param mixed $version
	 * @param bool $expected
	 */
	public function testIsVersionFieldValue( $version, bool $expected ) {
		$this->assertSame( $expected, WikiPageStore::isVersionFieldValue( $version ) );
	}

	public static function provideMalformedStoredVersions(): array {
		return [
			'not a version at all' => [ 'latest' ],
			'two parts only' => [ '1.0' ],
			// Previously skipped silently, because the version was stamped under a
			// truthiness check.
			'empty string' => [ '' ],
		];
	}

	/**
	 * The store must not write a version that readVersionField() would refuse to read back.
	 *
	 * The parameter is typed ?string, so only the format can be wrong here; every collaborator
	 * is a no-op mock, which asserts that a rejected version reaches neither the writer nor
	 * the title resolution.
	 *
	 * @dataProvider provideMalformedStoredVersions
	 */
	public function testAlwaysStoreConfigurationRejectsMalformedVersion( string $version ) {
		$store = new WikiPageStore(
			'MediaWiki:Foo.json',
			new WANObjectCache( [ 'cache' => new HashBagOStuff() ] ),
			$this->createNoOpMock( TitleFactory::class ),
			$this->createNoOpMock( RevisionLookup::class ),
			$this->createNoOpMock( Writer::class ),
			false
		);

		$this->assertStatusError(
			'communityconfiguration-malformed-schema-version',
			$store->alwaysStoreConfiguration(
				(object)[ 'Foo' => 42 ],
				$version,
				new UltimateAuthority( new UserIdentityValue( 1, 'Admin' ) ),
				'summary'
			)
		);
	}
}

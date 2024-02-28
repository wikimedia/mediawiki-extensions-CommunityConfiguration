<?php

namespace MediaWiki\Extension\CommunityConfiguration\Store\WikiPage;

use HashBagOStuff;
use JsonContent;
use LogicException;
use MediaWiki\Linker\LinkTarget;
use MediaWiki\Revision\RevisionLookup;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\Title\TitleFactory;
use MediaWikiUnitTestCase;
use StatusValue;
use stdClass;
use WANObjectCache;

class LoaderTest extends MediaWikiUnitTestCase {

	/**
	 * @covers \MediaWiki\Extension\CommunityConfiguration\Store\WikiPage\Loader
	 */
	public function testLoaderLoadsValidConfigurationOnCacheMiss() {
		$cache = new WANObjectCache( [ 'cache' => new HashBagOStuff() ] );
		$revisionLookupMock = $this->createMock( RevisionLookup::class );
		$titleFactoryMock = $this->createMock( TitleFactory::class );
		$linkTarget = $this->createMock( LinkTarget::class );

		$revisionRecordMock = $this->createMock( RevisionRecord::class );
		$revisionRecordMock->method( 'getContent' )
			->with( SlotRecord::MAIN, RevisionRecord::FOR_PUBLIC )
			->willReturn( new JsonContent( json_encode( ( [ 'CCExampleBackgroundColor' => 'brown' ] ) ) ) );

		$revisionLookupMock->expects( $this->once() )
			->method( 'getRevisionByTitle' )
			->with( $linkTarget, 0, 0 )
			->willReturn( $revisionRecordMock );

		$loader = new Loader( $cache, $revisionLookupMock, $titleFactoryMock );
		$loadedData = $loader->load( $linkTarget );

		$this->assertInstanceOf(
			StatusValue::class, $loadedData, "The loaded data is not an instance of StatusValue." );

		$expectedValue = new stdClass();
		$expectedValue->CCExampleBackgroundColor = 'brown';
		$this->assertEquals(
			$expectedValue, $loadedData->getValue(), "Cache miss: Loaded data does not match expected data." );
	}

	/**
	 * @covers \MediaWiki\Extension\CommunityConfiguration\Store\WikiPage\Loader
	 */
	public function testLoaderLoadsFromCacheOnHit() {
		$cache = new WANObjectCache( [ 'cache' => new HashBagOStuff() ] );
		$revisionLookupMock1 = $this->createMock( RevisionLookup::class );
		$titleFactoryMock1 = $this->createMock( TitleFactory::class );
		$linkTarget1 = $this->createMock( LinkTarget::class );

		$revisionRecordMock1 = $this->createMock( RevisionRecord::class );
		$revisionRecordMock1->method( 'getContent' )
			->with( SlotRecord::MAIN, RevisionRecord::FOR_PUBLIC )
			->willReturn( new JsonContent( json_encode( [ 'CCExampleBackgroundColor' => 'red' ] ) ) );

		$revisionLookupMock1->expects( $this->once() )
			->method( 'getRevisionByTitle' )
			->with( $linkTarget1, 0, 0 )
			->willReturn( $revisionRecordMock1 );

		$loader1 = new Loader( $cache, $revisionLookupMock1, $titleFactoryMock1 );

		// Initial load to populate cache
		$loader1->load( $linkTarget1 );

		// Subsequent load
		$revisionLookupMock2 = $this->createMock( RevisionLookup::class );
		$titleFactoryMock2 = $this->createMock( TitleFactory::class );
		$linkTarget2 = $this->createMock( LinkTarget::class );

		// No revision lookup on cache hit
		$revisionLookupMock2->expects( $this->never() )
			->method( 'getRevisionByTitle' );

		$loader2 = new Loader( $cache, $revisionLookupMock2, $titleFactoryMock2 );
		$loadedData = $loader2->load( $linkTarget2 );

		$this->assertInstanceOf(
			StatusValue::class, $loadedData, "The loaded data is not an instance of StatusValue." );
		$expectedValue = new stdClass();
		$expectedValue->CCExampleBackgroundColor = 'red';
		$this->assertEquals(
			$expectedValue, $loadedData->getValue(), "Cache miss: Loaded data does not match expected data." );
	}

	/**
	 * @covers \MediaWiki\Extension\CommunityConfiguration\Store\WikiPage\Loader
	 */
	public function testLoaderLoadsUpdatedConfigurationAfterInvalidation() {
		$cache = new WANObjectCache( [ 'cache' => new HashBagOStuff() ] );

		// Initial load
		$revisionLookupMock1 = $this->createMock( RevisionLookup::class );
		$titleFactoryMock1 = $this->createMock( TitleFactory::class );
		$linkTarget1 = $this->createMock( LinkTarget::class );

		$revisionRecordMock1 = $this->createMock( RevisionRecord::class );
		$revisionRecordMock1->method( 'getContent' )
			->with( SlotRecord::MAIN, RevisionRecord::FOR_PUBLIC )
			->willReturn( new JsonContent( json_encode( [ 'CCExampleBackgroundColor' => 'brown' ] ) ) );

		$revisionLookupMock1->expects( $this->once() )
			->method( 'getRevisionByTitle' )
			->with( $linkTarget1, 0, 0 )
			->willReturn( $revisionRecordMock1 );

		$loader1 = new Loader( $cache, $revisionLookupMock1, $titleFactoryMock1 );

		// Load on cache miss
		$loader1->load( $linkTarget1 );

		// For load after invalidation
		$revisionLookupMock2 = $this->createMock( RevisionLookup::class );
		$titleFactoryMock2 = $this->createMock( TitleFactory::class );
		$linkTarget2 = $this->createMock( LinkTarget::class );

		$revisionRecordMock2 = $this->createMock( RevisionRecord::class );
		$revisionRecordMock2->method( 'getContent' )
			->with( SlotRecord::MAIN, RevisionRecord::FOR_PUBLIC )
			->willReturn( new JsonContent( json_encode( [ 'CCExampleBackgroundColor' => 'gold' ] ) ) );

		$revisionLookupMock2->expects( $this->once() )
			->method( 'getRevisionByTitle' )
			->with( $linkTarget2, 0, 0 )
			->willReturn( $revisionRecordMock2 );

		$loader2 = new Loader( $cache, $revisionLookupMock2, $titleFactoryMock2 );

		// Invalidate cache
		$loader2->invalidate( $linkTarget2 );

		$loadedData = $loader2->load( $linkTarget2 );
		// assert on cache miss after invalidation
		$this->assertInstanceOf(
			StatusValue::class,
			$loadedData, "The loaded data is not an instance of StatusValue after cache invalidation." );
		$expectedValue = new stdClass();
		$expectedValue->CCExampleBackgroundColor = 'gold';
		$this->assertEquals(
			$expectedValue,
			$loadedData->getValue(), "Cache miss after invalidation: Loaded data does not match expected data." );
	}

	/**
	 * @covers \MediaWiki\Extension\CommunityConfiguration\Store\WikiPage\Loader::fetchConfig
	 */
	public function testLoaderThrowsExceptionOnExternalPage() {
		$cache = new WANObjectCache( [ 'cache' => new HashBagOStuff() ] );
		$revisionLookupMock = $this->createMock( RevisionLookup::class );
		$titleFactoryMock = $this->createMock( TitleFactory::class );

		$externalLinkTarget = $this->createMock( LinkTarget::class );
		$externalLinkTarget->method( 'isExternal' )->willReturn( true );

		$loader = new Loader( $cache, $revisionLookupMock, $titleFactoryMock );

		$this->expectException( LogicException::class );
		$this->expectExceptionMessage( 'Config page should not be external' );

		$loader->load( $externalLinkTarget, 0 );
	}

	/**
	 * @covers \MediaWiki\Extension\CommunityConfiguration\Store\WikiPage\Loader::fetchConfig
	 */
	public function testLoaderHandlesNonExistentPage() {
		$cache = new WANObjectCache( [ 'cache' => new HashBagOStuff() ] );
		$revisionLookupMock = $this->createMock( RevisionLookup::class );
		$titleFactoryMock = $this->createMock( TitleFactory::class );

		$linkTarget = $this->createMock( LinkTarget::class );
		$revisionLookupMock->method( 'getRevisionByTitle' )
			->with( $linkTarget, 0, 0 )
			->willReturn( null );

		$loader = new Loader( $cache, $revisionLookupMock, $titleFactoryMock );

		$result = $loader->load( $linkTarget, 0 );

		$expectedValue = new stdClass();
		$this->assertEquals(
			$expectedValue, $result->getValue(), "Expected an empty stdClass object for non-existent pages." );
	}
}

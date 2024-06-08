<?php

namespace MediaWiki\Extension\CommunityConfiguration\Tests;

use MediaWiki\Content\JsonContent;
use MediaWiki\Extension\CommunityConfiguration\Store\WikiPage\Writer;
use MediaWiki\HookContainer\HookContainer;
use MediaWiki\Json\FormatJson;
use MediaWiki\Page\PageIdentity;
use MediaWiki\Page\WikiPageFactory;
use MediaWiki\Permissions\Authority;
use MediaWiki\Permissions\SimpleAuthority;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\Storage\PageUpdater;
use MediaWiki\User\User;
use MediaWiki\User\UserFactory;
use MediaWiki\User\UserIdentityValue;
use MediaWikiUnitTestCase;
use RecentChange;
use WikiPage;

/**
 * @covers \MediaWiki\Extension\CommunityConfiguration\Store\WikiPage\Writer
 */
class WriterTest extends MediaWikiUnitTestCase {

	private function getWikiPageFactory(
		PageUpdater $updater,
		Authority $authority,
		PageIdentity $configPage
	) {
		$wikiPage = $this->createMock( WikiPage::class );
		$wikiPage->expects( $this->once() )
			->method( 'newPageUpdater' )
			->with( $authority )
			->willReturn( $updater );
		$wikiPageFactoryMock = $this->createMock( WikiPageFactory::class );
		$wikiPageFactoryMock->expects( $this->once() )
			->method( 'newFromTitle' )
			->with( $configPage )
			->willReturn( $wikiPage );
		return $wikiPageFactoryMock;
	}

	public static function provideSaveOK() {
		return [
			[ 'tag', [ 'edit' ] ],
			[ [ 'tag-1', 'tag-2' ], [ 'edit', 'autopatrol' ] ],
			[ [ 'tag-1', 'tag-2' ], [ 'edit' ] ],
		];
	}

	/**
	 * @dataProvider provideSaveOK
	 * @param array|string $tags
	 * @param array $permissions
	 * @return void
	 */
	public function testSaveOK( $tags, array $permissions ) {
		$authority = new SimpleAuthority(
			new UserIdentityValue( 1, 'Admin' ),
			$permissions
		);
		$newContentData = [
			'ANumber' => 42,
			'BObject' => [ 'Foo' => 1, 'Bar' => 2 ],
			'ZString' => 'some value',
		];
		// recursively convert arrays to PHP objects
		$newContent = json_decode( json_encode( $newContentData ) );

		$configPageMock = $this->createNoOpMock( PageIdentity::class );

		$updater = $this->createMock( PageUpdater::class );
		$updater->expects( $this->once() )
			->method( 'setContent' )
			->with( SlotRecord::MAIN, new JsonContent( FormatJson::encode( $newContent ) ) );

		$updater->expects( in_array( 'autopatrol', $permissions ) ? $this->once() : $this->never() )
			->method( 'setRcPatrolStatus' )
			->with( RecentChange::PRC_AUTOPATROLLED );

		if ( is_string( $tags ) ) {
			$updater->expects( $this->once() )
				->method( 'addTag' )
				->with( $tags );
		} else {
			$updater->expects( $this->once() )
				->method( 'addTags' )
				->with( $tags );
		}

		$userFactoryMock = $this->createMock( UserFactory::class );
		$userFactoryMock->expects( $this->once() )
			->method( 'newFromAuthority' )
			->with( $authority )
			->willReturn( $this->createNoOpMock( User::class ) );

		$hookContainer = $this->createMock( HookContainer::class );
		$hookContainer->expects( $this->once() )
			->method( 'run' )
			->with( 'EditFilterMergedContent', $this->anything() )
			->willReturn( true );

		$writer = new Writer(
			$this->getWikiPageFactory( $updater, $authority, $configPageMock ),
			$userFactoryMock,
			$hookContainer
		);
		$status = $writer->save(
			$configPageMock,
			$newContent,
			$authority,
			'summary',
			false,
			$tags
		);
		$this->assertStatusOK( $status );

		// assert $newContent was not modified
		$this->assertEquals(
			json_decode( json_encode( $newContentData ) ),
			$newContent
		);
	}
}

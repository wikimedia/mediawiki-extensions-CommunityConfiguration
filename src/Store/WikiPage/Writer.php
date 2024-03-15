<?php

namespace MediaWiki\Extension\CommunityConfiguration\Store\WikiPage;

use Content;
use DerivativeContext;
use FormatJson;
use JsonContent;
use MediaWiki\CommentStore\CommentStoreComment;
use MediaWiki\HookContainer\HookContainer;
use MediaWiki\HookContainer\HookRunner;
use MediaWiki\Page\PageIdentity;
use MediaWiki\Page\WikiPageFactory;
use MediaWiki\Permissions\Authority;
use MediaWiki\Permissions\PermissionStatus;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\Status\Status;
use MediaWiki\Title\Title;
use MediaWiki\User\UserFactory;
use RecentChange;
use RequestContext;

class Writer {

	private WikiPageFactory $wikiPageFactory;
	private UserFactory $userFactory;
	private HookContainer $hookContainer;

	/**
	 * @param WikiPageFactory $wikiPageFactory
	 * @param UserFactory $userFactory
	 * @param HookContainer $hookContainer
	 */
	public function __construct(
		WikiPageFactory $wikiPageFactory,
		UserFactory $userFactory,
		HookContainer $hookContainer
	) {
		$this->wikiPageFactory = $wikiPageFactory;
		$this->userFactory = $userFactory;
		$this->hookContainer = $hookContainer;
	}

	/**
	 * Save a new version to the configuration page
	 *
	 * No permission changes or validation is performed.
	 *
	 * @param PageIdentity $configPage
	 * @param mixed $newConfig
	 * @param Authority $performer
	 * @param string $summary
	 * @param bool $minor
	 * @param array|string $tags Tag(s) to apply (defaults to none)
	 * @return Status
	 */
	public function doSave(
		PageIdentity $configPage,
		$newConfig,
		Authority $performer,
		string $summary = '',
		bool $minor = false,
		$tags = []
	): Status {
		// REVIEW: Should this validate $configPage is an acceptable target?

		// Sort config alphabetically

		$configSorted = get_object_vars( $newConfig );
		uasort( $configSorted, static function ( $a, $b ): int {
			if ( $a == $b ) {
				return 0;
			}
			return ( $a > $b ) ? -1 : 1;
		} );

		$status = Status::newGood();
		$content = new JsonContent( FormatJson::encode( $configSorted ) );

		// Give AbuseFilter et al. a chance to block the edit (T346235)
		$page = $this->wikiPageFactory->newFromTitle( $configPage );
		$status->merge( $this->runEditFilterMergedContentHook(
			$performer,
			$page->getTitle(),
			$content,
			$summary,
			$minor
		) );

		if ( !$status->isOK() ) {
			return $status;
		}

		$updater = $page->newPageUpdater( $performer );
		if ( is_string( $tags ) ) {
			$updater->addTag( $tags );
		} elseif ( is_array( $tags ) ) {
			$updater->addTags( $tags );
		}
		$updater->setContent( SlotRecord::MAIN, $content );

		if ( $performer->isAllowed( 'autopatrol' ) ) {
			$updater->setRcPatrolStatus( RecentChange::PRC_AUTOPATROLLED );
		}

		$updater->saveRevision(
			CommentStoreComment::newUnsavedComment( $summary ),
			$minor ? EDIT_MINOR : 0
		);
		$status->merge( $updater->getStatus() );

		return $status;
	}

	/**
	 * Save a new version of the configuration page
	 *
	 * Permissions are verified.
	 *
	 * @param PageIdentity $configPage
	 * @param mixed $newConfig The configuration value to store. Can be any JSON serializable type
	 * @param Authority $performer
	 * @param string $summary
	 * @param bool $minor
	 * @param array|string $tags Tag(s) to apply (defaults to none)
	 * @return Status
	 */
	public function save(
		PageIdentity $configPage,
		$newConfig,
		Authority $performer,
		string $summary = '',
		bool $minor = false,
		$tags = []
	): Status {
		$permissionStatus = PermissionStatus::newGood();
		if ( !$performer->authorizeWrite( 'edit', $configPage, $permissionStatus ) ) {
			return Status::wrap( $permissionStatus );
		}

		return $this->doSave(
			$configPage,
			$newConfig,
			$performer,
			$summary,
			$minor,
			$tags
		);
	}

	/**
	 * Run the EditFilterMergedContentHook
	 *
	 * @param Authority $performer
	 * @param Title $title
	 * @param Content $content
	 * @param string $summary
	 * @param bool $minor
	 * @return Status
	 */
	private function runEditFilterMergedContentHook(
		Authority $performer,
		Title $title,
		Content $content,
		string $summary,
		bool $minor
	): Status {
		$performerUser = $this->userFactory->newFromAuthority( $performer );

		// Ensure context has right values for title and performer, which are available to the
		// config writer. Use the global context for the rest.
		$derivativeContext = new DerivativeContext( RequestContext::getMain() );
		$derivativeContext->setUser( $performerUser );
		$derivativeContext->setTitle( $title );

		$status = new Status();
		$hookRunner = new HookRunner( $this->hookContainer );
		if ( !$hookRunner->onEditFilterMergedContent(
			$derivativeContext,
			$content,
			$status,
			$summary,
			$performerUser,
			$minor
		) ) {
			if ( $status->isGood() ) {
				$status->fatal( 'hookaborted' );
			}
		}
		return $status;
	}
}

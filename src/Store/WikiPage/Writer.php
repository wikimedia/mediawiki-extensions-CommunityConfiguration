<?php

namespace MediaWiki\Extension\CommunityConfiguration\Store\WikiPage;

use MediaWiki\CommentStore\CommentStoreComment;
use MediaWiki\Content\Content;
use MediaWiki\Content\JsonContent;
use MediaWiki\Context\DerivativeContext;
use MediaWiki\Context\RequestContext;
use MediaWiki\HookContainer\HookContainer;
use MediaWiki\HookContainer\HookRunner;
use MediaWiki\Json\FormatJson;
use MediaWiki\Page\PageIdentity;
use MediaWiki\Page\WikiPageFactory;
use MediaWiki\Permissions\Authority;
use MediaWiki\Permissions\UltimateAuthority;
use MediaWiki\RecentChanges\RecentChange;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\Status\Status;
use MediaWiki\Title\Title;
use MediaWiki\User\UserFactory;

class Writer {

	private WikiPageFactory $wikiPageFactory;
	private UserFactory $userFactory;
	private HookContainer $hookContainer;

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
	public function save(
		PageIdentity $configPage,
		$newConfig,
		Authority $performer,
		string $summary = '',
		bool $minor = false,
		$tags = []
	): Status {
		// REVIEW: Should this validate $configPage is an acceptable target?

		// Sort config alphabetically
		$configSorted = (array)$newConfig;
		ksort( $configSorted );
		$status = Status::newGood();
		$content = new JsonContent( FormatJson::encode( (object)$configSorted ) );

		$page = $this->wikiPageFactory->newFromTitle( $configPage );

		// Give AbuseFilter et al. a chance to block the edit (T346235)
		// Do not run when UltimateAuthority is used (from e.g. maintenance scripts), as in those
		// cases, we want the edit to succeed regardless of permissions.
		if ( !$performer instanceof UltimateAuthority ) {
			$status->merge( $this->runEditFilterMergedContentHook(
				$performer,
				$page->getTitle(),
				$content,
				$summary,
				$minor
			) );
		}

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

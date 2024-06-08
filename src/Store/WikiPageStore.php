<?php

namespace MediaWiki\Extension\CommunityConfiguration\Store;

use ApiRawMessage;
use LogicException;
use MediaWiki\Content\JsonContent;
use MediaWiki\Extension\CommunityConfiguration\Store\WikiPage\Writer;
use MediaWiki\Linker\LinkTarget;
use MediaWiki\Permissions\Authority;
use MediaWiki\Permissions\PermissionStatus;
use MediaWiki\Revision\RevisionLookup;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\Status\Status;
use MediaWiki\Title\MalformedTitleException;
use MediaWiki\Title\Title;
use MediaWiki\Title\TitleFactory;
use StatusValue;
use WANObjectCache;

class WikiPageStore extends AbstractJsonStore {

	private const CACHE_VERSION = 1;

	public const VERSION_FIELD_NAME = '$version';

	private ?string $configLocation;
	private ?Title $configTitle = null;
	private TitleFactory $titleFactory;
	private RevisionLookup $revisionLookup;
	private Writer $writer;

	/**
	 * @param string|null $configLocation
	 * @param WANObjectCache $cache
	 * @param TitleFactory $titleFactory
	 * @param RevisionLookup $revisionLookup
	 * @param Writer $writer
	 */
	public function __construct(
		?string $configLocation,
		WANObjectCache $cache,
		TitleFactory $titleFactory,
		RevisionLookup $revisionLookup,
		Writer $writer
	) {
		parent::__construct( $cache );

		$this->configLocation = $configLocation;
		$this->titleFactory = $titleFactory;
		$this->revisionLookup = $revisionLookup;
		$this->writer = $writer;
	}

	/**
	 * @throws MalformedTitleException
	 */
	public function getConfigurationTitle(): Title {
		if ( $this->configTitle === null && $this->configLocation ) {
			$this->configTitle = $this->titleFactory->newFromTextThrow( $this->configLocation );
		}
		return $this->configTitle;
	}

	/**
	 * @inheritDoc
	 */
	public function getInfoPageLinkTarget(): ?LinkTarget {
		return $this->getConfigurationTitle();
	}

	protected function makeCacheKey(): string {
		$configPage = $this->getConfigurationTitle();
		return $this->cache->makeKey( __CLASS__,
			self::CACHE_VERSION,
			$configPage->getNamespace(), $configPage->getDBkey() );
	}

	/**
	 * @inheritDoc
	 */
	protected function fetchJsonBlob(): StatusValue {
		$configPage = $this->getConfigurationTitle();

		if ( $configPage->isExternal() ) {
			throw new LogicException( 'Config page should not be external' );
		}

		$revision = $this->revisionLookup->getRevisionByTitle( $configPage );
		if ( !$revision ) {
			// The configuration page does not exist. Pretend it does not contain anything (failure
			// mode and empty-page behavior is equal, see T325236).
			// Top-level types different from object will require a corresponding empty value. eg: [] for arrays.
			return StatusValue::newGood( '{}' );
		}

		$content = $revision->getContent( SlotRecord::MAIN, RevisionRecord::FOR_PUBLIC );
		if ( !$content instanceof JsonContent ) {
			return StatusValue::newFatal( new ApiRawMessage(
				'The configuration title has no content or is not JSON content.'
			) );
		}

		// Do not return the parsed JSON just yet, to ensure each caller gets their own copy of
		// deserialized data. This needs to happen to avoid cache pollution. See T364101 for more
		// details.
		return StatusValue::newGood( $content->getText() );
	}

	/**
	 * @inheritDoc
	 * @param bool $dropVersion Should version be dropped from the result?
	 */
	public function loadConfiguration( bool $dropVersion = true ): StatusValue {
		$result = parent::loadConfiguration();
		if ( $dropVersion ) {
			$result = self::removeVersionDataFromStatus( $result );
		}
		return $result;
	}

	/**
	 * @inheritDoc
	 * @param bool $dropVersion Should version be dropped from the result?
	 */
	public function loadConfigurationUncached( bool $dropVersion = true ): StatusValue {
		$result = parent::loadConfigurationUncached();
		if ( $dropVersion ) {
			$result = self::removeVersionDataFromStatus( $result );
		}
		return $result;
	}

	/**
	 * Remove version data from status returned by the WikiPageStore
	 *
	 * @internal Only public to be used from ValidationHooks
	 * @param StatusValue $status as returned by WikiPageStore::loadConfiguration(Uncached)
	 * @return StatusValue
	 */
	public static function removeVersionDataFromStatus( StatusValue $status ): StatusValue {
		$data = $status->getValue();
		if ( $data ) {
			unset( $data->{self::VERSION_FIELD_NAME} );
			$status->setResult( $status->isOK(), $data );
		}
		return $status;
	}

	/**
	 * @inheritDoc
	 */
	public function getVersion(): ?string {
		$status = $this->loadConfiguration( false );
		if ( !$status->isOK() ) {
			return null;
		}
		return $status->getValue()->{self::VERSION_FIELD_NAME} ?? null;
	}

	/**
	 * @inheritDoc
	 */
	public function storeConfiguration(
		$config,
		?string $version,
		Authority $authority,
		string $summary = ''
	): StatusValue {
		$permissionStatus = PermissionStatus::newGood();
		if ( !$authority->authorizeWrite( 'edit', $this->getConfigurationTitle(), $permissionStatus ) ) {
			return Status::wrap( $permissionStatus );
		}

		if ( $version ) {
			$config->{self::VERSION_FIELD_NAME} = $version;
		}

		$status = $this->writer->save(
			$this->getConfigurationTitle(),
			$config,
			$authority,
			$summary
		)->getStatusValue();
		$this->invalidate();
		return $status;
	}

	/**
	 * @inheritDoc
	 */
	public function probablyCanEdit( Authority $authority ): bool {
		return $authority->probablyCan( 'edit', $this->getConfigurationTitle() );
	}

	/**
	 * @inheritDoc
	 */
	public function definitelyCanEdit( Authority $authority ): bool {
		return $authority->definitelyCan( 'edit', $this->getConfigurationTitle() );
	}
}

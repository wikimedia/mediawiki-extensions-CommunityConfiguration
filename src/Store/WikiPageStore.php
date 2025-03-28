<?php

namespace MediaWiki\Extension\CommunityConfiguration\Store;

use LogicException;
use MediaWiki\Api\ApiRawMessage;
use MediaWiki\Content\JsonContent;
use MediaWiki\Extension\CommunityConfiguration\Store\WikiPage\Writer;
use MediaWiki\Json\FormatJson;
use MediaWiki\Linker\LinkTarget;
use MediaWiki\MediaWikiServices;
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
use Wikimedia\ObjectCache\WANObjectCache;

class WikiPageStore extends AbstractJsonStore {

	public const OPTION_EXTRA_TAGS = 'extraTags';
	private const CACHE_VERSION = 1;

	public const VERSION_FIELD_NAME = '$version';
	public const TAG_NAME = 'community configuration';

	/**
	 * Versions name a Schema class (see JsonSchemaVersionManager::getVersionForSchema(),
	 * which maps dots to underscores), so they are restricted to what can appear there.
	 */
	private const VERSION_FIELD_PATTERN = '/^\d+\.\d+\.\d+$/';

	private bool $isTestWithStorageDisabled;
	private ?Title $configTitle = null;

	public function __construct(
		private readonly ?string $configLocation,
		WANObjectCache $cache,
		private readonly TitleFactory $titleFactory,
		private readonly RevisionLookup $revisionLookup,
		private readonly Writer $writer,
		?bool $isTestWithStorageDisabled = null
	) {
		parent::__construct( $cache );

		if ( $isTestWithStorageDisabled === null ) {
			$isTestWithStorageDisabled = defined( 'MW_PHPUNIT_TEST' ) &&
				MediaWikiServices::getInstance()->isStorageDisabled();
		}
		$this->isTestWithStorageDisabled = $isTestWithStorageDisabled;
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
		if ( $this->isTestWithStorageDisabled ) {
			// Storage is unavailable, so pretend the page does not contain anything (failure
			// mode and empty-page behavior is equal, see T325236).
			return StatusValue::newGood( '{}' );
		}

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
	 */
	public function loadConfiguration(): StatusValue {
		return self::removeVersionDataFromStatus( parent::loadConfiguration() );
	}

	/**
	 * @inheritDoc
	 */
	public function loadConfigurationUncached(): StatusValue {
		return self::removeVersionDataFromStatus( parent::loadConfigurationUncached() );
	}

	/**
	 * @inheritDoc
	 */
	public function loadVersionedConfiguration(): StatusValue {
		return self::splitVersionFromStatus( parent::loadConfiguration() );
	}

	/**
	 * @inheritDoc
	 */
	public function loadVersionedConfigurationUncached(): StatusValue {
		return self::splitVersionFromStatus( parent::loadConfigurationUncached() );
	}

	/**
	 * Split a raw load into the configuration and the version stored alongside it
	 *
	 * @param StatusValue $status As returned by AbstractJsonStore::loadConfiguration(Uncached),
	 * that is, still carrying the version field
	 * @return StatusValue
	 */
	private static function splitVersionFromStatus( StatusValue $status ): StatusValue {
		if ( !$status->isOK() ) {
			return $status;
		}
		$versionStatus = self::readVersionField( $status->getValue() );
		if ( !$versionStatus->isOK() ) {
			return $versionStatus;
		}
		return self::newVersionedConfigurationStatus(
			self::removeVersionDataFromStatus( $status ),
			$versionStatus->getValue()
		);
	}

	/**
	 * Is $version something that may be stored in the version field?
	 *
	 * @see WikiPageStore::readVersionField()
	 * @param mixed $version
	 * @return bool
	 */
	public static function isVersionFieldValue( mixed $version ): bool {
		return is_string( $version ) && (bool)preg_match( self::VERSION_FIELD_PATTERN, $version );
	}

	/**
	 * Read the schema version stored alongside the configuration
	 *
	 * The field is arbitrary user-provided JSON, but every consumer of it takes ?string, so a
	 * non-string value would reach them as an uncaught TypeError. Validating here keeps that
	 * check in one place, next to the constant that names the field.
	 *
	 * A well-formed version is not necessarily a *known* one: there may be no Schema class
	 * for it. That remains the validator's business, reported as
	 * communityconfiguration-invalid-schema-version. This only rejects values that cannot be
	 * a version at all.
	 *
	 * @internal Only public to be used from ValidationHooks
	 * @param mixed $data Decoded configuration as loaded from the page
	 * @return StatusValue If OK, the version string, or null when the field is absent
	 */
	public static function readVersionField( mixed $data ): StatusValue {
		$version = is_object( $data ) ? ( $data->{self::VERSION_FIELD_NAME} ?? null ) : null;
		if ( $version === null ) {
			// Absent, or explicitly null: the configuration is unversioned, which is what this
			// read has always reported for either.
			return StatusValue::newGood( null );
		}

		if ( !self::isVersionFieldValue( $version ) ) {
			return StatusValue::newFatal(
				'communityconfiguration-malformed-schema-version',
				// The value is user-provided and need not be a scalar; render it rather than
				// passing it through, because a non-scalar message parameter is itself a
				// fatal, which would defeat the point of this check.
				FormatJson::encode( $version )
			);
		}

		return StatusValue::newGood( $version );
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
		$status = $this->loadVersionedConfiguration();
		if ( !$status->isOK() ) {
			return null;
		}
		return $status->getValue()->getVersion();
	}

	/**
	 * @inheritDoc
	 */
	public function alwaysStoreConfiguration(
		$config,
		?string $version,
		Authority $authority,
		string $summary = ''
	): StatusValue {
		if ( $version !== null ) {
			if ( !self::isVersionFieldValue( $version ) ) {
				// Callers may pass a version straight from user input; see setVersionData.php
				// and migrateConfig.php --version. Refuse to write what readVersionField()
				// would then refuse to read back.
				return StatusValue::newFatal(
					'communityconfiguration-malformed-schema-version',
					FormatJson::encode( $version )
				);
			}
			$config->{self::VERSION_FIELD_NAME} = $version;
		}

		$status = $this->writer->save(
			$this->getConfigurationTitle(),
			$config,
			$authority,
			$summary,
			false,
			array_merge(
				[ self::TAG_NAME ],
				$this->getOption( self::OPTION_EXTRA_TAGS ) ?? []
			)
		)->getStatusValue();
		$this->invalidate();
		return $status;
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
		return $this->alwaysStoreConfiguration( $config, $version, $authority, $summary );
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

<?php

namespace MediaWiki\Extension\CommunityConfiguration\Store\WikiPage;

use ApiRawMessage;
use DBAccessObjectUtils;
use FormatJson;
use HashBagOStuff;
use IDBAccessObject;
use JsonContent;
use LogicException;
use MediaWiki\Extension\CommunityConfiguration\Store\CustomReadConstantsTrait;
use MediaWiki\Extension\CommunityConfiguration\Store\ICustomReadConstants;
use MediaWiki\Linker\LinkTarget;
use MediaWiki\Revision\RevisionLookup;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\Title\TitleFactory;
use StatusValue;
use stdClass;
use WANObjectCache;
use Wikimedia\LightweightObjectStore\ExpirationAwareness;

class Loader implements IDBAccessObject, ICustomReadConstants {

	use CustomReadConstantsTrait;

	private WANObjectCache $cache;
	private HashBagOStuff $inProcessCache;
	private RevisionLookup $revisionLookup;
	private TitleFactory $titleFactory;

	public function __construct(
		WANObjectCache $cache,
		RevisionLookup $revisionLookup,
		TitleFactory $titleFactory
	) {
		$this->cache = $cache;
		$this->inProcessCache = new HashBagOStuff();
		$this->revisionLookup = $revisionLookup;
		$this->titleFactory = $titleFactory;
	}

	/**
	 * @param LinkTarget $configPage
	 * @return string
	 */
	private function makeCacheKey( LinkTarget $configPage ) {
		return $this->cache->makeKey( __CLASS__,
			$configPage->getNamespace(), $configPage->getDBkey() );
	}

	/**
	 * @param LinkTarget $configPage
	 */
	public function invalidate( LinkTarget $configPage ) {
		$cacheKey = $this->makeCacheKey( $configPage );
		$this->cache->delete( $cacheKey );
		$this->inProcessCache->delete( $cacheKey );
	}

	/**
	 * Load the configured page, with caching.
	 * @param LinkTarget $configPage
	 * @param int $flags bit field, see self::READ_XXX
	 * @return array|StatusValue The content of the configuration page (as JSON
	 *   data in PHP-native format), or a StatusValue on error.
	 */
	public function load( LinkTarget $configPage, int $flags = 0 ) {
		if (
			DBAccessObjectUtils::hasFlags( $flags, self::READ_LATEST ) ||
			// This is a custom flag, but bitfield logic should work regardless.
			DBAccessObjectUtils::hasFlags( $flags, self::READ_UNCACHED )
		) {
			// User does not want to used cached data, invalidate the cache.
			$this->invalidate( $configPage );
		}

		// WANObjectCache has an in-process cache (pcTTL), but it is not subject
		// to invalidation, which breaks WikiPageConfigLoaderTest.
		return $this->inProcessCache->getWithSetCallback(
			$this->makeCacheKey( $configPage ),
			ExpirationAwareness::TTL_INDEFINITE,
			function () use ( $configPage, $flags ) {
				return $this->loadFromWanCache( $configPage, $flags );
			}
		);
	}

	/**
	 * Load configuration from the WAN cache
	 *
	 * @param LinkTarget $configPage
	 * @param int $flags bit field, see self::READ_XXX
	 * @return array|StatusValue The content of the configuration page (as JSON
	 *   data in PHP-native format), or a StatusValue on error.
	 */
	private function loadFromWanCache( LinkTarget $configPage, int $flags = 0 ) {
		return $this->cache->getWithSetCallback(
			$this->makeCacheKey( $configPage ),
			// Cache config for a day; cache is invalidated by WikiPageStore::storeConfiguration
			// when changing the config file.
			ExpirationAwareness::TTL_DAY,
			function ( $oldValue, &$ttl ) use ( $configPage, $flags ) {
				$flags = $this->removeCustomFlags( $flags );
				$result = $this->fetchConfig( $configPage, $flags );
				if ( !$result->isOK() ) {
					// error should not be cached
					$ttl = ExpirationAwareness::TTL_UNCACHEABLE;
				}
				return $result;
			}
		);
	}

	/**
	 * Fetch the contents of the configuration page, without caching.
	 *
	 * Result is not validated with a config validator.
	 *
	 * @param LinkTarget $configPage
	 * @param int $flags bit field, see IDBAccessObject::READ_XXX; do NOT pass READ_UNCACHED
	 * @return StatusValue Status object, with the configuration (as JSON data) on success.
	 */
	private function fetchConfig( LinkTarget $configPage, int $flags ) {
		if ( $configPage->isExternal() ) {
			throw new LogicException( 'Config page should not be external' );
		}

		$revision = $this->revisionLookup->getRevisionByTitle( $configPage, 0, $flags );
		if ( !$revision ) {
			// The configuration page does not exist. Pretend it does not contain anything (failure
			// mode and empty-page behavior is equal, see T325236).
			// Top-level types different from object will require a corresponding empty value. eg: [] for arrays.
			return StatusValue::newGood( new stdClass() );
		}

		$content = $revision->getContent( SlotRecord::MAIN, RevisionRecord::FOR_PUBLIC );
		if ( !$content instanceof JsonContent ) {
			return StatusValue::newFatal( new ApiRawMessage(
				'The configuration title has no content or is not JSON content.'
			) );
		}
		return FormatJson::parse( $content->getText() );
	}

}

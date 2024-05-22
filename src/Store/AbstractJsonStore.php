<?php

namespace MediaWiki\Extension\CommunityConfiguration\Store;

use FormatJson;
use HashBagOStuff;
use StatusValue;
use WANObjectCache;
use Wikimedia\LightweightObjectStore\ExpirationAwareness;

/**
 * Implements caching for the store; assumes stored values are JSON blobs
 */
abstract class AbstractJsonStore implements IConfigurationStore {

	protected WANObjectCache $cache;
	protected HashBagOStuff $inProcessCache;

	public function __construct( WANObjectCache $cache ) {
		$this->cache = $cache;
		$this->inProcessCache = new HashBagOStuff();
	}

	/**
	 * Create a key to cache the stored blobs under
	 *
	 * @return string
	 */
	abstract protected function makeCacheKey(): string;

	/**
	 * Fetch JSON blob itself
	 *
	 * @return StatusValue When OK, must contain the JSON blob (represented as string) as the value.
	 */
	abstract protected function fetchJsonBlob(): StatusValue;

	/**
	 * @inheritDoc
	 */
	public function invalidate(): void {
		$cacheKey = $this->makeCacheKey();
		$this->cache->delete( $cacheKey );
		$this->inProcessCache->delete( $cacheKey );
	}

	/**
	 * @inheritDoc
	 */
	public function loadConfiguration(): StatusValue {
		// WANObjectCache has an in-process cache (pcTTL), but it is not subject
		// to invalidation.
		$result = $this->inProcessCache->getWithSetCallback(
			$this->makeCacheKey(),
			ExpirationAwareness::TTL_INDEFINITE,
			function () {
				return $this->loadFromWanCache();
			}
		);

		if ( $result->isOK() ) {
			// Deserialize the data at the very last step, to ensure each caller gets their own
			// copy of the data. This is to avoid cache pollution; see LoaderIntegrationTest and
			// T364101 for more details.
			return FormatJson::parse( $result->getValue() );
		}

		// Return a (shallow) clone of the (cached) StatusValue. This is necessary, as
		// StatusValue objects are mutable and cached in the in-process cache. Omitting this is
		// likely to result in a cache pollution problem similar to T364101.
		return clone $result;
	}

	/**
	 * @inheritDoc
	 */
	public function loadConfigurationUncached(): StatusValue {
		$result = $this->fetchJsonBlob();
		if ( $result->isOK() ) {
			// Deserialize the data at the very last step, to ensure each caller gets their own
			// copy of the data. This is to avoid cache pollution; see LoaderIntegrationTest and
			// T364101 for more details.
			return FormatJson::parse( $result->getValue() );
		}

		// Return a (shallow) clone of the (cached) StatusValue. This is necessary, as
		// StatusValue objects are mutable and cached in the in-process cache. Omitting this is
		// likely to result in a cache pollution problem similar to T364101.
		return clone $result;
	}

	private function loadFromWanCache() {
		return $this->cache->getWithSetCallback(
			$this->makeCacheKey(),
			ExpirationAwareness::TTL_DAY,
			function ( $oldValue, &$ttl ) {
				$result = $this->fetchJsonBlob();
				if ( !$result->isOK() ) {
					// error should not be cached
					$ttl = ExpirationAwareness::TTL_UNCACHEABLE;
				}
				return $result;
			}
		);
	}
}

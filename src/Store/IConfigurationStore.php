<?php

namespace MediaWiki\Extension\CommunityConfiguration\Store;

use StatusValue;

/**
 * Representation of the configuration store
 *
 * Store object defines where/how is a configuration stored and is able to both read from and
 * write to that location. Reads/writes happen at the blob level (meaning all values are inserted
 * at once). This is to maximize the number of use cases CC2.0 can have, and considering most
 * traffic is read (which can be cached) and writes are very rare, it should be fine.
 *
 * Supported store objects are defined in $wgCommunityConfigurationStores, which can look
 * like this (dict of ObjectFactory specs keyed by store name):
 * {
 *     "static": {
 *         "class": "MediaWiki\\Extension\\CommunityConfiguration\\Store\\StaticStore",
 *         "services": []
 *     },
 *     "wikipage": {
 *         "class": "MediaWiki\\Extension\\CommunityConfiguration\\Store\\WikiPageStore",
 *         "services": []
 *     }
 * }
 */
interface IConfigurationStore {

	/**
	 * Get the location of the configuration
	 *
	 * @return ?string
	 */
	public function getConfigurationLocation(): ?string;

	/**
	 * Load the configuration without any caching
	 *
	 * @return array
	 */
	public function loadConfigurationUncached(): array;

	/**
	 * Store the configuration
	 *
	 * @note Permissions are the caller's responsibility
	 * @param array $config
	 * @return StatusValue
	 */
	public function storeConfiguration( array $config ): StatusValue;
}

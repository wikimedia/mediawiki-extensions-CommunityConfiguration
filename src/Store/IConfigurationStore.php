<?php

namespace MediaWiki\Extension\CommunityConfiguration\Store;

use MediaWiki\Permissions\Authority;
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
 *
 * Configuration store provides direct access to the underlying store. There is no validation,
 * permission control or similar. Unless you are certain this is what you need, consider using
 * IConfigurationProvider's methods instead.
 */
interface IConfigurationStore {

	/**
	 * Invalidate internal cache
	 *
	 * @return void
	 */
	public function invalidate(): void;

	/**
	 * Load the configuration without any caching
	 *
	 * @return StatusValue
	 */
	public function loadConfigurationUncached(): StatusValue;

	/**
	 * Load the configuration (cached)
	 *
	 * @return StatusValue
	 */
	public function loadConfiguration(): StatusValue;

	/**
	 * Store the configuration
	 *
	 * @note Permissions are the caller's responsibility
	 * @param array $config
	 * @param Authority $authority
	 * @param string $summary Short (human-written) summary of the change
	 * @return StatusValue
	 */
	public function storeConfiguration(
		array $config,
		Authority $authority,
		string $summary = ''
	): StatusValue;
}

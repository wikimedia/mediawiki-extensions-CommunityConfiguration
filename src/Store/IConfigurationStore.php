<?php

namespace MediaWiki\Extension\CommunityConfiguration\Store;

use MediaWiki\Linker\LinkTarget;
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
 * Configuration store does not provide validation; ensure you validate the config via an
 * appropriate IValidator instance (ideally through IConfigurationProvider::getValidator). Unless you
 * are certain this is what you need, consider using IConfigurationProvider's methods for
 * writing configuration instead.
 *
 * Store implementations are recommended to inherit AbstractStore.
 */
interface IConfigurationStore {

	/**
	 * Set store options
	 *
	 * Options are used by the store to adapt its behavior; options cannot be queried outside of
	 * the store.
	 *
	 * @see AbstractStore::getOption()
	 * @param array $options A map of option name => option value
	 * @return void
	 */
	public function setOptions( array $options ): void;

	/**
	 * @return LinkTarget|null
	 */
	public function getInfoPageLinkTarget(): ?LinkTarget;

	/**
	 * Invalidate internal cache
	 *
	 * @return void
	 */
	public function invalidate(): void;

	/**
	 * Get version for the currently stored data
	 *
	 * @return string|null null if version was not stored
	 */
	public function getVersion(): ?string;

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
	 * Permissions are checked by the store.
	 *
	 * @param mixed $config The configuration value to store. Can be any JSON serializable type.
	 * @param string|null $version Version of the data (null means store no version data)
	 * @param Authority $authority
	 * @param string $summary
	 * @return mixed
	 */
	public function storeConfiguration(
		$config,
		?string $version,
		Authority $authority,
		string $summary = ''
	);

	/**
	 * Authorize a given authority to edit the configuration.
	 *
	 * This method offers a fast, lightweight check,
	 * It is intended for determining which UI elements should be offered to the user.
	 * It provides a check similar to Authority::probablyCan
	 *
	 * @param Authority $authority The authority whose permissions are to be checked.
	 * @return bool True if the authority is authorized to edit the configuration, otherwise false.
	 */
	public function probablyCanEdit( Authority $authority ): bool;

	/**
	 * Authorize a given authority to edit the configuration.
	 *
	 * This method performs a thorough check. It is intended to be used when a user is intending to
	 * edit the configuration, but has not yet committed to it. It provides a check similar to Authority::definitelyCan
	 *
	 * @param Authority $authority The authority whose permissions are to be checked.
	 * @return bool True if the authority is authorized to edit the configuration, otherwise false.
	 */
	public function definitelyCanEdit( Authority $authority ): bool;
}

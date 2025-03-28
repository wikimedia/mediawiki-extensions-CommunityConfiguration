<?php

namespace MediaWiki\Extension\CommunityConfiguration\Provider;

use MediaWiki\Permissions\Authority;
use StatusValue;

interface IVersionedConfigurationProvider extends IConfigurationProvider {

	/**
	 * Load (possibly cached) configuration that is guaranteed to be valid.
	 * If the configuration was stored with a previous version of the schema,
	 * then it will be converted to the latest schema version before being returned.
	 *
	 * @return StatusValue if OK, loaded configuration is passed as a value
	 */
	public function loadValidConfigurationConvertedToLatest(): StatusValue;

	/**
	 * Load (possibly cached) configuration that is guaranteed to be valid
	 * If the configuration was stored with a previous version of the schema,
	 * then it will be returned as that version.
	 *
	 * @return StatusValue if OK, loaded configuration is passed as a value
	 */
	public function loadValidConfigurationUnconverted(): StatusValue;

	/**
	 * Load (uncached) configuration that is guaranteed to be valid
	 * If the configuration was stored with a previous version of the schema,
	 * then it will be converted to the latest schema version before being returned.
	 *
	 * @return StatusValue if OK, loaded configuration is passed as a value
	 */
	public function loadValidConfigurationUncachedConvertedToLatest(): StatusValue;

	/**
	 * Load (uncached) configuration that is guaranteed to be valid
	 * If the configuration was stored with a previous version of the schema,
	 * then it will be returned as that version.
	 *
	 * @return StatusValue
	 */
	public function loadValidConfigurationUncachedUnconverted(): StatusValue;

	/**
	 * Store configuration while guaranteeing it is valid
	 *
	 * The method checks for editing permissions.
	 *
	 * @param mixed $newConfig The configuration value to store. Can be any JSON serializable type
	 * @param Authority $authority
	 * @param string $summary Edit summary
	 * @param string|null $version The schema version to store. If null, the latest version will be used if available.
	 * @return StatusValue
	 * @see IConfigurationProvider::alwaysStoreValidConfiguration(), which skips the permissions
	 * check.
	 */
	public function storeValidConfiguration(
		$newConfig,
		Authority $authority,
		string $summary = '',
		?string $version = null
	): StatusValue;

	/**
	 * Store configuration while guaranteeing it is valid
	 *
	 * The method SKIPS any permission checks, and leaves them as the caller's responsibility.
	 * Use storeValidConfiguration() if that is not what you want.
	 *
	 * @see IConfigurationProvider::storeValidConfiguration()
	 * @param mixed $newConfig The configuration value to store. Can be any JSON serializable type.
	 * @param Authority $authority
	 * @param string $summary
	 * @param ?string $version Schema version
	 * @return StatusValue
	 */
	public function alwaysStoreValidConfiguration(
		$newConfig,
		Authority $authority,
		string $summary = '',
		?string $version = null
	): StatusValue;

}

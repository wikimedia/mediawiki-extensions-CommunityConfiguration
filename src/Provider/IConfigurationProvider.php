<?php

namespace MediaWiki\Extension\CommunityConfiguration\Provider;

use MediaWiki\Extension\CommunityConfiguration\Storage\IConfigurationStore;
use MediaWiki\Extension\CommunityConfiguration\Validation\IValidator;
use StatusValue;

interface IConfigurationProvider {

	/**
	 * Get the associated configuration store
	 *
	 * @return IConfigurationStore
	 */
	public function getStore(): IConfigurationStore;

	/**
	 * Get the associated validator
	 *
	 * @return IValidator
	 */
	public function getValidator(): IValidator;

	/**
	 * Load configuration that is guaranteed to be valid
	 *
	 * @return StatusValue if OK, loaded configuration is passed as a value
	 */
	public function loadValidConfiguration(): StatusValue;
}
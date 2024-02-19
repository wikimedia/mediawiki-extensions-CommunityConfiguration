<?php

namespace MediaWiki\Extension\CommunityConfiguration\Store;

use MediaWiki\Permissions\Authority;
use StatusValue;

class StaticStore implements IConfigurationStore {

	private ?string $configLocation;

	/**
	 * @param string|null $configLocation
	 */
	public function __construct( ?string $configLocation ) {
		$this->configLocation = $configLocation;
	}

	/**
	 * @inheritDoc
	 */
	public function loadConfigurationUncached(): StatusValue {
		return StatusValue::newGood( [
			'FooBar' => 42,
		] );
	}

	/**
	 * @inheritDoc
	 */
	public function loadConfiguration(): StatusValue {
		return $this->loadConfigurationUncached();
	}

	/**
	 * @inheritDoc
	 */
	public function storeConfiguration(
		array $config,
		Authority $authority,
		string $summary = ''
	): StatusValue {
		// TODO: add a proper i18n message
		return StatusValue::newFatal( 'no-writes' );
	}

	/**
	 * @inheritDoc
	 */
	public function invalidate(): void {
	}
}

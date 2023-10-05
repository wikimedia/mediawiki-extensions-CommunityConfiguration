<?php

namespace MediaWiki\Extension\CommunityConfiguration\Storage;

use MediaWiki\Config\Config;
use StatusValue;


class StaticStorage implements IConfigurationStore {

	private ?string $configLocation;
	private Config $mainConfig;
	private string $name;

	/**
	 * @param string|null $configLocation
	 */
	public function __construct( Config $mainConfig, string $name, ?string $configLocation ) {
		$this->configLocation = $configLocation;
		$this->mainConfig = $mainConfig;
		$this->name = $name;
	}

	/**
	 * @inheritDoc
	 */
	public function getConfigurationLocation(): ?string {
		return $this->configLocation;
	}

	/**
	 * @inheritDoc
	 */
	public function loadConfigurationUncached(): array {
		return [
			$this->name => $this->mainConfig->get( $this->name )
		];
	}

	/**
	 * @inheritDoc
	 */
	public function storeConfiguration( array $config ): StatusValue {
		// TODO: add a proper i18n message
		return StatusValue::newFatal( 'no-writes' );
	}
}

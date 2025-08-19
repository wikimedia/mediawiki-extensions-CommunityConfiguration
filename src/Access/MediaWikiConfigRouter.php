<?php

namespace MediaWiki\Extension\CommunityConfiguration\Access;

use MediaWiki\Config\Config;
use MediaWiki\Config\ConfigException;

/**
 * Allows to read any configuration, no matter if it belongs to CommunityConfiguration's own
 * "CommunityConfiguration.MediaWikiConfigReader" or core's "MainConfig" service.
 */
class MediaWikiConfigRouter implements Config {

	private MediaWikiConfigReader $configReader;
	private Config $mainConfig;

	public function __construct(
		MediaWikiConfigReader $configReader,
		Config $mainConfig
	) {
		$this->configReader = $configReader;
		$this->mainConfig = $mainConfig;
	}

	/**
	 * @inheritDoc
	 */
	public function get( $name ) {
		if ( $this->configReader->has( $name ) ) {
			return $this->configReader->get( $name );
		} elseif ( $this->mainConfig->has( $name ) ) {
			return $this->mainConfig->get( $name );
		} else {
			throw new ConfigException( __METHOD__ . ": undefined option: '$name'" );
		}
	}

	/**
	 * @inheritDoc
	 */
	public function has( $name ) {
		return $this->configReader->has( $name ) || $this->mainConfig->has( $name );
	}
}

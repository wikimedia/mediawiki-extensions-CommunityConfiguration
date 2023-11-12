<?php

namespace MediaWiki\Extension\CommunityConfiguration\Provider;

use ConfigException;
use MediaWiki\Config\Config;

class KeyValueConfigurationProvider
	extends DataConfigurationProvider
	implements IConfigurationProvider, Config {

	private function getValidConfigOrNothing(): array {
		$status = $this->loadValidConfiguration();
		if ( !$status->isOK() ) {
			// TODO: Log error
			return [];
		}

		return $status->getValue();
	}

	/**
	 * @inheritDoc
	 */
	public function get( $name ) {
		if ( !$this->has( $name ) ) {
			throw new ConfigException( 'Key ' . $name . ' was not found.' );
		}

		return $this->getValidConfigOrNothing()[$name];
	}

	/**
	 * @inheritDoc
	 */
	public function has( $name ) {
		if ( !in_array( $name, $this->getValidator()->getSupportedTopLevelKeys() ) ) {
			// This config value is not supported
			return false;
		}

		return array_key_exists( $name, $this->getValidConfigOrNothing() );
	}
}

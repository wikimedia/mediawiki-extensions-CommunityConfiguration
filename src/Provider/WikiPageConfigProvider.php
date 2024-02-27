<?php

namespace MediaWiki\Extension\CommunityConfiguration\Provider;

use ConfigException;
use MediaWiki\Config\Config;

class WikiPageConfigProvider
	extends DataProvider
	implements IConfigurationProvider, Config
{

	private function getValidConfigOrNothing(): array {
		$status = $this->loadValidConfiguration();
		if ( !$status->isOK() ) {
			$this->logger->error(
				'CommunityConfiguration provider ' . $this->getName() . ' failed to load; '
				. 'stored configuration is not valid.'
			);
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
		// FIXME: IValidator::getSupportedTopLevelKeys() is not implemented yet and always
		// returns an empty string.
		// @phan-suppress-next-line PhanImpossibleCondition
		if ( false && !in_array( $name, $this->getValidator()->getSupportedTopLevelKeys() ) ) {
			// This config value is not supported
			return false;
		}

		return array_key_exists( $name, $this->getValidConfigOrNothing() );
	}
}

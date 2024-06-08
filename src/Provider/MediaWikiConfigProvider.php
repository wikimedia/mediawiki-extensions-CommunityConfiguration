<?php

namespace MediaWiki\Extension\CommunityConfiguration\Provider;

use MediaWiki\Config\Config;
use MediaWiki\Config\ConfigException;
use stdClass;

class MediaWikiConfigProvider
	extends DataProvider
	implements IConfigurationProvider, Config
{

	/**
	 * @return stdClass
	 */
	private function getValidConfigOrDefaults(): stdClass {
		$status = $this->loadValidConfiguration();
		if ( !$status->isOK() ) {
			$this->logger->error(
				'CommunityConfiguration provider ' . $this->getId() . ' failed to load; '
				. 'stored configuration is not valid.'
			);

			return $this->getValidator()->areSchemasSupported()
				? $this->getValidator()->getSchemaBuilder()->getDefaultsMap()
				: new stdClass();
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

		return $this->getValidConfigOrDefaults()->{$name};
	}

	/**
	 * Get a list of supported config variables
	 *
	 * @return array|null Null if all variables are supported
	 */
	public function getSupportedConfigVariableNames(): ?array {
		if ( !$this->getValidator()->areSchemasSupported() ) {
			return null;
		}

		return array_keys( $this->getValidator()->getSchemaBuilder()->getRootProperties() );
	}

	/**
	 * @inheritDoc
	 */
	public function has( $name ) {
		$allowlist = $this->getSupportedConfigVariableNames();
		if ( $allowlist && !in_array( $name, $allowlist ) ) {
			// This config value is not supported
			return false;
		}

		return property_exists( $this->getValidConfigOrDefaults(), $name );
	}
}

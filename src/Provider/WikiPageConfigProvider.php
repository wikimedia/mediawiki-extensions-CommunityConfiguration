<?php

namespace MediaWiki\Extension\CommunityConfiguration\Provider;

use ConfigException;
use MediaWiki\Config\Config;
use stdClass;

class WikiPageConfigProvider
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
				'CommunityConfiguration provider ' . $this->getName() . ' failed to load; '
				. 'stored configuration is not valid.'
			);

			return $this->getValidator()->getSchemaBuilder()->getDefaultsMap();
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
		$schemaBuilder = $this->getValidator()->getSchemaBuilder();
		if ( $schemaBuilder === null ) {
			return null;
		}

		return array_keys( $schemaBuilder->getRootProperties() );
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

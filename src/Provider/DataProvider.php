<?php

namespace MediaWiki\Extension\CommunityConfiguration\Provider;

use MediaWiki\Permissions\Authority;
use StatusValue;
use stdClass;

class DataProvider extends AbstractProvider {

	/**
	 * Process a StatusValue returned from IConfigurationStore
	 *
	 * This ensures the configuration in $storeStatus is valid.
	 *
	 * @param stdClass $config
	 * @return StatusValue
	 */
	private function validateConfiguration( stdClass $config ): StatusValue {
		$validationStatus = $this->getValidator()->validate( $config );
		if ( !$validationStatus->isOK() ) {
			return $validationStatus;
		}

		return StatusValue::newGood( $config );
	}

	/**
	 * Process a store status
	 *
	 * Common logic for both loadValidConfiguration() and loadValidConfigurationUncached().
	 *
	 * This function:
	 *     (1) Enhances config with defaults
	 *     (2) Validates the configuration against the schema
	 *
	 * @param StatusValue $storeStatus Result of IConfigurationStore::loadConfiguration(Uncached)
	 * @return StatusValue
	 */
	private function processStoreStatus( StatusValue $storeStatus ): StatusValue {
		if ( !$storeStatus->isOK() ) {
			return $storeStatus;
		}

		$config = $storeStatus->getValue();

		// enhance $config with defaults (if possible)
		if ( $this->getValidator()->areSchemasSupported() ) {
			$defaultsMap = $this->getValidator()->getSchemaBuilder()->getDefaultsMap();
			foreach ( $defaultsMap as $propertyName => $defaultValue ) {
				if ( $defaultValue !== null && !isset( $config->$propertyName ) ) {
					$config->$propertyName = $defaultValue;
				}
			}
		}

		return $this->validateConfiguration( $config );
	}

	/**
	 * @inheritDoc
	 */
	public function loadValidConfiguration(): StatusValue {
		return $this->processStoreStatus( $this->getStore()->loadConfiguration() );
	}

	/**
	 * @inheritDoc
	 */
	public function loadValidConfigurationUncached(): StatusValue {
		return $this->processStoreStatus( $this->getStore()->loadConfigurationUncached() );
	}

	/**
	 * @inheritDoc
	 */
	public function storeValidConfiguration(
		$newConfig,
		Authority $authority,
		string $summary = ''
	): StatusValue {
		$validationStatus = $this->getValidator()->validate( $newConfig );
		if ( !$validationStatus->isOK() ) {
			return $validationStatus;
		}

		return $this->getStore()->storeConfiguration(
			$newConfig,
			$authority,
			$summary
		);
	}
}

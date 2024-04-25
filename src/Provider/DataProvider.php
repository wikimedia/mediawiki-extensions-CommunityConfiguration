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
		$validationStatus = $this->getValidator()->validatePermissively( $config );
		if ( !$validationStatus->isOK() ) {
			return $validationStatus;
		}

		return $validationStatus->setResult( true, $config );
	}

	private function enhanceConfigPreValidation( stdClass $config ): stdClass {
		// enhance $config with defaults (if possible)
		if ( $this->getValidator()->areSchemasSupported() ) {
			$defaultsMap = $this->getValidator()->getSchemaBuilder()->getDefaultsMap();
			foreach ( $defaultsMap as $propertyName => $defaultValue ) {
				if ( $defaultValue !== null && !isset( $config->$propertyName ) ) {
					$config->$propertyName = $defaultValue;
				}
			}
		}

		return $config;
	}

	protected function addAutocomputedProperties( stdClass $config ): stdClass {
		return $config;
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

		$result = $this->validateConfiguration(
			$this->enhanceConfigPreValidation( $storeStatus->getValue() )
		);
		if ( !$result->isOK() ) {
			// an issue occurred, return the StatusValue
			return $result;
		}

		return $result->setResult( true, $this->addAutocomputedProperties( $result->getValue() ) );
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
		$validationStatus = $this->getValidator()->validateStrictly( $newConfig );
		if ( !$validationStatus->isGood() ) {
			return $validationStatus;
		}

		return $this->storeConfiguration(
			$newConfig,
			$authority,
			$summary
		);
	}
}

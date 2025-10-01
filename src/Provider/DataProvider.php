<?php

declare( strict_types = 1 );

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

	private function overrideDefaultsWithConfigRecursive( stdClass $defaults, stdClass $config ): stdClass {
		$merged = clone $defaults;

		foreach ( $config as $configPropertyName => $configPropertyValue ) {
			if ( property_exists( $merged, $configPropertyName ) ) {
				if ( is_object( $merged->$configPropertyName ) && is_object( $configPropertyValue ) ) {
					$merged->$configPropertyName = $this->overrideDefaultsWithConfigRecursive(
						$merged->$configPropertyName,
						$configPropertyValue
					);
				} else {
					// REVIEW: In particular, this uses any existing config value for an array as is.
					// It does not apply defaults to fields in the array elements.
					// Not even when those elements are objects.
					$merged->$configPropertyName = $configPropertyValue;
				}
			} else {
				$merged->$configPropertyName = $configPropertyValue;
			}
		}

		return $merged;
	}

	private function enhanceConfigPreValidation( stdClass $config ): stdClass {
		// enhance $config with defaults (if possible)
		if ( !$this->getValidator()->areSchemasSupported() ) {
			return $config;
		}

		$defaultsMap = $this->getValidator()->getSchemaBuilder()->getDefaultsMap();

		$config = $this->overrideDefaultsWithConfigRecursive( $defaultsMap, $config );

		return $config;
	}

	protected function addAutocomputedProperties( stdClass $config ): stdClass {
		return $config;
	}

	/**
	 * Normalize config to objects
	 *
	 * This method is a no-op if schemas are not supported. It is responsible for ensuring that
	 * arrays passed for type `object` are converted into `object` via PHP typecasting.
	 *
	 * @param stdClass $config
	 * @return stdClass
	 */
	private function normalizeConfigToObjects( stdClass $config ): stdClass {
		if ( !$this->getValidator()->areSchemasSupported() ) {
			return $config;
		}

		$schemaProperties = $this->getValidator()->getSchemaBuilder()->getRootProperties();

		foreach ( $config as $configPropertyName => &$configPropertyValue ) {
			if ( !isset( $schemaProperties[ $configPropertyName ] ) ) {
				continue;
			}

			if ( $schemaProperties[ $configPropertyName ][ 'type' ] === 'object' ) {
				if ( is_array( $configPropertyValue ) ) {
					$configPropertyValue = (object)$configPropertyValue;
				}
			}
		}

		return $config;
	}

	/** @inheritDoc */
	public function loadValidConfiguration(): StatusValue {
		return $this->processStoreStatus( $this->getStore()->loadConfiguration() );
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

		$normalizedConfiguration = $this->normalizeConfigToObjects( $storeStatus->getValue() );
		$result = $this->validateConfiguration(
			$this->enhanceConfigPreValidation( $normalizedConfiguration )
		);
		if ( !$result->isOK() ) {
			// an issue occurred, return the StatusValue
			return $result;
		}

		return $result->setResult( true, $this->addAutocomputedProperties( $result->getValue() ) );
	}

	/** @inheritDoc */
	public function loadValidConfigurationUncached(): StatusValue {
		return $this->processStoreStatus( $this->getStore()->loadConfigurationUncached() );
	}

	/**
	 * @param mixed $newConfig The configuration value; can be anything JSON-serializable
	 * @return stdClass Normalized object
	 */
	private function normalizeConfigDataBeforeStore( $newConfig ): stdClass {
		// Normalize the top level field first to avoid T379094
		$newConfig = $this->normalizeConfigToObjects( (object)$newConfig );

		// Sort config alphabetically
		$configSorted = (array)$newConfig;
		ksort( $configSorted );
		return (object)$configSorted;
	}

	/** @inheritDoc */
	public function storeValidConfiguration(
		$newConfig, Authority $authority, string $summary = ''
	): StatusValue {
		$normalizedConfig = $this->normalizeConfigDataBeforeStore( $newConfig );
		return parent::storeValidConfiguration( $normalizedConfig, $authority, $summary );
	}

	/** @inheritDoc */
	public function alwaysStoreValidConfiguration(
		$newConfig, Authority $authority, string $summary = ''
	): StatusValue {
		$normalizedConfig = $this->normalizeConfigDataBeforeStore( $newConfig );
		return parent::alwaysStoreValidConfiguration( $normalizedConfig, $authority, $summary );
	}
}

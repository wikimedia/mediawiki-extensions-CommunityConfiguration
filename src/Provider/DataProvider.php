<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\CommunityConfiguration\Provider;

use MediaWiki\Json\FormatJson;
use MediaWiki\Permissions\Authority;
use Psr\Log\LogLevel;
use ReflectionException;
use StatusValue;
use stdClass;

class DataProvider extends AbstractProvider implements IVersionedConfigurationProvider {

	/**
	 * @var StatusValue|null In process cache to ensure the computations happen only once per
	 * request.
	 */
	private ?StatusValue $configInProcessCache = null;

	/**
	 * Process a StatusValue returned from IConfigurationStore
	 *
	 * This ensures the configuration in $storeStatus is valid.
	 *
	 * @param stdClass $config
	 * @param string|null $version
	 * @return StatusValue
	 */
	private function validateConfiguration( stdClass $config, ?string $version ): StatusValue {
		$validationStatus = $this->getValidator()->validatePermissively( $config, $version );
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

	private function enhanceConfigPreValidation( stdClass $config, ?string $version ): stdClass {
		// enhance $config with defaults (if possible)
		if ( !$this->getValidator()->areSchemasSupported() ) {
			return $config;
		}
		try {
			$defaultsMap = $this->getValidator()->getSchemaBuilder()->getDefaultsMap( $version );
		} catch ( ReflectionException ) {
			// No Schema class for $version, so there are no defaults to apply. Leave $config
			// alone and let validateConfiguration() report the unknown version: validating
			// against $version fails with communityconfiguration-invalid-schema-version, so
			// the un-enhanced $config is discarded rather than used.
			return $config;
		}

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

	private function copyStatusValue( StatusValue $status ): StatusValue {
		// Note this is a shallow copy, value needs to be copied separately.
		$copy = clone $status;
		if ( $copy->isOK() && $copy->getValue() ) {
			// Copy the value, so each caller gets its own object. A caller that changes
			// the config must not pollute the cache. See T364101.
			$copy->setResult(
				true,
				FormatJson::decode( FormatJson::encode( $copy->getValue() ) )
			);
		}
		return $copy;
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
	 * @param string|null $version The schema version $storeStatus was stored under, read from
	 * the very same load. Null validates against the latest version.
	 * @return StatusValue
	 */
	private function processStoreStatus( StatusValue $storeStatus, ?string $version ): StatusValue {
		if ( !$storeStatus->isOK() ) {
			$this->logger->error( ...$this->getProviderServicesContainer()
				->getStatusFormatter()
				->getPsr3MessageAndContext( $storeStatus, [
					'providerId' => $this->getId(),
				] ) );
			return $storeStatus;
		}

		$normalizedConfiguration = $this->normalizeConfigToObjects( $storeStatus->getValue() );
		$result = $this->validateConfiguration(
			$this->enhanceConfigPreValidation( $normalizedConfiguration, $version ),
			$version
		);
		if ( !$result->isOK() ) {
			$this->logger->log(
				$this->getOptionValue( IConfigurationProvider::OPTION_READ_VALIDATION_LOG_LEVEL ) ?? LogLevel::DEBUG,
				...$this->getProviderServicesContainer()
					->getStatusFormatter()
					->getPsr3MessageAndContext( $result, [
						'providerId' => $this->getId(),
					] )
			);

			// an issue occurred, return the StatusValue
			return $result;
		}

		return $result->setResult( true, $this->addAutocomputedProperties( $result->getValue() ) );
	}

	/**
	 * @param mixed $newConfig The configuration value; can be anything JSON-serializable
	 * @return stdClass Normalized object
	 */
	private function normalizeConfigDataBeforeStore( mixed $newConfig ): stdClass {
		// Normalize the top level field first to avoid T379094
		$newConfig = $this->normalizeConfigToObjects( (object)$newConfig );

		// Sort config alphabetically
		$configSorted = (array)$newConfig;
		ksort( $configSorted );
		return (object)$configSorted;
	}

	/** @inheritDoc */
	public function storeValidConfiguration(
		$newConfig,
		Authority $authority,
		string $summary = '',
		?string $version = null
	): StatusValue {
		$normalizedConfig = $this->normalizeConfigDataBeforeStore( $newConfig );
		return parent::storeValidConfiguration( $normalizedConfig, $authority, $summary, $version );
	}

	/** @inheritDoc */
	public function alwaysStoreValidConfiguration(
		$newConfig,
		Authority $authority,
		string $summary = '',
		?string $version = null
	): StatusValue {
		$normalizedConfig = $this->normalizeConfigDataBeforeStore( $newConfig );
		return parent::alwaysStoreValidConfiguration( $normalizedConfig, $authority, $summary, $version );
	}

	/**
	 * Give subclasses a last chance to adjust a loaded configuration before it is returned
	 *
	 * Applied to the result of every loadValid*() method, after the configuration has been
	 * validated and, where applicable, converted to the most recent schema version. It is not
	 * applied on the write path.
	 *
	 * An override may change the type of the value, not just its contents:
	 * GrowthExperiments' MentorListConfigProvider decodes it into an associative array rather
	 * than a stdClass. Callers that hand a loaded configuration back to CommunityConfiguration
	 * must therefore not assume a stdClass; SchemaMigrator::convertDataToVersion() does, which
	 * is why migrating such a provider is not currently supported. See T369608.
	 *
	 * @stable to override
	 * @param StatusValue $status If OK, the loaded configuration is the value
	 * @return StatusValue
	 */
	protected function finalizeLoadedStatus( StatusValue $status ): StatusValue {
		return $status;
	}

	/**
	 * @inheritDoc
	 */
	public function loadValidConfigurationUnconverted(): StatusValue {
		return $this->finalizeLoadedStatus(
			$this->processVersionedStoreStatus( $this->getStore()->loadVersionedConfiguration() )
		);
	}

	/**
	 * @inheritDoc
	 */
	public function loadValidConfigurationConvertedToLatest(): StatusValue {
		if ( $this->configInProcessCache === null ) {
			$this->configInProcessCache = $this->processAndConvertStoreStatus(
				$this->getStore()->loadVersionedConfiguration()
			);
		}

		return $this->finalizeLoadedStatus(
			$this->copyStatusValue( $this->configInProcessCache )
		);
	}

	/**
	 * @inheritDoc
	 */
	public function loadValidConfiguration(): StatusValue {
		return $this->loadValidConfigurationConvertedToLatest();
	}

	/**
	 * @inheritDoc
	 */
	public function loadValidConfigurationUncachedUnconverted(): StatusValue {
		return $this->finalizeLoadedStatus(
			$this->processVersionedStoreStatus( $this->getStore()->loadVersionedConfigurationUncached() )
		);
	}

	/**
	 * @inheritDoc
	 */
	public function loadValidConfigurationUncachedConvertedToLatest(): StatusValue {
		$this->configInProcessCache = $this->processAndConvertStoreStatus(
			$this->getStore()->loadVersionedConfigurationUncached()
		);
		return $this->finalizeLoadedStatus(
			$this->copyStatusValue( $this->configInProcessCache )
		);
	}

	/**
	 * @inheritDoc
	 */
	public function loadValidConfigurationUncached(): StatusValue {
		return $this->loadValidConfigurationUncachedConvertedToLatest();
	}

	/**
	 * Process a versioned store status, validating against the schema version the data was
	 * stored under
	 *
	 * @param StatusValue $storeStatus Result of
	 * IConfigurationStore::loadVersionedConfiguration(Uncached)
	 * @return StatusValue
	 */
	private function processVersionedStoreStatus( StatusValue $storeStatus ): StatusValue {
		if ( !$storeStatus->isOK() ) {
			return $this->processStoreStatus( $storeStatus, null );
		}
		$versionedConfiguration = $storeStatus->getValue();
		return $this->processStoreStatus(
			$storeStatus->setResult( true, $versionedConfiguration->getData() ),
			$versionedConfiguration->getVersion()
		);
	}

	/**
	 * Process a versioned store status and convert it to the most recent schema version
	 *
	 * The configuration is processed at the version it was stored under, converted, and then
	 * processed again at the most recent version, because the converted data has to be
	 * validated against the schema it was converted to, and fields that the newer schema adds
	 * need their defaults.
	 *
	 * TODO (T371028): convert the stored data before processing it, so that processing happens
	 * once. Two passes means the defaults map is built twice and the configuration is validated
	 * twice on every read of an out-of-date configuration, which double-counts the
	 * JsonSchemaValidator_validate_seconds and JsonSchemaBuilder_getDefaultsMap_seconds timings
	 * for exactly the wikis that are mid-migration. It also hands the converter a configuration
	 * that already has its defaults merged in and addAutocomputedProperties() applied, so a
	 * converter cannot tell an unset field from one holding the old schema's default. Moving
	 * the conversion first changes what converters are given, so it wants its own change.
	 *
	 * @param StatusValue $storeStatus Result of
	 * IConfigurationStore::loadVersionedConfiguration(Uncached)
	 * @return StatusValue
	 */
	private function processAndConvertStoreStatus( StatusValue $storeStatus ): StatusValue {
		$versionOfStoredData = $storeStatus->isOK() ? $storeStatus->getValue()->getVersion() : null;
		$processedConfiguration = $this->processVersionedStoreStatus( $storeStatus );
		if ( !$processedConfiguration->isOK() ) {
			return $processedConfiguration;
		}
		if ( !$this->getValidator()->areSchemasSupported() ) {
			return $processedConfiguration;
		}

		$mostRecentVersion = $this->getValidator()->getSchemaVersion();
		if ( !$versionOfStoredData || !$mostRecentVersion || $versionOfStoredData === $mostRecentVersion ) {
			// was already loaded and validated with the most-recent Schema
			return $processedConfiguration;
		}

		$schemaMigrator = $this->getProviderServicesContainer()->getSchemaMigrator();
		$dataConvertedToLatest = $schemaMigrator->convertDataToVersion(
			$this->getValidator(),
			$processedConfiguration->getValue(),
			$versionOfStoredData,
			$mostRecentVersion
		);

		return $this->processStoreStatus( $dataConvertedToLatest, $mostRecentVersion );
	}

	/** @inheritDoc */
	public function invalidateCache(): void {
		parent::invalidateCache();
		$this->configInProcessCache = null;
	}
}

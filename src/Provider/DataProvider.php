<?php

namespace MediaWiki\Extension\CommunityConfiguration\Provider;

use MediaWiki\Extension\CommunityConfiguration\Store\IConfigurationStore;
use MediaWiki\Extension\CommunityConfiguration\Validation\IValidator;
use MediaWiki\Permissions\Authority;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use StatusValue;
use stdClass;

class DataProvider implements IConfigurationProvider {
	use LoggerAwareTrait;

	private string $providerName;
	private IConfigurationStore $store;
	private IValidator $validator;

	public function __construct(
		string $providerName,
		IConfigurationStore $store,
		IValidator $validator
	) {
		$this->providerName = $providerName;
		$this->store = $store;
		$this->validator = $validator;

		$this->setLogger( new NullLogger() );
	}

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return $this->providerName;
	}

	/**
	 * @inheritDoc
	 */
	public function getStore(): IConfigurationStore {
		return $this->store;
	}

	/**
	 * @inheritDoc
	 */
	public function getValidator(): IValidator {
		return $this->validator;
	}

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
		$schemaBuilder = $this->getValidator()->getSchemaBuilder();
		if ( $schemaBuilder ) {
			$defaultsMap = $schemaBuilder->getDefaultsMap();
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

		// TODO: Implement permission control here.

		return $this->getStore()->storeConfiguration(
			$newConfig,
			$authority,
			$summary
		);
	}
}

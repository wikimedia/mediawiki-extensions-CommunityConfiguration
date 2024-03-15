<?php

namespace MediaWiki\Extension\CommunityConfiguration\Provider;

use MediaWiki\Extension\CommunityConfiguration\Store\IConfigurationStore;
use MediaWiki\Extension\CommunityConfiguration\Validation\IValidator;
use MediaWiki\Permissions\Authority;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use StatusValue;

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
	 * @param StatusValue $storeStatus Status coming from IConfigurationStore::loadConfiguration(Uncached).
	 * @return StatusValue
	 */
	private function validateConfiguration( StatusValue $storeStatus ): StatusValue {
		if ( !$storeStatus->isOK() ) {
			return $storeStatus;
		}

		$config = $storeStatus->getValue();
		$validationStatus = $this->getValidator()->validate( $config );
		if ( !$validationStatus->isOK() ) {
			return $validationStatus;
		}

		return StatusValue::newGood( $config );
	}

	/**
	 * @inheritDoc
	 */
	public function loadValidConfiguration(): StatusValue {
		return $this->validateConfiguration( $this->getStore()->loadConfiguration() );
	}

	/**
	 * @inheritDoc
	 */
	public function loadValidConfigurationUncached(): StatusValue {
		return $this->validateConfiguration( $this->getStore()->loadConfigurationUncached() );
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

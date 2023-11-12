<?php

namespace MediaWiki\Extension\CommunityConfiguration\Provider;

use MediaWiki\Extension\CommunityConfiguration\Store\IConfigurationStore;
use MediaWiki\Extension\CommunityConfiguration\Validation\IValidator;
use StatusValue;

class DataConfigurationProvider implements IConfigurationProvider {

	private IConfigurationStore $store;
	private IValidator $validator;

	public function __construct( IConfigurationStore $store, IValidator $validator ) {
		$this->store = $store;
		$this->validator = $validator;
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
	 * @inheritDoc
	 */
	public function loadValidConfiguration(): StatusValue {
		$configStatus = $this->getStore()->loadConfigurationUncached();
		if ( !$configStatus->isOK() ) {
			return $configStatus;
		}

		$config = $configStatus->getValue();
		$validationStatus = $this->getValidator()->validate( $config );
		if ( !$validationStatus->isOK() ) {
			return $validationStatus;
		}

		return StatusValue::newGood( $config );
	}
}

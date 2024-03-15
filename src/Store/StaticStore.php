<?php

namespace MediaWiki\Extension\CommunityConfiguration\Store;

use LogicException;
use MediaWiki\Permissions\Authority;
use StatusValue;

class StaticStore implements IConfigurationStore {

	private array $config;

	/**
	 * @param mixed $config
	 */
	public function __construct( $config ) {
		$this->config = $config;
	}

	/**
	 * @inheritDoc
	 */
	public function loadConfigurationUncached(): StatusValue {
		return StatusValue::newGood( $this->config );
	}

	/**
	 * @inheritDoc
	 */
	public function loadConfiguration(): StatusValue {
		return $this->loadConfigurationUncached();
	}

	/**
	 * @inheritDoc
	 * @return never
	 */
	public function storeConfiguration(
		$config,
		Authority $authority,
		string $summary = ''
	): StatusValue {
		throw new LogicException( 'Static store cannot be edited' );
	}

	/**
	 * @inheritDoc
	 */
	public function invalidate(): void {
	}
}

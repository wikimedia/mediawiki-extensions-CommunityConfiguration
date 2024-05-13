<?php

namespace MediaWiki\Extension\CommunityConfiguration\Store;

use LogicException;
use MediaWiki\Linker\LinkTarget;
use MediaWiki\Permissions\Authority;
use StatusValue;
use stdClass;

class StaticStore implements IConfigurationStore {

	private stdClass $config;

	/**
	 * @param stdClass $config
	 */
	public function __construct( stdClass $config ) {
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

	/**
	 * @inheritDoc
	 */
	public function getInfoPageLinkTarget(): ?LinkTarget {
		return null;
	}
}

<?php

namespace MediaWiki\Extension\CommunityConfiguration\Store;

use LogicException;
use MediaWiki\Linker\LinkTarget;
use MediaWiki\Permissions\Authority;
use StatusValue;
use stdClass;

class StaticStore extends AbstractStore {

	private stdClass $config;

	/**
	 * @param stdClass|array $config
	 */
	public function __construct( $config ) {
		if ( !is_object( $config ) ) {
			$config = (object)json_decode( json_encode( $config ) );
		}
		$this->config = $config;
	}

	public function getVersion(): ?string {
		return null;
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
	public function alwaysStoreConfiguration(
		$config,
		?string $version,
		Authority $authority,
		string $summary = ''
	): StatusValue {
		throw new LogicException( 'Static store cannot be edited' );
	}

	/**
	 * @inheritDoc
	 * @return never
	 */
	public function storeConfiguration(
		$config,
		?string $version,
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

	/**
	 * @inheritDoc
	 */
	public function probablyCanEdit( Authority $authority ): bool {
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function definitelyCanEdit( Authority $authority ): bool {
		return false;
	}
}

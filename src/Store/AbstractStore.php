<?php

namespace MediaWiki\Extension\CommunityConfiguration\Store;

use StatusValue;

abstract class AbstractStore implements IConfigurationStore {

	private array $options = [];

	/**
	 * @inheritDoc
	 *
	 * Stores that keep a schema version alongside the configuration must override this to
	 * report it, reading both from the same load. The default assumes no version data.
	 */
	public function loadVersionedConfiguration(): StatusValue {
		return self::newVersionedConfigurationStatus( $this->loadConfiguration(), null );
	}

	/**
	 * @inheritDoc
	 *
	 * @see AbstractStore::loadVersionedConfiguration()
	 */
	public function loadVersionedConfigurationUncached(): StatusValue {
		return self::newVersionedConfigurationStatus( $this->loadConfigurationUncached(), null );
	}

	/**
	 * Wrap the result of a load in a VersionedConfiguration, preserving failures as-is
	 *
	 * @param StatusValue $status As returned by IConfigurationStore::loadConfiguration(Uncached)
	 * @param string|null $version The schema version read from the very same load
	 * @return StatusValue
	 */
	protected static function newVersionedConfigurationStatus(
		StatusValue $status,
		?string $version,
	): StatusValue {
		if ( !$status->isOK() ) {
			return $status;
		}
		return $status->setResult(
			true,
			new VersionedConfiguration( $status->getValue(), $version )
		);
	}

	/**
	 * @inheritDoc
	 */
	public function setOptions( array $options ): void {
		$this->options = $options;
	}

	/**
	 * Get a store option
	 *
	 * Options can be modified via setOptions()
	 *
	 * @see IConfigurationStore::setOptions()
	 * @param string $key
	 * @return mixed Option value or null if not found
	 */
	protected function getOption( string $key ) {
		return $this->options[$key] ?? null;
	}
}

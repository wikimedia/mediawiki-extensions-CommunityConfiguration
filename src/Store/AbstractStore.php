<?php

namespace MediaWiki\Extension\CommunityConfiguration\Store;

abstract class AbstractStore implements IConfigurationStore {

	private array $options = [];

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

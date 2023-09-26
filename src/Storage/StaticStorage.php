<?php

namespace MediaWiki\Extension\CommunityConfiguration\Storage;

use StatusValue;

class StaticStorage implements IConfigurationStore {

	/**
	 * @inheritDoc
	 */
	public function loadConfigurationUncached(): array {
		return [
			'wgFooBar' => 42,
		];
	}

	/**
	 * @inheritDoc
	 */
	public function storeConfiguration( array $config ): StatusValue {
		// TODO: add a proper i18n message
		return StatusValue::newFatal( 'no-writes' );
	}
}

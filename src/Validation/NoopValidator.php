<?php

namespace MediaWiki\Extension\CommunityConfiguration\Validation;

use StatusValue;

class NoopValidator implements IValidator {

	/**
	 * @inheritDoc
	 */
	public function validate( array $config ): StatusValue {
		return StatusValue::newGood();
	}

	public function getSupportedTopLevelKeys(): array {
		return [];
	}
}

<?php

namespace MediaWiki\Extension\CommunityConfiguration\Tests;

use MediaWiki\Extension\CommunityConfiguration\Schema\JsonSchema;

// phpcs:disable Generic.NamingConventions.UpperCaseConstantName.ClassConstantNotUpperCase
class JsonSchemaForTestingNewerVersion extends JsonSchema {

	public const VERSION = '1.0.1';

	public const Number = [
		JsonSchema::TYPE => JsonSchema::TYPE_NUMBER,
	];
}

<?php

namespace MediaWiki\Extension\CommunityConfiguration\Tests;

use MediaWiki\Extension\CommunityConfiguration\Schema\JsonSchema;

// phpcs:disable Generic.NamingConventions.UpperCaseConstantName.ClassConstantNotUpperCase
class JsonSchemaForTesting implements JsonSchema {

	public const Foo = [
		JsonSchema::TYPE => JsonSchema::TYPE_NUMBER,
	];
}

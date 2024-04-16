<?php

namespace MediaWiki\Extension\CommunityConfiguration\Tests;

use MediaWiki\Extension\CommunityConfiguration\Schema\JsonSchema;

// phpcs:disable Generic.NamingConventions.UpperCaseConstantName.ClassConstantNotUpperCase
class JsonSchemaForTesting extends JsonSchema {

	public const Foo = [
		JsonSchema::TYPE => JsonSchema::TYPE_NUMBER,
	];

	public const NumberWithDefault = [
		JsonSchema::TYPE => JsonSchema::TYPE_NUMBER,
		JsonSchema::DEFAULT => 0,
	];
}

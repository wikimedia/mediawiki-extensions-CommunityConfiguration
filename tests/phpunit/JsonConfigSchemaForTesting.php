<?php

namespace MediaWiki\Extension\CommunityConfiguration\Tests;

use MediaWiki\Extension\CommunityConfiguration\Schema\JsonSchema;

// phpcs:disable Generic.NamingConventions.UpperCaseConstantName.ClassConstantNotUpperCase
class JsonConfigSchemaForTesting extends JsonSchema {

	public const VERSION = '1.0.0';

	public const FeatureEnabled = [
		JsonSchema::TYPE => JsonSchema::TYPE_BOOLEAN,
		JsonSchema::DEFAULT => false,
	];

	public const FeatureActivationRegex = [
		JsonSchema::TYPE => JsonSchema::TYPE_STRING,
	];
}

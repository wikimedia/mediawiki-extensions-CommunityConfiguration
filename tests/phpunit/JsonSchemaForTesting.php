<?php

namespace MediaWiki\Extension\CommunityConfiguration\Tests;

use MediaWiki\Extension\CommunityConfiguration\Schema\JsonSchema;

// phpcs:disable Generic.NamingConventions.UpperCaseConstantName.ClassConstantNotUpperCase
class JsonSchemaForTesting extends JsonSchema {

	public const NumberWithDefault = [
		JsonSchema::TYPE => JsonSchema::TYPE_NUMBER,
		JsonSchema::DEFAULT => 0,
	];

	public const Mentors = [
		self::TYPE => self::TYPE_OBJECT,
		'patternProperties' => [
			'^[0-9]+$' => [
				self::TYPE => self::TYPE_OBJECT,
				self::PROPERTIES => [
					'username' => [
						self::TYPE => self::TYPE_STRING,
					],
					'message' => [
						self::TYPE => [ self::TYPE_STRING, 'null' ],
					],
				],
				self::ADDITIONAL_PROPERTIES => false,
			],
		],
		self::DEFAULT => [],
		self::ADDITIONAL_PROPERTIES => false,
	];
}

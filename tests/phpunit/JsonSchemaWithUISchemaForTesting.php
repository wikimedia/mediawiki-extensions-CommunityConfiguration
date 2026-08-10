<?php

namespace MediaWiki\Extension\CommunityConfiguration\Tests;

/**
 * A schema that declares a companion UI schema.
 *
 * The properties are the same as JsonSchemaForTesting, so that a test can compare the two and
 * show that UI_SCHEMA has no effect on the emitted JSON schema.
 */
class JsonSchemaWithUISchemaForTesting extends JsonSchemaForTesting {
	public const UI_SCHEMA = UISchemaForTesting::class;
}

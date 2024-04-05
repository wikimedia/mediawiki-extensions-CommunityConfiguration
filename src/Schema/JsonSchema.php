<?php

namespace MediaWiki\Extension\CommunityConfiguration\Schema;

/**
 * @note This is only a class to let extending classes override (some) constants defined within
 * this class.
 */
abstract class JsonSchema {

	/**
	 * @var string Default schema standard
	 *
	 * JsonSchemaBuilder supports overriding this, but it might not support future versions of
	 * JSONSchema.
	 */
	public const SCHEMA_URI = 'https://json-schema.org/draft-04/schema#';

	/**
	 * @var string Version of the schema
	 * @stable to override
	 *
	 * This is included in the $id field of the schema. Feel free to override in implementations.
	 */
	public const VERSION = '1.0.0';

	public const PROPERTIES = 'properties';
	public const TYPE = 'type';
	public const ADDITIONAL_PROPERTIES = 'additionalProperties';
	public const REF = '$ref';
	public const DEFS = '$defs';

	/**
	 * @var string To define a default value for the property
	 *
	 * Only supported on the root-level. Defaults for objects need to be defined at their root
	 * level.
	 */
	public const DEFAULT = 'default';

	public const TYPE_OBJECT = 'object';
	public const TYPE_STRING = 'string';
	public const TYPE_NUMBER = 'number';
	public const TYPE_BOOLEAN = 'boolean';
}

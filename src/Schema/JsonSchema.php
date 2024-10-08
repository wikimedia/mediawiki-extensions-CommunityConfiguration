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
	public const JSON_SCHEMA_VERSION = 'https://json-schema.org/draft-04/schema#';

	/**
	 * @var string|null Version of the schema (or null if versions are not used)
	 * @stable to override
	 *
	 * This is included in the $id field of the schema. Feel free to override in implementations.
	 */
	public const VERSION = null;

	/**
	 * @var string|null Classname of a ISchemaConverter
	 * @stable to override
	 *
	 * @see ISchemaConverter
	 */
	public const SCHEMA_CONVERTER = null;

	/**
	 * @var string|null A version of the next schema version (or null, when not
	 * available/supported).
	 * @stable to override
	 *
	 * Versions need to be comparable via PHP's version_compare.
	 */
	public const SCHEMA_NEXT_VERSION = null;

	/**
	 * @var string|null A version of the previous schema version (or null, when not
	 * available/supported).
	 * @stable to override
	 *
	 * Versions need to be comparable via PHP's version_compare.
	 */
	public const SCHEMA_PREVIOUS_VERSION = null;

	/*
	 * JSON-schema draft-04 supported vocabulary
	 */
	public const PROPERTIES = 'properties';
	public const TYPE = 'type';
	public const ADDITIONAL_PROPERTIES = 'additionalProperties';
	public const REF = '$ref';
	public const DEFS = '$defs';
	public const ITEMS = 'items';
	public const ENUM = 'enum';
	// Validation vocabulary
	public const REQUIRED = 'required';
	// integer and number types
	public const MINIMUM = 'minimum';
	public const MAXIMUM = 'maximum';
	public const MULTIPLE_OF = 'multipleOf';
	// string type
	public const MIN_LENGTH = 'minLength';
	public const MAX_LENGTH = 'maxLength';
	public const FORMAT = 'format';
	public const PATTERN = 'pattern';
	// array type
	public const MIN_ITEMS = 'minItems';
	public const MAX_ITEMS = 'maxItems';

	/**
	 * @var string To define a default value for the property
	 *
	 * Only supported on the root-level. Defaults for objects need to be defined at their root
	 * level.
	 */
	public const DEFAULT = 'default';

	public const DYNAMIC_DEFAULT = 'dynamicDefault';

	public const TYPE_OBJECT = 'object';
	public const TYPE_ARRAY = 'array';
	public const TYPE_STRING = 'string';
	public const TYPE_INTEGER = 'integer';
	public const TYPE_NUMBER = 'number';
	public const TYPE_BOOLEAN = 'boolean';

	/**
	 * Non-standard vocabulary.
	 */
	public const CONTROL = 'control';
}

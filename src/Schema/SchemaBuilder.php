<?php

namespace MediaWiki\Extension\CommunityConfiguration\Schema;

use stdClass;

/**
 * An implementation of the SchemaBuilder interface is capable of building a schema governing the
 * configuration.
 */
interface SchemaBuilder {

	/**
	 * Get the schema as a PHP associative array
	 *
	 * This method looks at the associated PHP class and builds the PHP associative
	 * array to represent it directly.
	 *
	 * @param string|null $version Schema version to use (null for newest)
	 * @return array
	 */
	public function getRootSchema( ?string $version = null ): array;

	/**
	 * Return a list of properties supported by the schema (null for newest)
	 *
	 * @param string|null $version Schema version to use
	 * @return array Map of property name => schema (that describes just that said property).
	 * Precise format of the schema is implementation-defined.
	 */
	public function getRootProperties( ?string $version = null ): array;

	/**
	 * Return default values for root-level properties
	 *
	 * @param string|null $version Schema version to use (null for newest)
	 * @return stdClass
	 */
	public function getDefaultsMap( ?string $version = null ): stdClass;
}

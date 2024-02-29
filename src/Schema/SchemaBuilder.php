<?php

namespace MediaWiki\Extension\CommunityConfiguration\Schema;

interface SchemaBuilder {

	/**
	 * Get the schema as a PHP associative array
	 *
	 * This method looks at the associated PHP class and builds the PHP associative
	 * array to represent it directly.
	 *
	 * @return array
	 */
	public function getRootSchema(): array;

	/**
	 * Return a list of properties supported by the schema
	 *
	 * @return string[] Map of property name => schema (that describes just that said property).
	 * Precise format of the schema is implementation-defined.
	 */
	public function getRootProperties(): array;
}

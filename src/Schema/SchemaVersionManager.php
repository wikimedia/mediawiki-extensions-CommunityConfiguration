<?php

namespace MediaWiki\Extension\CommunityConfiguration\Schema;

use InvalidArgumentException;

/**
 * A schema version manager is able to construct a SchemaReader for any version of a schema
 */
interface SchemaVersionManager {

	/**
	 * Get a schema reader
	 *
	 * @param string $version
	 * @throws InvalidArgumentException when $version is invalid
	 * @return SchemaReader
	 */
	public function getVersionForSchema( string $version ): SchemaReader;
}

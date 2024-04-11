<?php

namespace MediaWiki\Extension\CommunityConfiguration\Schema;

use InvalidArgumentException;

/**
 * Class capable of reading a schema from a file (represented as a PHP class, or otherwise).
 * Implementations remember which schema they are reading
 */
interface SchemaReader {

	/**
	 * Are we pointed at a schema?
	 *
	 * Answer is based on the data passed through the constructor.
	 *
	 * @see SchemaReader::assertIsSchema()
	 * @return bool
	 */
	public function isSchema(): bool;

	/**
	 * Throw an exception if not pointed at a schema
	 *
	 * @throws InvalidArgumentException when not processing a schema (isSchema returns false);
	 * this is based on data passed through the constructor.
	 * @see SchemaReader::isSchema()
	 * @return void
	 */
	public function assertIsSchema(): void;

	/**
	 * Get the schema version (if applicable)
	 *
	 * Returned versions should be comparable via PHP's version_compare.
	 *
	 * @return string|null Version (or null if unavailable)
	 */
	public function getVersion(): ?string;

	/**
	 * Get the next schema version (if available)
	 *
	 * Returned versions should be comparable via PHP's version_compare.
	 *
	 * @return string|null Version (or null if not available)
	 */
	public function getNextVersion(): ?string;

	/**
	 * Get the previous schema version (if available)
	 *
	 * Returned versions should be comparable via PHP's version_compare.
	 *
	 * @return string|null Version (or null if not available)
	 */
	public function getPreviousVersion(): ?string;

	/**
	 * Get a schema ID
	 *
	 * A schema ID uniquely identifies a certain schema.
	 *
	 * @return string
	 */
	public function getSchemaId(): string;

	/**
	 * Return a schema converter
	 *
	 * A schema converter is able to convert data to conform
	 * to this schema version, using data conforming to the immediately preceding
	 * (or succeeding) schema versions.
	 *
	 * @see ISchemaConverter
	 * @return string|null Classname of an ISchemaConverter implementation
	 */
	public function getSchemaConverterId(): ?string;
}

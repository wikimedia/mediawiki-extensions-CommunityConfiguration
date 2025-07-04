<?php

namespace MediaWiki\Extension\CommunityConfiguration\Validation;

use Iterator;
use LogicException;
use MediaWiki\Extension\CommunityConfiguration\Schema\SchemaBuilder;

/**
 * Validator that can validate a config page
 *
 * @note ValidatorFactory constructs validators in a service-like way (construct it once and then
 * keep the instance for all future calls). Do not keep state in your class when implementing a
 * validator.
 */
interface IValidator {

	/**
	 * Validate passed config strictly
	 *
	 * All validations errors from the library will make this validation fail.
	 *
	 * This is executed by WikiPageConfigWriter _before_ writing a config (for edits made
	 * via GrowthExperiments-provided interface), and by ConfigHooks for manual edits.
	 *
	 * @param mixed $config Associative array representing config that's going to be validated
	 * @param string|null $version Version (of the schema) to use for validation, null for latest or if not supported.
	 *                             Implementations SHOULD return a fatal ValidationStatus for invalid versions as they
	 *                             might be user-provided.
	 * @return ValidationStatus
	 */
	public function validateStrictly( $config, ?string $version = null ): ValidationStatus;

	/**
	 * Validate passed config permissively
	 *
	 * This will not return a fatal StatusValue if required attributes are missing or if there are extra attributes,
	 * but it will add warnings instead.
	 * It will still return a fatal StatusValue for all other types of errors,
	 * for example if a value is of the wrong type.
	 *
	 * This is used by WikiPageConfigLoader before returning the config (this is to ensure invalid config is never used)
	 *
	 * When writing a config, use @see validateStrictly() instead of this.
	 *
	 * @param mixed $config Associative array representing config that's going to be validated
	 * @param string|null $version Version (of the schema) to use for validation, null for latest or if not supported.
	 *                             Implementations SHOULD return a fatal ValidationStatus for invalid versions as they
	 *                             might be user-provided.
	 * @return ValidationStatus
	 */
	public function validatePermissively( $config, ?string $version = null ): ValidationStatus;

	/**
	 * Are configuration schemas supported?
	 *
	 * @return bool
	 */
	public function areSchemasSupported(): bool;

	/**
	 * Return a SchemaBuilder object
	 *
	 * Callers need to check areSchemasSupported() returns true first.
	 *
	 * @return SchemaBuilder
	 * @throws LogicException if called when areSchemasSupported() returns false
	 */
	public function getSchemaBuilder(): SchemaBuilder;

	/**
	 * Return a schema Iterator that allows all internal
	 * sub-schemas to be iterated.
	 *
	 * Callers need to check areSchemasSupported() returns true first.
	 *
	 * @return Iterator
	 * @throws LogicException if called when areSchemasSupported() returns false
	 */
	public function getSchemaIterator(): Iterator;

	/**
	 * Return current version for the schema
	 *
	 * Only safe to call when areSchemasSupported() returns true.
	 *
	 * @return string|null
	 */
	public function getSchemaVersion(): ?string;
}

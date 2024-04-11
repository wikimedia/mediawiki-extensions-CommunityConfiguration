<?php

namespace MediaWiki\Extension\CommunityConfiguration\Validation;

use LogicException;
use MediaWiki\Extension\CommunityConfiguration\Schema\SchemaBuilder;
use StatusValue;

/**
 * Validator that can validate a config page
 *
 * @note ValidatorFactory constructs validators in a service-like way (construct it once and then
 * keep the instance for all future calls). Do not keep state in your class when implementing a
 * validator.
 */
interface IValidator {
	/**
	 * Validate passed config
	 *
	 * This is executed by WikiPageConfigWriter _before_ writing a config (for edits made
	 * via GrowthExperiments-provided interface), by ConfigHooks for manual edits and
	 * by WikiPageConfigLoader before returning the config (this is to ensure invalid config
	 * is never used).
	 *
	 * @param mixed $config Associative array representing config that's going to be validated
	 * @return StatusValue
	 */
	public function validate( $config ): StatusValue;

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
}

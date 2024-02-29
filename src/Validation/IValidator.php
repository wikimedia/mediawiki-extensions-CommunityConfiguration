<?php

namespace MediaWiki\Extension\CommunityConfiguration\Validation;

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
	 * @param array $config Associative array representing config that's going to be validated
	 * @return StatusValue
	 */
	public function validate( array $config ): StatusValue;

	/**
	 * Return a SchemaBuilder object or null for no schema support
	 *
	 * @return SchemaBuilder|null
	 */
	public function getSchemaBuilder(): ?SchemaBuilder;
}

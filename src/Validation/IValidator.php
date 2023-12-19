<?php

namespace MediaWiki\Extension\CommunityConfiguration\Validation;

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
	 * Return list of supported top level keys
	 *
	 * This is useful for IConfigurationProvider implementations; this information can be used to
	 * decide whether a certain configuration request asks for an information that can be
	 * present (but is missing from the store at the moment), or whether the information cannot
	 * exist at all (and thus the request is invalid). Example is deciding whether Config::get
	 * should throw, or return a default value.
	 *
	 * @return array List of top level keys names
	 */
	public function getSupportedTopLevelKeys(): array;


	/**
	 * Return a SchemaLoader object or null for no schema support
	 *
	 */
	public function getSchemaLoader(): ?SchemaLoader;
}

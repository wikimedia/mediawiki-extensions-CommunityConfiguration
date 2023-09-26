<?php

namespace MediaWiki\Extension\CommunityConfiguration\Provider;

use MediaWiki\Extension\CommunityConfiguration\Storage\IConfigurationStore;
use MediaWiki\Extension\CommunityConfiguration\Validation\IValidator;
use StatusValue;

/**
 * This is the main point of interaction with a community configuration. Each provider is an
 * implementation of IConfigurationProvider, with a constructor accepting (1) an
 * IConfigurationStore, (2) an IValidator and (3) arbitrary number of other services or arguments
 * (services are passed first; after services, arguments are passed). In other words, this is a
 * valid constructor signature:
 *
 *     public function __construct( IConfigurationProvider, IValidator, FooService, bool );
 *
 * Supported configuration providers are defined via $wgCommunityConfigurationProviders, which is
 * a dictionary keyed by provider name; each item must have the following properties:
 *
 *     * storage: name of the configuration storage, or a {"type": "name", "args": [...]} dict if
 *       the storage's constructor needs arguments.
 *     * validator: name of the validator or a  {"type": "name", "args": [...]} dict if the
 *       validator's constructor needs arguments.
 *     * type: fully-qualified class name (must implement IConfigurationProvider)
 *
 * and may have the following properties:
 *     * services: names of services that should be passed to the provider.
 *     * args: if present, has to be an array of arguments (arguments are passed to __construct
 *       after all services).
 */
interface IConfigurationProvider {

	/**
	 * Get the associated configuration store
	 *
	 * @return IConfigurationStore
	 */
	public function getStore(): IConfigurationStore;

	/**
	 * Get the associated validator
	 *
	 * @return IValidator
	 */
	public function getValidator(): IValidator;

	/**
	 * Load configuration that is guaranteed to be valid
	 *
	 * @return StatusValue if OK, loaded configuration is passed as a value
	 */
	public function loadValidConfiguration(): StatusValue;
}
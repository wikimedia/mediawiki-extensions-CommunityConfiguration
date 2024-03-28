<?php

namespace MediaWiki\Extension\CommunityConfiguration\Provider;

use MediaWiki\Extension\CommunityConfiguration\Store\IConfigurationStore;
use MediaWiki\Extension\CommunityConfiguration\Validation\IValidator;
use MediaWiki\Permissions\Authority;
use Psr\Log\LoggerAwareInterface;
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
 *     * store: name of the configuration store, or a {"type": "name", "args": [...]} dict if
 *       the store's constructor needs arguments.
 *     * validator: name of the validator or a  {"type": "name", "args": [...]} dict if the
 *       validator's constructor needs arguments.
 *     * type: fully-qualified class name (must implement IConfigurationProvider)
 *
 * and may have the following properties:
 *     * services: names of services that should be passed to the provider.
 *     * args: if present, has to be an array of arguments (arguments are passed to __construct
 *       after all services).
 */
interface IConfigurationProvider extends LoggerAwareInterface {

	/**
	 * Get a provider's ID (key under which it is defined)
	 *
	 * This is intended for logging outputs, to make it possible to determine which provider
	 * caused a given log message, so that the issue can be debugged and fixed.
	 *
	 * @return string
	 */
	public function getId(): string;

	/**
	 * Get the associated configuration store
	 *
	 * @note Store provides direct access to wherever the configuration is stored. No validation
	 * or access control is done at the store level. Use loadValidConfiguration() and
	 * storeValidConfiguration() whenever possible.
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
	 * Load (possibly cached) configuration that is guaranteed to be valid
	 *
	 * @return StatusValue if OK, loaded configuration is passed as a value
	 */
	public function loadValidConfiguration(): StatusValue;

	/**
	 * Load (uncached) configuration that is guaranteed to be valid
	 *
	 * @return StatusValue if OK, loaded configuration is passed as a value
	 */
	public function loadValidConfigurationUncached(): StatusValue;

	/**
	 * Store configuration while guaranteeing
	 *
	 * @param mixed $newConfig The configuration value to store. Can be any JSON serializable type
	 * @param Authority $authority
	 * @param string $summary
	 * @return StatusValue
	 */
	public function storeValidConfiguration(
		$newConfig,
		Authority $authority,
		string $summary = ''
	): StatusValue;
}

<?php

namespace MediaWiki\Extension\CommunityConfiguration\Validation;

use InvalidArgumentException;
use MediaWiki\Config\ServiceOptions;
use Wikimedia\ObjectFactory\ObjectFactory;

/**
 * Create a configuration validation object
 *
 * Configuration returned by an IConfigurationStore needs to be validated. This factory is
 * responsible for constructing an IValidator that can validate a given config file.
 *
 * Configuration of available validators is included in $wgCommunityConfigurationValidators,
 * which can look like this (dict of ObjectFactory specs keyed by validator name):
 *
 * {
 *     "noop": {
 *         "class": "MediaWiki\\Extension\\CommunityConfiguration\\Validation\\NoopValidator",
 *         "services": []
 *     },
 *     "jsonschema": {
 *         "class": "MediaWiki\\Extension\\CommunityConfiguration\\Validation\\JsonSchemaValidator",
 *         "services": []
 *     }
 * }
 */
class ValidatorFactory {

	/**
	 * @var string[]
	 * @internal for use in ServiceWiring only
	 */
	public const CONSTRUCTOR_OPTIONS = [
		'CommunityConfigurationValidators',
	];

	/** @var array ObjectFactory specs for validators, indexed by validator name */
	private array $validatorSpecs;
	/** @var IValidator[] validators indexed by name */
	private array $validators = [];
	private ObjectFactory $objectFactory;

	/**
	 * @param ServiceOptions $options
	 * @param ObjectFactory $objectFactory
	 */
	public function __construct(
		ServiceOptions $options,
		ObjectFactory $objectFactory
	) {
		$options->assertRequiredOptions( self::CONSTRUCTOR_OPTIONS );
		$this->validatorSpecs = $options->get( 'CommunityConfigurationValidators' );

		$this->objectFactory = $objectFactory;
	}

	/**
	 * Construct a new validator
	 *
	 * @param string $name Validator key (from CommunityConfigurationValidators)
	 * @param array $validatorArgs
	 * @return IValidator
	 */
	public function newValidator( string $name, array $validatorArgs ): IValidator {
		if ( !array_key_exists( $name, $this->validatorSpecs ) ) {
			throw new InvalidArgumentException( "Validator $name is not supported" );
		}
		if ( !array_key_exists( $name, $this->validators ) ) {
			$this->validators[$name] = $this->objectFactory->createObject(
				$this->validatorSpecs[$name],
				[
					'assertClass' => IValidator::class,
					'extraArgs' => $validatorArgs,
				],
			);
		}
		return $this->validators[$name];
	}

	/**
	 * Return a list of supported validators
	 *
	 * @return string[] List of validator names (supported by newValidator)
	 */
	public function getSupportedKeys(): array {
		return array_keys( $this->validatorSpecs );
	}
}

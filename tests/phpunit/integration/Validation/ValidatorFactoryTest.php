<?php

namespace MediaWiki\Extension\CommunityConfiguration\Tests;

use InvalidArgumentException;
use MediaWiki\Extension\CommunityConfiguration\CommunityConfigurationServices;
use MediaWiki\Extension\CommunityConfiguration\Validation\IValidator;
use MediaWikiIntegrationTestCase;

/**
 * @covers \MediaWiki\Extension\CommunityConfiguration\Validation\ValidatorFactory
 */
class ValidatorFactoryTest extends MediaWikiIntegrationTestCase {

	private ?array $validatorSpecs = null;

	private const VALIDATOR_EXTRA_ARGS = [
		'jsonschema' => [ JsonSchemaForTesting::class ],
	];

	private function getValidatorSpecs(): array {
		if ( !$this->validatorSpecs ) {
			$this->validatorSpecs = $this->getServiceContainer()->getMainConfig()
				->get( 'CommunityConfigurationValidators' );
		}
		return $this->validatorSpecs;
	}

	public function testConstructValidator() {
		$factory = CommunityConfigurationServices::wrap( $this->getServiceContainer() )
			->getValidatorFactory();

		foreach ( $this->getValidatorSpecs() as $validatorName => $_ ) {
			$this->assertInstanceOf(
				IValidator::class,
				$factory->newValidator(
					$validatorName, $validatorName,
					self::VALIDATOR_EXTRA_ARGS[$validatorName] ?? []
				)
			);

			// Ensure multiple validators of the same name can be instanciated
			$this->assertInstanceOf(
				IValidator::class,
				$factory->newValidator(
					$validatorName . '-second', $validatorName,
					self::VALIDATOR_EXTRA_ARGS[$validatorName] ?? []
				)
			);
		}
	}

	public function testNonexistentValidator() {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Validator nonexistent is not supported' );

		CommunityConfigurationServices::wrap( $this->getServiceContainer() )
			->getValidatorFactory()
			->newValidator( 'nonexistent', 'nonexistent', [] );
	}

	/**
	 * Test that core validators are always available while allowing for extension-registered validators.
	 *
	 * 1. All core validators from CommunityConfigurationValidators are present
	 * 2. Additional validators from extensions are allowed
	 */
	public function testSupportedKeys() {
		$coreValidatorKeys = array_keys( $this->getValidatorSpecs() );

		// Get all supported validators (including extension-registered ones)
		$allValidatorKeys = CommunityConfigurationServices::wrap( $this->getServiceContainer() )
			->getValidatorFactory()
			->getSupportedKeys();

		sort( $coreValidatorKeys );
		sort( $allValidatorKeys );

		foreach ( $coreValidatorKeys as $coreValidator ) {
			$this->assertContains(
				$coreValidator,
				$allValidatorKeys,
				sprintf(
					"Core validator '%s' must be present in supported keys. Found validators: %s",
					$coreValidator,
					implode( ', ', $allValidatorKeys )
				)
			);
		}
		$this->assertGreaterThanOrEqual(
			count( $coreValidatorKeys ),
			count( $allValidatorKeys ),
			'Total validator count should be at least equal to core validator count'
		);
	}
}

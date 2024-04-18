<?php

namespace MediaWiki\Extension\CommunityConfiguration\Tests;

use InvalidArgumentException;
use MediaWiki\Extension\CommunityConfiguration\CommunityConfigurationServices;
use MediaWiki\Extension\CommunityConfiguration\Validation\IValidator;
use MediaWikiIntegrationTestCase;

/**
 * @coversDefaultClass \MediaWiki\Extension\CommunityConfiguration\Validation\ValidatorFactory
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

	/**
	 * @covers ::newValidator
	 * @return void
	 */
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

	/**
	 * @covers ::newValidator
	 * @return void
	 */
	public function testNonexistentValidator() {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Validator nonexistent is not supported' );

		CommunityConfigurationServices::wrap( $this->getServiceContainer() )
			->getValidatorFactory()
			->newValidator( 'nonexistent', 'nonexistent', [] );
	}

	/**
	 * @covers ::getSupportedKeys
	 */
	public function testSupportedKeys() {
		$this->assertSame(
			array_keys( $this->getValidatorSpecs() ),
			CommunityConfigurationServices::wrap( $this->getServiceContainer() )
				->getValidatorFactory()
				->getSupportedKeys()
		);
	}
}

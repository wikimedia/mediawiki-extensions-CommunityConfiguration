<?php

namespace MediaWiki\Extension\CommunityConfiguration\Tests\Unit\Validation;

use InvalidArgumentException;
use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\CommunityConfiguration\Validation\JsonSchemaValidator;
use MediaWiki\Extension\CommunityConfiguration\Validation\NoopValidator;
use MediaWiki\Extension\CommunityConfiguration\Validation\ValidatorFactory;
use MediaWiki\MediaWikiServices;
use MediaWikiUnitTestCase;
use Wikimedia\ObjectFactory\ObjectFactory;

/**
 * @covers \MediaWiki\Extension\CommunityConfiguration\Validation\ValidatorFactory
 */
class ValidatorFactoryTest extends MediaWikiUnitTestCase {

	/**
	 * Creates a validator factory
	 *
	 * @param array $validatorSpecs Override default validator specifications
	 * @return ValidatorFactory
	 */
	private function getValidatorFactory( array $validatorSpecs = [] ): ValidatorFactory {
		// mirror extension.json
		$defaultSpecs = [
			'noop' => [
				'class' => NoopValidator::class,
				'services' => [],
			],
			'jsonschema' => [
				'class' => JsonSchemaValidator::class,
				'services' => [ 'StatsdDataFactory' ],
			],
		];

		$options = new ServiceOptions(
			ValidatorFactory::CONSTRUCTOR_OPTIONS,
			[ 'CommunityConfigurationValidators' => $validatorSpecs ?: $defaultSpecs ],
		);

		return new ValidatorFactory(
			$options,
			new ObjectFactory( $this->createNoOpMock( MediaWikiServices::class ) )
		);
	}

	public function testSupportedKeys() {
		// Test with core validators
		$factory = $this->getValidatorFactory();
		$this->assertEquals(
			[ 'noop', 'jsonschema' ],
			$factory->getSupportedKeys(),
			'Should support core validators'
		);

		// extension validator added
		$factory = $this->getValidatorFactory( [
			'noop' => [
				'class' => NoopValidator::class,
				'services' => [],
			],
			'jsonschema' => [
				'class' => JsonSchemaValidator::class,
				'services' => [ 'StatsdDataFactory' ],
			],
			'Foo' => [
				'class' => 'GrowthExperiments\Config\Validation\CommunityStructuredFooValidator',
			],
		] );

		// Core validators must be present
		foreach ( [ 'noop', 'jsonschema' ] as $coreValidator ) {
			$this->assertContains(
				$coreValidator,
				$factory->getSupportedKeys(),
				"Core validator '$coreValidator' must be present"
			);
		}

		// Extension validator should be included
		$this->assertContains(
			'Foo',
			$factory->getSupportedKeys(),
			'Extension validator should be registered'
		);
	}

	public function testUnknownValidator() {
		$factory = $this->getValidatorFactory();

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Validator nonexistent is not supported' );

		$factory->newValidator( 'nonexistent', 'nonexistent', [] );
	}
}

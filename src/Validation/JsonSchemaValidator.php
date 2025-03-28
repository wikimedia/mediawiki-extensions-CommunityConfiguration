<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\CommunityConfiguration\Validation;

use InvalidArgumentException;
use Iterator;
use JsonSchema\Validator;
use MediaWiki\Extension\CommunityConfiguration\Schema\JsonSchema;
use MediaWiki\Extension\CommunityConfiguration\Schema\JsonSchemaBuilder;
use MediaWiki\Extension\CommunityConfiguration\Schema\JsonSchemaIterator;
use MediaWiki\Extension\CommunityConfiguration\Schema\JsonSchemaReader;
use MediaWiki\Extension\CommunityConfiguration\Schema\SchemaBuilder;
use Wikimedia\Stats\IBufferingStatsdDataFactory;
use Wikimedia\Stats\StatsFactory;

class JsonSchemaValidator implements IValidator {

	private JsonSchemaReader $jsonSchema;
	private JsonSchemaBuilder $jsonSchemaBuilder;
	private Iterator $jsonSchemaIterator;
	private IBufferingStatsdDataFactory $statsdDataFactory;
	private StatsFactory $statsFactory;

	/**
	 * @param JsonSchema|string $classNameOrClassInstance JsonSchema derived class name (instance only allowed in tests)
	 * @param IBufferingStatsdDataFactory $statsdDataFactory
	 * @param StatsFactory $statsFactory
	 */
	public function __construct(
		$classNameOrClassInstance,
		IBufferingStatsdDataFactory $statsdDataFactory,
		StatsFactory $statsFactory
	) {
		// @codeCoverageIgnoreStart
		if ( is_object( $classNameOrClassInstance ) ) {
			if ( !defined( 'MW_PHPUNIT_TEST' ) ) {
				throw new InvalidArgumentException(
					'JsonSchema should never be instantiated in production code'
				);
			}
			if ( !( $classNameOrClassInstance instanceof JsonSchema ) ) {
				throw new InvalidArgumentException(
					get_class( $classNameOrClassInstance ) . ' must be instance of ' . JsonSchema::class
				);
			}
		}
		// @codeCoverageIgnoreEnd

		$this->jsonSchema = new JsonSchemaReader( $classNameOrClassInstance );
		$this->jsonSchemaBuilder = new JsonSchemaBuilder( $statsdDataFactory, $this->jsonSchema, $statsFactory );
		$this->jsonSchemaIterator = new JsonSchemaIterator( $this->jsonSchema );
		$this->statsdDataFactory = $statsdDataFactory;
		$this->statsFactory = $statsFactory;
	}

	/**
	 * @inheritDoc
	 */
	public function areSchemasSupported(): bool {
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function getSchemaBuilder(): SchemaBuilder {
		return $this->jsonSchemaBuilder;
	}

	/**
	 * @inheritDoc
	 */
	public function validateStrictly( $config ): ValidationStatus {
		return $this->validate( $config, false );
	}

	/**
	 * @inheritDoc
	 */
	public function validatePermissively( $config ): ValidationStatus {
		return $this->validate( $config, true );
	}

	/**
	 * @param mixed $config
	 * @param bool $modeForReading
	 * @return ValidationStatus
	 */
	private function validate( $config, bool $modeForReading ): ValidationStatus {
		$start = microtime( true );
		$timing = $this->statsFactory->withComponent( 'CommunityConfiguration' )->getTiming(
			'JsonSchemaValidator_validate_seconds'
		)->setLabel(
			'schema',
			str_replace( [ '/', '.' ], '_', $this->jsonSchema->getSchemaId() ),
		)->start();

		$validator = new Validator();
		$validator->validate(
			$config,
			$this->jsonSchemaBuilder->getRootSchema()
		);
		if ( $validator->isValid() ) {
			return ValidationStatus::newGood();
		}
		$status = new ValidationStatus();
		foreach ( $validator->getErrors() as $error ) {
			if ( $modeForReading && in_array( $error['constraint'], [ 'required', 'additionalProp', 'enum' ] ) ) {
				$status->addWarning(
					$error['property'],
					$error['pointer'],
					$error['message'],
					[ 'constraint' => $error['constraint'] ],
				);
			} else {
				$status->addFatal(
					$error['property'],
					$error['pointer'],
					$error['message'],
					[ 'constraint' => $error['constraint'] ],
				);
			}
		}

		$timing->stop();
		$this->statsdDataFactory->timing(
			'timing.communityConfiguration.JsonSchemaValidator.validate',
			microtime( true ) - $start
		);
		return $status;
	}

	/**
	 * @inheritDoc
	 */
	public function getSchemaVersion(): ?string {
		return $this->jsonSchema->getVersion();
	}

	public function getSchemaIterator(): Iterator {
		return $this->jsonSchemaIterator;
	}
}

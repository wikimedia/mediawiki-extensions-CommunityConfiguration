<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\CommunityConfiguration\Schema;

use LogicException;
use Wikimedia\ObjectFactory\ObjectFactory;

/**
 * Construct an ISchemaConverter for a specified version and schema.
 */
class SchemaConverterFactory {

	private ObjectFactory $objectFactory;

	public function __construct( ObjectFactory $objectFactory ) {
		$this->objectFactory = $objectFactory;
	}

	/**
	 * Construct a ISchemaConverter from a class name
	 */
	private function getSchemaConverterFromClass( ?string $className ): ?ISchemaConverter {
		if ( $className === null ) {
			return null;
		}

		// ObjectFactory::createObject supports arrays as its arguments, not just callables
		// Link to Phan bug: https://github.com/phan/phan/issues/1648
		// @phan-suppress-next-line PhanTypeInvalidCallableArraySize
		$res = $this->objectFactory->createObject( [
			'class' => $className,
		], [
			'assertClass' => ISchemaConverter::class,
		] );

		// NOTE: This is here to allow for type hints.
		if ( !$res instanceof ISchemaConverter ) {
			throw new LogicException( 'ObjectFactory\'s assertion is invalid' );
		}

		return $res;
	}

	/**
	 * Get an appropriate SchemaReader for given version
	 *
	 * @param SchemaBuilder $schemaBuilder
	 * @param string|null $version Version (null for latest)
	 * @return SchemaReader
	 */
	private function getSchemaReader(
		SchemaBuilder $schemaBuilder,
		?string $version = null
	): SchemaReader {
		if ( $version === null ) {
			return $schemaBuilder->getSchemaReader();
		}

		return $schemaBuilder->getVersionManager()
			->getVersionForSchema( $version );
	}

	/**
	 * Construct a schema converter for a particular version
	 *
	 * @param SchemaBuilder $schemaBuilder
	 * @param string|null $version Version (null for latest)
	 * @return ISchemaConverter|null
	 */
	public function getConverterFromVersion(
		SchemaBuilder $schemaBuilder,
		?string $version = null
	): ?ISchemaConverter {
		$schemaReader = $this->getSchemaReader( $schemaBuilder, $version );
		return $this->getSchemaConverterFromClass( $schemaReader->getSchemaConverterId() );
	}
}

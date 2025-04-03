<?php

namespace MediaWiki\Extension\CommunityConfiguration\Schema;

use stdClass;

class JsonSchemaIterator implements \Iterator {

	private JsonSchemaReader $schema;
	private int $position = 0;
	private array $data = [];
	private bool $initialized = false;

	public function __construct( JsonSchemaReader $schema ) {
		$this->schema = $schema;
	}

	private function initialize() {
		if ( !$this->initialized ) {
			// Load schema as components to omit processing the top level object "properties"
			$rootSchema = $this->schema->getReflectionSchemaSource()->loadAsSchema( true );
			$objectSchema = json_decode( json_encode( $rootSchema ) );
			$this->data = $this->buildDataFromSchema( $objectSchema );
			$this->initialized = true;
		}
	}

	/** @return array */
	#[\ReturnTypeWillChange]
	public function current() {
		$this->initialize();
		return $this->data[$this->position];
	}

	public function next(): void {
		$this->initialize();
		$this->position++;
	}

	public function key(): int {
		$this->initialize();
		return $this->position;
	}

	public function valid(): bool {
		$this->initialize();
		return isset( $this->data[$this->position] );
	}

	public function rewind(): void {
		$this->initialize();
		$this->position = 0;
	}

	/**
	 * Return all sub-schemas in the given schemas as an array
	 * while calculating a json pointer for each of them.
	 *
	 * @param stdClass $rootSchema A stdClass representing a full JSON schema
	 * @return array<array> An array with the relevant "objects" (represented as associative arrays in PHP) in
	 * the schema. That should be a single object per schema.
	 */
	private function buildDataFromSchema( stdClass $rootSchema ): array {
		$result = [];

		$stack = new \SplStack();
		$stack->push( [
			'parentType' => 'root',
			'schema' => $rootSchema,
			'pointer' => '#',
		] );

		while ( !$stack->isEmpty() ) {
			$schemaNode = $stack->pop();
			// Only inspect subschemas which inform a type
			if ( is_object( $schemaNode['schema'] ) && isset( $schemaNode['schema']->type ) ) {
				array_push( $result, $schemaNode );
			}

			foreach ( $schemaNode['schema'] as $propertyName => $propertyValue ) {
				if ( is_object( $propertyValue ) || is_array( $propertyValue ) ) {
					$stack->push( [
						'parentType' => $schemaNode['schema']->type ?? $schemaNode['parentType'],
						'schema' => $propertyValue,
						'pointer' => $schemaNode['pointer'] . '/' . $propertyName,
					] );
				}
			}
		}

		return $result;
	}
}

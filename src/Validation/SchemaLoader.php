<?php

namespace MediaWiki\Extension\CommunityConfiguration\Validation;

class SchemaLoader {

	// REVIEW refactor to array of schemas?
	private string $schema;
	private SchemaResolver $resolver;

	/**
	 * @param string $schema
	 */
	public function __construct( string $schema ) {
		$this->resolver = new SchemaResolver();
		$this->schema = $schema;
	}

	public function getPath(): string {
		return $this->resolver->resolvePath( $this->schema );
	}

	/**
	 * @return bool|mixed|object|null
	 */
	public function getSchema() {
		return $this->resolver->resolve( $this->schema );
	}
}

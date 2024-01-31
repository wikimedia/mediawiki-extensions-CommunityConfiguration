<?php

namespace MediaWiki\Extension\CommunityConfiguration\Validation;

class SchemaLoader {

	private string $schema;
	private SchemaResolver $resolver;

	/**
	 * @param string $schema
	 */
	public function __construct( string $schema ) {
		$this->resolver = new SchemaResolver();
		$this->schema = $schema;
	}

	public function getPath(): ?string {
		return $this->resolver->resolvePath( $this->schema );
	}

	/**
	 * @return array|null
	 */
	public function getSchema(): ?array {
		return $this->resolver->resolve( $this->schema );
	}
}

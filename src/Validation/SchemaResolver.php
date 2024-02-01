<?php

namespace MediaWiki\Extension\CommunityConfiguration\Validation;

class SchemaResolver {

	/**
	 * @param string $schema
	 * @return array|null
	 */
	public function resolve( string $schema ): ?array {
		$path = $this->resolvePath( $schema );

		if ( $path === null || !is_file( $path ) ) {
			return null;
		}

		$data = file_get_contents( $path );
		if ( !is_string( $data ) ) {
			return null;
		}

		$data = json_decode( $data, true );

		return $data;
	}

	/**
	 * @param string $schema
	 * @return string|null
	 */
	public function resolvePath( string $schema ): ?string {
		$path = $schema;
		// If the $schema definition does not start with "/" assume it is
		// relative to the extensions directory
		if ( !str_starts_with( $path, '/' ) ) {
			$path = $GLOBALS[ 'wgExtensionDirectory' ] . '/' . $path;
		}
		if ( !realpath( $path ) ) {
			return null;
		}
		return $path;
	}

}

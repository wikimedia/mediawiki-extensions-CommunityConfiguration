<?php

namespace MediaWiki\Extension\CommunityConfiguration\Validation;

class SchemaResolver {

	// TODO allow Uris as schema declarations?
	/**
	 * @param string $schema
	 * @return bool|mixed|object|null
	 */
	public function resolve( string $schema ) {
		$path = $this->resolvePath( $schema );

		if ( $path === null || !is_file( $path ) ) {
			return null;
		}

		$data = file_get_contents( $path );
		if ( !is_string( $data ) ) {
			return null;
		}

		$data = json_decode($data, false );

		return $data;
	}

	/**
	 * @param string $schema
	 * @return string|null
	 */
	protected function resolvePath( string $schema ): ?string {
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

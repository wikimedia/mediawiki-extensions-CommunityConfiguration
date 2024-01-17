<?php

namespace MediaWiki\Extension\CommunityConfiguration\Validation;

use http\Exception\RuntimeException;

class SchemaResolver {

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
	public function resolvePath( string $schema, string $targetDirName ): ?string {
		$currentDir = __DIR__;
		// avoid endless traversal if target directory is not found
		$maxDepth = 10;
		for ( $depth = 0; $depth < $maxDepth; $depth++ ) {
			if ( basename( $currentDir ) === $targetDirName ) {
				break;
			}
			$parentDir = dirname( $currentDir );
			if ( $parentDir === $currentDir ) {
				// Root directory reached but target directory not found
				throw new RuntimeException( "Unable to find the `{$targetDirName}` directory." );
			}
			$currentDir = $parentDir;
		}

		if ( basename( $currentDir ) !== $targetDirName ) {
			throw new RuntimeException(
				" `{$targetDirName}` directory not found within the maximum search depth." );
		}
		$path = realpath( $currentDir . '/' . ltrim( $schema, '/' ) );
		if ( $path === false || !is_file( $path ) ) {
			return null;
		}
		return $path;
	}

}

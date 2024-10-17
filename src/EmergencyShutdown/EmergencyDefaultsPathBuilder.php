<?php

namespace MediaWiki\Extension\CommunityConfiguration\EmergencyShutdown;

use InvalidArgumentException;
use MediaWiki\Extension\CommunityConfiguration\Provider\IConfigurationProvider;
use MediaWiki\Registration\ExtensionRegistry;

class EmergencyDefaultsPathBuilder {
	private const DEFAULT_DIRECTORY_NAME = 'CommunityConfigurationFallbacks';

	private ExtensionRegistry $registry;

	public function __construct( ExtensionRegistry $registry ) {
		$this->registry = $registry;
	}

	/**
	 * Get directory that contains emergency defaults for a given extension
	 *
	 * @throws InvalidArgumentException if $extension is not loaded
	 * @param string $extension Extension name (pass the same as passed to eg. wfLoadExtension)
	 * @return string Filesystem path to the directory
	 */
	public function getDefaultsDirectory( string $extension ): string {
		if ( !$this->registry->isLoaded( $extension ) ) {
			throw new InvalidArgumentException(
				'Extension ' . $extension . ' is not loaded'
			);
		}
		$extensionDir = dirname( $this->registry->getAllThings()[ $extension ]['path'] );
		// TODO: Make DEFAULT_DIRECTORY_NAME customizable
		return $extensionDir . DIRECTORY_SEPARATOR . self::DEFAULT_DIRECTORY_NAME;
	}

	/**
	 * Get file that contains emergency defaults for a given provider
	 *
	 * @throws InvalidArgumentException if $extension is not loaded
	 * @param IConfigurationProvider $provider
	 * @param string $extensionName Treat the provider as registered by $extensionName (pass
	 * extension name as passed to eg. wfLoadExtension)
	 * @return string Path to the file
	 */
	public function getDefaultsFileForProvider(
		IConfigurationProvider $provider,
		string $extensionName
	): string {
		return $this->getDefaultsDirectory( $extensionName ) . DIRECTORY_SEPARATOR .
			$provider->getId() . '.php';
	}
}

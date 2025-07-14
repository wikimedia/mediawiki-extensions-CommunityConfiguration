<?php

namespace MediaWiki\Extension\CommunityConfiguration\Maintenance;

use MediaWiki\Extension\CommunityConfiguration\CommunityConfigurationServices;
use MediaWiki\Extension\CommunityConfiguration\EmergencyShutdown\EmergencyDefaultsPathBuilder;
use MediaWiki\Extension\CommunityConfiguration\EmergencyShutdown\EmergencyDefaultsUpdater;
use MediaWiki\Extension\CommunityConfiguration\Provider\ConfigurationProviderFactory;
use MediaWiki\Maintenance\Maintenance;

// @codeCoverageIgnoreStart
$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
	$IP = __DIR__ . '/../../..';
}
require_once "$IP/maintenance/Maintenance.php";
// @codeCoverageIgnoreEnd

class UpdateEmergencyDefaults extends Maintenance {

	private EmergencyDefaultsPathBuilder $emergencyDefaultsPathBuilder;
	private EmergencyDefaultsUpdater $updater;
	private ConfigurationProviderFactory $providerFactory;

	public function __construct() {
		parent::__construct();
		$this->requireExtension( 'CommunityConfiguration' );

		$this->addDescription( 'Update emergency defaults.' );
		// REVIEW: Do we need to have extension as a mandatory user input?
		$this->addArg(
			'extension',
			'Name of the extension the provider is defined by '
			. ' (generated defaults will be saved to that extension\'s namespace)'
		);
		$this->addArg(
			'providerId',
			'ID of the provider to update'
		);
	}

	private function initServices() {
		$ccServices = CommunityConfigurationServices::wrap( $this->getServiceContainer() );
		$this->emergencyDefaultsPathBuilder = $ccServices->getEmergencyDefaultsPathBuilder();
		$this->updater = $ccServices->getEmergencyDefaultsUpdater();
		$this->providerFactory = $ccServices->getConfigurationProviderFactory();
	}

	/**
	 * @inheritDoc
	 */
	public function execute() {
		$this->initServices();
		$extensionName = $this->getArg( 'extension' );
		$providerId = $this->getArg( 'providerId' );
		if ( !$this->providerFactory->isProviderSupported( $providerId ) ) {
			$this->fatalError( "Provider $providerId is not supported" );
		}
		$provider = $this->providerFactory->newProvider( $providerId );

		$path = $this->emergencyDefaultsPathBuilder->getDefaultsFileForProvider( $provider, $extensionName );

		// Ensure the base directory exists
		$baseDir = dirname( $path );
		if ( !is_dir( $baseDir ) ) {
			mkdir( $baseDir, 0777, true );
		}

		file_put_contents(
			$path,
			$this->updater->getSerializedDefaults( $provider )
		);
		$this->output( "Updated emergency defaults for $providerId.\n" );
	}
}

// @codeCoverageIgnoreStart
$maintClass = UpdateEmergencyDefaults::class;
require RUN_MAINTENANCE_IF_MAIN;
// @codeCoverageIgnoreEnd

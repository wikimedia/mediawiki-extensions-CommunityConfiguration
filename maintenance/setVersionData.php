<?php

namespace MediaWiki\Extension\CommunityConfiguration\Maintenance;

use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\CommunityConfiguration\CommunityConfigurationServices;
use MediaWiki\Extension\CommunityConfiguration\Provider\ConfigurationProviderFactory;
use MediaWiki\Maintenance\Maintenance;
use MediaWiki\Permissions\UltimateAuthority;
use MediaWiki\Status\StatusFormatter;
use MediaWiki\User\User;

$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
	$IP = __DIR__ . '/../../..';
}
require_once "$IP/maintenance/Maintenance.php";

class SetVersionData extends Maintenance {

	private StatusFormatter $statusFormatter;
	private ConfigurationProviderFactory $providerFactory;

	public function __construct() {
		parent::__construct();
		$this->requireExtension( 'CommunityConfiguration' );

		$this->addDescription( 'Add version data to configuration that previously did not have it ' .
			'(useful when adding versioning to a schema that did not have it before)' );
		$this->addArg(
			'provider',
			'ID of the provider to change'
		);
		$this->addArg(
			'version',
			'Version of schema the provider adheres to'
		);
	}

	private function initServices(): void {
		$ccServices = CommunityConfigurationServices::wrap( $this->getServiceContainer() );

		$this->statusFormatter = $this->getServiceContainer()->getFormatterFactory()->getStatusFormatter(
			RequestContext::getMain()
		);
		$this->providerFactory = $ccServices->getConfigurationProviderFactory();
	}

	private function fatalStatus( \StatusValue $status, string $headline ) {
		$this->fatalError(
			"$headline.\n\n== Error details ==\n"
			. $this->statusFormatter->getWikiText( $status )
		);
	}

	/**
	 * @inheritDoc
	 */
	public function execute() {
		$this->initServices();

		$providerId = $this->getArg( 'provider' );
		if ( !$this->providerFactory->isProviderSupported( $providerId ) ) {
			$this->fatalError( 'Provider ' . $providerId . ' is not supported' );
		}
		$provider = $this->providerFactory->newProvider( $providerId );
		$version = $this->getArg( 'version' );

		if ( !$provider->getValidator()->areSchemasSupported() ) {
			$this->fatalError( 'Provider ' . $provider->getId() . ' does not support schemas' );
		}
		if ( $provider->getValidator()->getSchemaVersion() === null ) {
			$this->fatalError( 'Provider ' . $provider->getId() . ' does not support versions' );
		}

		$currentConfig = $provider->loadValidConfiguration();
		if ( !$currentConfig->isOK() ) {
			$this->fatalStatus( $currentConfig, 'Failed to load configuration' );
		}

		$status = $provider->getStore()->storeConfiguration(
			$currentConfig->getValue(),
			$version,
			new UltimateAuthority( User::newSystemUser( User::MAINTENANCE_SCRIPT_USER ) ),
			'Adding version data'
		);
		if ( !$status->isOK() ) {
			$this->fatalStatus( $status, 'Failed to add version data' );
		}

		$this->output( 'All done!' . PHP_EOL );
	}
}

$maintClass = SetVersionData::class;
require_once RUN_MAINTENANCE_IF_MAIN;

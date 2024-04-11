<?php

namespace MediaWiki\Extension\CommunityConfiguration\Maintenance;

use Maintenance;
use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\CommunityConfiguration\CommunityConfigurationServices;
use MediaWiki\Extension\CommunityConfiguration\Provider\ConfigurationProviderFactory;
use MediaWiki\Extension\CommunityConfiguration\Schema\SchemaMigrator;
use MediaWiki\Permissions\UltimateAuthority;
use MediaWiki\Status\StatusFormatter;
use MediaWiki\User\User;
use StatusValue;

$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
	$IP = __DIR__ . '/../../..';
}
require_once "$IP/maintenance/Maintenance.php";

class MigrateConfig extends Maintenance {

	private StatusFormatter $statusFormatter;
	private ConfigurationProviderFactory $providerFactory;
	private SchemaMigrator $schemaMigrator;

	public function __construct() {
		parent::__construct();
		$this->requireExtension( 'CommunityConfiguration' );

		$this->addDescription( 'Migrate configuration provider to a different version' );
		$this->addArg(
			'provider',
			'ID of the provider to upgrade'
		);
		$this->addOption(
			'version',
			'Version to upgrade to (defaults to newest available)',
			false,
			true
		);
		$this->addOption(
			'dry-run',
			'Do not actually save the edit'
		);
	}

	private function initServices(): void {
		$ccServices = CommunityConfigurationServices::wrap( $this->getServiceContainer() );
		$this->statusFormatter = $this->getServiceContainer()->getFormatterFactory()->getStatusFormatter(
			RequestContext::getMain()
		);
		$this->providerFactory = $ccServices->getConfigurationProviderFactory();
		$this->schemaMigrator = $ccServices->getSchemaMigrator();
	}

	private function fatalStatus( StatusValue $status, string $headline ) {
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

		$provider = $this->providerFactory->newProvider( $this->getArg( 'provider' ) );

		$validator = $provider->getValidator();
		if ( !$validator->areSchemasSupported() ) {
			$this->fatalError( 'Provider ' . $provider->getId() . ' does not support schemas.' );
		}

		$targetVersion = $this->getOption( 'version' ) ?? $validator->getSchemaVersion();

		// NOTE: We will be writing the data back; ensure we are not running into any cache
		// issues by purging it.
		$provider->getStore()->invalidate();
		$conversionStatus = $this->schemaMigrator->convertDataToVersion(
			$provider,
			$targetVersion
		);

		if ( !$conversionStatus->isOK() ) {
			$this->fatalStatus( $conversionStatus, 'Failed to convert the config data' );
		}

		if ( $this->hasOption( 'dry-run' ) ) {
			$this->output( 'Would save:' . PHP_EOL );
			$this->output( var_export( $conversionStatus->getValue(), true ) . PHP_EOL );
			return;
		}

		$status = $provider->storeValidConfiguration(
			$conversionStatus->getValue(),
			new UltimateAuthority( User::newSystemUser( User::MAINTENANCE_SCRIPT_USER ) ),
			'Migrating data to new format'
		);
		if ( !$status->isOK() ) {
			$this->fatalStatus( $status, 'Failed to update the store with new config' );
		}

		$this->output( 'All done!' . PHP_EOL );
	}
}

$maintClass = MigrateConfig::class;
require_once RUN_MAINTENANCE_IF_MAIN;

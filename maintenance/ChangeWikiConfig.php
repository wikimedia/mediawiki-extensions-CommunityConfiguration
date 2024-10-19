<?php

declare( strict_types=1 );

namespace MediaWiki\Extension\CommunityConfiguration\Maintenance;

use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\CommunityConfiguration\CommunityConfigurationServices;
use MediaWiki\Extension\CommunityConfiguration\Provider\ConfigurationProviderFactory;
use MediaWiki\Json\FormatJson;
use MediaWiki\Language\FormatterFactory;
use MediaWiki\Maintenance\Maintenance;
use MediaWiki\Maintenance\MaintenanceFatalError;
use MediaWiki\MediaWikiServices;
use MediaWiki\User\User;
use MessageLocalizer;
use stdClass;

$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
	$IP = __DIR__ . '/../../..';
}
require_once "$IP/maintenance/Maintenance.php";

class ChangeWikiConfig extends Maintenance {

	private ConfigurationProviderFactory $providerFactory;
	private FormatterFactory $formatterFactory;
	private MessageLocalizer $messageLocalizer;

	public function __construct() {
		parent::__construct();
		$this->requireExtension( 'CommunityConfiguration' );
		$this->addDescription( 'Update a config key in on-wiki config' );

		$this->addOption(
			'summary',
			'Edit summary to use',
			false,
			true
		);
		$this->addOption(
			'delete',
			'delete the key if it exists',
		);

		$this->addOption(
			'dry-run',
			'print the config that would be saved and exit',
		);

		$this->addArg(
			'provider',
			'Provider whose data that will be changed',
			true
		);
		$this->addArg(
			'key',
			'Config key that is updated (use . to separate keys in an object)',
			true
		);
		$this->addArg(
			'value',
			'New value of the config key, must be valid json',
			false
		);
	}

	private function initServices(): void {
		$services = MediaWikiServices::getInstance();
		$ccServices = CommunityConfigurationServices::wrap( $services );
		$this->providerFactory = $ccServices->getConfigurationProviderFactory();
		$this->formatterFactory = $services->getFormatterFactory();
		$this->messageLocalizer = RequestContext::getMain();
	}

	/**
	 * @inheritDoc
	 * @throws MaintenanceFatalError
	 */
	public function execute(): bool {
		$this->initServices();

		$provider = $this->providerFactory->newProvider( $this->getArg( 'provider' ) );

		$configStatus = $provider->getStore()->loadConfigurationUncached();
		if ( !$configStatus->isGood() ) {
			$this->output( "Failed to load config:\n" );
			$this->fatalError( $configStatus );
		}
		$config = $configStatus->value;

		$key = $this->getArg( 'key' );
		if ( $this->hasOption( 'delete' ) ) {
			if ( $this->getArg( 'value' ) !== null ) {
				$this->fatalError( '"value" argument must not be set when deleting a key!' );
			}
			$this->deleteConfigKey( $config, $key );
		} else {
			if ( $this->getArg( 'value' ) === null ) {
				$this->fatalError( '"value" argument must be set when adding a value!' );
			}
			$value = $this->getValueFromArg( $this->getArg( 'value' ) );

			$this->setConfigKeyToValue( $config, $key, $value );
		}

		$user = User::newSystemUser( User::MAINTENANCE_SCRIPT_USER, [ 'steal' => true ] );
		if ( !$user ) {
			$this->fatalError( 'Failed to create user' );
		}

		if ( $this->hasOption( 'dry-run' ) ) {
			$this->output( "Would save:\n" );
			$this->output( FormatJson::encode( $config, true ) . "\n" );
			$validationStatus = $provider->getValidator()->validateStrictly( $config );
			$this->output( "Validation status:\n" );
			$this->output( $validationStatus );
			$this->output( "\n" );
			return true;
		}

		$summaryAsWikitext = $this->messageLocalizer->msg(
			'communityconfiguration-maintenance-config-change-summary'
		)->params( $this->getOption( 'summary', '' ) )->inContentLanguage()->text();
		$saveStatus = $provider->getStore()->alwaysStoreConfiguration(
			$config,
			null,
			$user,
			$summaryAsWikitext
		);
		if ( !$saveStatus->isOK() ) {
			$this->error( $saveStatus );
			return false;
		}

		$this->output( "Saved!\n" );
		return true;
	}

	/**
	 * @throws MaintenanceFatalError
	 * @return mixed The decoded json from the argument
	 */
	private function getValueFromArg( string $arg ) {
		$valueStatus = FormatJson::parse( $arg );

		if ( !$valueStatus->isGood() ) {
			$parsingError = $this->formatterFactory->getStatusFormatter(
				$this->messageLocalizer
			)->getWikiText(
				$valueStatus
			);
			$this->fatalError( '`' . $arg . '` is not valid JSON: ' . $parsingError );
		}

		return $valueStatus->getValue();
	}

	/**
	 * @param stdClass $config
	 * @param string $key
	 * @param mixed $value
	 */
	private function setConfigKeyToValue( stdClass $config, string $key, $value ): void {
		if ( !str_contains( $key, '.' ) ) {
			$config->$key = $value;
		} else {
			$this->setObjectSubKey( $config, $key, $value );
		}
	}

	/**
	 * @param stdClass $config
	 * @param string $key
	 * @param mixed $value
	 */
	private function setObjectSubKey( stdclass $config, string $key, $value ): void {
		$subKeys = explode( '.', $key );
		$valueKey = array_pop( $subKeys );
		$target = &$config;
		foreach ( $subKeys as $subKey ) {
			if ( !isset( $target->$subKey ) ) {
				$target->$subKey = new stdClass();
			}
			$target = &$target->$subKey;
		}
		$target->$valueKey = $value;
	}

	private function deleteConfigKey( stdClass $config, string $key ): void {
		if ( !str_contains( $key, '.' ) ) {
			unset( $config->$key );
			return;
		}
		[ $propertyKey, $subKeys ] = explode( '.', $key, 2 );
		if ( is_array( $config->$propertyKey ) ) {
			$this->deleteArraySubKey( $config->$propertyKey, $subKeys );
		} else {
			$this->deleteConfigKey( $config->$propertyKey, $subKeys );
		}
	}

	private function deleteArraySubKey( array $config, string $key ): void {
		foreach ( $config as $arrayElement ) {
			$this->deleteConfigKey( $arrayElement, $key );
		}
	}

}

$maintClass = ChangeWikiConfig::class;
require_once RUN_MAINTENANCE_IF_MAIN;

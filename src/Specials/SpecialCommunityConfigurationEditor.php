<?php

namespace MediaWiki\Extension\CommunityConfiguration\Specials;

use MediaWiki\Extension\CommunityConfiguration\Provider\ConfigurationProviderFactory;
use MediaWiki\Extension\CommunityConfiguration\Provider\IConfigurationProvider;
use MediaWiki\Html\Html;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\SpecialPage\SpecialPageFactory;

class SpecialCommunityConfigurationEditor extends SpecialPage {

	private ConfigurationProviderFactory $providerFactory;
	private SpecialPageFactory $specialPageFactory;
	private IConfigurationProvider $provider;

	public function __construct(
		ConfigurationProviderFactory $providerFactory,
		SpecialPageFactory $specialPageFactory
	) {
		parent::__construct( 'CommunityConfigurationEditor', '', false );
		$this->providerFactory = $providerFactory;
		$this->specialPageFactory = $specialPageFactory;
	}

	/**
	 * @param string $name
	 */
	private function setProvider( string $name ) {
		$provider = $this->providerFactory->newProvider( $name );
		$this->provider = $provider;
	}

	/**
	 * @param string|null $subPage
	 */
	public function execute( $subPage ) {
		parent::execute( $subPage );
		$out = $this->getContext()->getOutput();
		$communityConfigurationEntryURL = $this->specialPageFactory->getTitleForAlias(
			'CommunityConfiguration'
		)->getLinkURL();

		if ( !$subPage ) {
			$out->redirect( $communityConfigurationEntryURL );
		} else {
			$out->setPageTitle( $this->msg( 'communityconfigurationeditor', $subPage ) );
			$out->addSubtitle( '&lt; ' . $this->getLinkRenderer()->makeLink(
				$this->getTitleFor( 'CommunityConfiguration' )
			) );

			$this->setProvider( $subPage );
			$config = $this->provider->loadValidConfigurationUncached();

			if ( !$config->isGood() ) {
				$out->addHTML( Html::element( 'p', [ 'class' => 'error' ], $this->msg(
					'communityconfiguration-invalid-stored-config-error'
				)->text() ) );
				LoggerFactory::getInstance( 'CommunityConfiguration' )->error(
					'Failed to load valid config from ' . $subPage,
					[
						'errors' => $config->getErrors()
					]
				);
				return;
			}
			$out->addJsConfigVars( [
				'communityConfigurationData' => [
					'providerName' => $subPage,
					'schema' => $this->provider->getValidator()->getSchemaBuilder()->getRootSchema(),
					'data' => $config->getValue(),
					'config' => [
						'i18nPrefix' => "communityconfiguration-$subPage"
					]
				]
			] );
			$out->addModules( [ 'ext.communityConfiguration.Editor' ] );
			$out->addHTML( Html::element( 'div', [ 'id' => 'ext-communityConfiguration-app-root' ] ) );
		}
	}
}

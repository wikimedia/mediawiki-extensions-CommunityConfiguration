<?php

namespace MediaWiki\Extension\CommunityConfiguration\Specials;

use MediaWiki\Extension\CommunityConfiguration\Provider\ConfigurationProviderFactory;
use MediaWiki\Html\Html;
use MediaWiki\SpecialPage\DisabledSpecialPage;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\SpecialPage\SpecialPageFactory;

class SpecialCommunityConfiguration extends SpecialPage {

	private ConfigurationProviderFactory $providerFactory;
	private SpecialPageFactory $specialPageFactory;
	private SpecialPage $dashboardSpecial;
	private SpecialPage $editorSpecial;
	private bool $dashboardSpecialEnabled;
	private bool $editorSpecialEnabled;

	public function __construct(
		ConfigurationProviderFactory $providerFactory,
		SpecialPageFactory $specialPageFactory
	) {
		parent::__construct( 'CommunityConfiguration' );
		$this->providerFactory = $providerFactory;
		$this->specialPageFactory = $specialPageFactory;
	}

	/**
	 * @param string|null $subPage
	 */
	public function execute( $subPage ) {
		$out = $this->getContext()->getOutput();

		$this->setCommunityConfigurationSpecialPages();

		if ( $subPage && !$this->isProviderSupported( $subPage ) ) {
			$out->addHTML( Html::rawElement( 'p', [ 'class' => 'error' ], $this->msg(
				'communityconfiguration-provider-not-found',
				$subPage
			)->parse() ) );

		} elseif ( $subPage && $this->isProviderSupported( $subPage ) && $this->editorSpecialEnabled ) {
			$this->editorSpecial->execute( $subPage );
		} elseif ( !$subPage && $this->dashboardSpecialEnabled ) {
			$this->dashboardSpecial->execute( $subPage );
		} else {
			$out->addHTML( Html::element( 'p', [ 'class' => 'error' ], $this->msg(
				'communityconfiguration-unknown-error'
			)->text() ) );
		}
		parent::execute( $subPage );
	}

	/**
	 * @param string $spName The name of the special page
	 * @return bool
	 */
	private function isSpecialPageEnabled( string $spName ): bool {
		$spClass = $this->specialPageFactory->getPage( $spName );
		if ( $spClass === null || $spClass instanceof DisabledSpecialPage ) {
			return false;
		}
		return true;
	}

	/**
	 * @param string $providerName The name of the provider as registered in extension.json
	 * @return bool
	 */
	private function isProviderSupported( string $providerName ): bool {
		return in_array( $providerName, $this->providerFactory->getSupportedKeys() );
	}

	private function setCommunityConfigurationSpecialPages() {
		$this->editorSpecialEnabled = $this->isSpecialPageEnabled( 'CommunityConfigurationEditor' );
		$this->dashboardSpecialEnabled = $this->isSpecialPageEnabled( 'CommunityConfigurationDashboard' );

		if ( $this->dashboardSpecialEnabled ) {
			$this->dashboardSpecial = $this->specialPageFactory->getPage( 'CommunityConfigurationDashboard' );
		}
		if ( $this->editorSpecialEnabled ) {
			$this->editorSpecial = $this->specialPageFactory->getPage( 'CommunityConfigurationEditor' );
		}
	}
}

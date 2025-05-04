<?php

namespace MediaWiki\Extension\CommunityConfiguration\Specials;

use MediaWiki\Extension\CommunityConfiguration\EditorCapabilities\EditorCapabilityFactory;
use MediaWiki\Extension\CommunityConfiguration\Provider\ConfigurationProviderFactory;
use MediaWiki\Extension\CommunityConfiguration\Provider\IConfigurationProvider;
use MediaWiki\Html\Html;
use MediaWiki\Output\OutputPage;
use MediaWiki\SpecialPage\SpecialPage;

class SpecialCommunityConfiguration extends SpecialPage {

	private EditorCapabilityFactory $editorCapabilityFactory;
	private ConfigurationProviderFactory $providerFactory;

	private const CAPABILITY_DASHBOARD = 'dashboard';
	private const CAPABILITY_EDITOR = 'generic-editor';

	public function __construct(
		EditorCapabilityFactory $editorCapabilityFactory,
		ConfigurationProviderFactory $providerFactory
	) {
		parent::__construct( 'CommunityConfiguration' );
		$this->editorCapabilityFactory = $editorCapabilityFactory;
		$this->providerFactory = $providerFactory;
	}

	/**
	 * @param string|null $subPage
	 * @return void
	 */
	public function execute( $subPage ) {
		parent::execute( $subPage );
		$out = $this->getContext()->getOutput();

		$parsedSubpage = explode( '/', $subPage ?? '', 2 );
		$providerId = $parsedSubpage[0];
		$provider = null;

		if ( $providerId === '' ) {
			$capabilityName = self::CAPABILITY_DASHBOARD;
		} else {
			if ( !$this->isProviderSupported( $providerId ) ) {
				$this->showErrorMessage( $out, 'communityconfiguration-provider-not-found', $providerId );
				return;
			}

			$provider = $this->providerFactory->newProvider( $providerId );

			// If not displayed on the dashboard, it doesn't necessarily mean it's not supported.
			if ( $provider->getOptionValue( IConfigurationProvider::OPTION_EXCLUDE_FROM_UI ) ) {
				$this->showErrorMessage( $out, 'communityconfiguration-provider-not-found', $providerId );
				return;
			}

			$capabilityName = $provider->getOptionValue( IConfigurationProvider::OPTION_EDITOR_CAPABILITY )
				?? self::CAPABILITY_EDITOR;
		}

		$this->editorCapabilityFactory
			->newCapability( $capabilityName, $this->getContext(), $this->getPageTitle() )
			->executeNew( $provider, $parsedSubpage[1] ?? null );
	}

	/** @inheritDoc */
	protected function getSubpagesForPrefixSearch() {
		$providerIds = $this->providerFactory->getSupportedKeysWithUI();
		sort( $providerIds );
		return $providerIds;
	}

	/**
	 * Show an error message on the output page
	 *
	 * @param OutputPage $out
	 * @param string $messageKey
	 * @param string $subPage
	 * @return void
	 */
	private function showErrorMessage( OutputPage $out, string $messageKey, $subPage ) {
		$out->addHTML( Html::rawElement( 'p', [ 'class' => 'error' ], $this->msg(
			$messageKey,
			$subPage
		)->parse() ) );
	}

	/**
	 * @param string $providerName The name of the provider as registered in extension.json
	 * @return bool
	 */
	private function isProviderSupported( string $providerName ): bool {
		return in_array( $providerName, $this->providerFactory->getSupportedKeys() );
	}
}

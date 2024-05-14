<?php

namespace MediaWiki\Extension\CommunityConfiguration\Hooks;

use MediaWiki\Extension\CommunityConfiguration\Provider\ConfigurationProviderFactory;
use MediaWiki\Extension\CommunityConfiguration\Store\WikiPageStore;
use MediaWiki\Hook\SkinTemplateNavigation__UniversalHook;
use MediaWiki\Title\MalformedTitleException;
use MediaWiki\Title\Title;
use SkinTemplate;

class NavigationHooks implements SkinTemplateNavigation__UniversalHook {
	private ConfigurationProviderFactory $providerFactory;

	public function __construct( ConfigurationProviderFactory $providerFactory ) {
		$this->providerFactory = $providerFactory;
	}

	/**
	 * Adds the `View Form` and `View History` tab.
	 *
	 * This is attached to the MediaWiki 'SkinTemplateNavigation::Universal' hook.
	 *
	 * @param SkinTemplate $sktemplate
	 * @param array &$links Navigation links.
	 * @throws MalformedTitleException
	 */
	public function onSkinTemplateNavigation__Universal( $sktemplate, &$links ): void {
		$title = $sktemplate->getTitle();
		$relevantTitle = $sktemplate->getRelevantTitle();
		if ( $title === null || $relevantTitle === null ) {
			return;
		}
		$dbKeyParts = explode( '/', $title->getDBkey() );
		$providerName = $dbKeyParts[1] ?? null;

		if ( $title->getContentModel() === CONTENT_MODEL_JSON && $title->getNamespace() === NS_MEDIAWIKI ) {
			$subpageText = $title->getSubpageText();
			foreach ( $this->providerFactory->getSupportedKeys() as $providerKey ) {
				$provider = $this->providerFactory->newProvider( $providerKey );
				$store = $provider->getStore();
				if ( $store instanceof WikiPageStore && $store->getConfigurationTitle()->getDBkey() === $subpageText ) {
					$specialPageTitle = Title::makeTitleSafe(
						NS_SPECIAL, "CommunityConfiguration/$providerKey" );
					if ( $specialPageTitle ) {
						$configurationUrl = $specialPageTitle->getFullURL();
						$links['views']['viewform'] = [
							'class' => '',
							'href' => $configurationUrl,
							'text' => $sktemplate->msg(
								'communityconfiguration-editor-navigation-tab-viewform' )->text()
						];
						break;
					}
				}
			}
		}
		if ( $providerName && in_array( $providerName, $this->providerFactory->getSupportedKeys() ) ) {
			unset( $links['associated-pages']['mediawiki'] );
			unset( $links['associated-pages']['mediawiki_talk'] );
			// Remove View link as it points to the JSON config page (text: "Read")
			unset( $links['views']['view'] );
			// Remove View link as it points to the JSON config page (text: "View source")
			unset( $links['views']['viewsource'] );
			$links['views']['edit'] = [
				'class' => 'selected',
				'href' => $sktemplate->getTitle()->getLocalURL(),
				'text' => $sktemplate->msg( 'communityconfiguration-editor-navigation-tab-viewform' )->text()
			];
			ksort( $links['views'] );
		}
	}
}

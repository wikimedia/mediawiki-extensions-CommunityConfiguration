<?php

namespace MediaWiki\Extension\CommunityConfiguration\Hooks;

use MediaWiki\Extension\CommunityConfiguration\Provider\ConfigurationProviderFactory;
use MediaWiki\Extension\CommunityConfiguration\Provider\IConfigurationProvider;
use MediaWiki\Extension\CommunityConfiguration\Store\WikiPageStore;
use MediaWiki\Hook\SkinTemplateNavigation__UniversalHook;
use MediaWiki\Skin\SkinTemplate;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\SpecialPage\SpecialPageFactory;
use MediaWiki\Title\MalformedTitleException;

class NavigationHooks implements SkinTemplateNavigation__UniversalHook {

	private SpecialPageFactory $specialPageFactory;
	private ConfigurationProviderFactory $providerFactory;

	public function __construct(
		SpecialPageFactory $specialPageFactory,
		ConfigurationProviderFactory $providerFactory
	) {
		$this->specialPageFactory = $specialPageFactory;
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
		if ( $title->getContentModel() === CONTENT_MODEL_JSON ) {
			foreach ( $this->providerFactory->getSupportedKeys() as $providerKey ) {
				$provider = $this->providerFactory->newProvider( $providerKey );
				if ( $provider->getOptionValue(
					IConfigurationProvider::OPTION_EXCLUDE_FROM_UI ) ) {
					continue;
				}
				$store = $provider->getStore();
				if ( $store instanceof WikiPageStore &&
					$store->getConfigurationTitle()->equals( $title ) ) {
					$specialPageTitle = SpecialPage::getTitleFor( 'CommunityConfiguration', $providerKey );
					$links['views'] = self::arrayInsertAfterView( $links['views'], [ 'viewform' => [
						'class' => '',
						'href' => $specialPageTitle->getLocalURL(),
						'text' => $sktemplate->msg(
							'communityconfiguration-editor-navigation-tab-viewform' )->text(),
					] ] );
					break;
				}
			}
		}

		[ $specialPageCanonicalName, $providerId ] = $this->specialPageFactory->resolveAlias( $title->getText() );
		if ( $specialPageCanonicalName === 'CommunityConfiguration'
			&& $providerId && in_array(
				$providerId, $this->providerFactory->getSupportedKeys() ) && $title->getNamespace() === NS_SPECIAL ) {
			// Unset Message and Discussion
			$links['associated-pages'] = [];
			// Unset Actions 'Move' and 'Delete'
			unset( $links['actions']['delete'] );
			unset( $links['actions']['move'] );
			unset( $links['actions']['protect'] );
			// Unset Views 'Edit' and 'Read'
			unset( $links['views']['view'] );
			unset( $links['views']['viewsource'] );
			unset( $links['views']['edit'] );
			$links['views'] = array_merge( [ 'viewform' => [
				'class' => 'selected',
				'href'  => $title->getLocalURL(),
				'text'  => $sktemplate->msg( 'communityconfiguration-editor-navigation-tab-viewform' )->text(),
			] ], $links['views'] );
		}
	}

	/**
	 * @param array $array
	 * @param mixed $insert
	 * @return array
	 */
	private static function arrayInsertAfterView( array $array, $insert ): array {
		$pos = array_search( 'view', array_keys( $array ) );
		return array_merge(
			array_slice( $array, 0, $pos + 1 ),
			$insert,
			array_slice( $array, $pos )
		);
	}
}

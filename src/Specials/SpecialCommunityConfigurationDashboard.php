<?php

namespace MediaWiki\Extension\CommunityConfiguration\Specials;

use MediaWiki\Extension\CommunityConfiguration\Provider\ConfigurationProviderFactory;
use MediaWiki\Html\TemplateParser;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\SpecialPage\SpecialPageFactory;

class SpecialCommunityConfigurationDashboard extends SpecialPage {

	private TemplateParser $templateParser;
	private ConfigurationProviderFactory $providerFactory;
	private SpecialPageFactory $specialPageFactory;

	private const GUIDELINES = [
		[
			'title' => 'communityconfiguration-guidelines-guideline1-title',
			'description' => 'communityconfiguration-guidelines-guideline1-description'
		],
		[
			'title' => 'communityconfiguration-guidelines-guideline2-title',
			'description' => 'communityconfiguration-guidelines-guideline2-description'
		]
	];

	public function __construct(
		ConfigurationProviderFactory $providerFactory,
		SpecialPageFactory $specialPageFactory
	) {
		parent::__construct( 'CommunityConfigurationDashboard', '', false );
		$this->providerFactory = $providerFactory;
		$this->specialPageFactory = $specialPageFactory;
		$this->templateParser = new TemplateParser( __DIR__ . '/templates' );
	}

	private function getProviders(): array {
		$availableProviders = [];
		$formEditorURL = $this->specialPageFactory->getTitleForAlias( 'CommunityConfiguration' )->getLinkURL();
		foreach ( $this->providerFactory->getSupportedKeys() as $providerName ) {
			$availableProviders[] = [
				'href' => $formEditorURL . '/' . $providerName,
				'title' => $this->msg( 'communityconfiguration-' . $providerName . '-title' ),
				'description' => $this->msg( 'communityconfiguration-' . $providerName . '-description' )
			];
		}
		return $availableProviders;
	}

	/**
	 * @param string|null $subPage
	 */
	public function execute( $subPage ) {
		$out = $this->getContext()->getOutput();
		$out->addModuleStyles( [ 'codex-styles' ] );
		$out->addModuleStyles( [ 'ext.communityConfiguration.Dashboard' ] );
		$data = [
			'title' => $this->msg( 'communityconfiguration-guidelines-title' ),
			'description' => $this->msg( 'communityconfiguration-guidelines-description' ),
			'guidelines' => array_map( function ( array $guideline ): array {
				return [
					'title' => $this->msg( $guideline['title'] ),
					'description' => $this->msg( $guideline['description'] )->parse()
				];
			}, self::GUIDELINES ),
			'providers-title' => $this->msg( 'communityconfiguration-providers-list-title' ),
			'providers' => $this->getProviders()
		];
		$templateHtml = $this->templateParser->processTemplate( 'Layout', $data );
		$out->addHTML( $templateHtml );
		parent::execute( $subPage );
	}
}

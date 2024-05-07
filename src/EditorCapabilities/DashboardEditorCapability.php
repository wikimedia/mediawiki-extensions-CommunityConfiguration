<?php

namespace MediaWiki\Extension\CommunityConfiguration\EditorCapabilities;

use MediaWiki\Context\IContextSource;
use MediaWiki\Extension\CommunityConfiguration\Provider\ConfigurationProviderFactory;
use MediaWiki\Extension\CommunityConfiguration\Provider\IConfigurationProvider;
use MediaWiki\Html\TemplateParser;
use MediaWiki\SpecialPage\SpecialPageFactory;
use MediaWiki\Title\Title;

class DashboardEditorCapability extends AbstractEditorCapability {

	private ConfigurationProviderFactory $providerFactory;
	private SpecialPageFactory $specialPageFactory;
	private TemplateParser $templateParser;

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
		IContextSource $ctx,
		Title $parentTitle,
		ConfigurationProviderFactory $providerFactory
	) {
		parent::__construct( $ctx, $parentTitle );

		$this->providerFactory = $providerFactory;
		$this->templateParser = new TemplateParser( __DIR__ . '/templates' );
	}

	private function getProviders(): array {
		$availableProviders = [];
		foreach ( $this->providerFactory->getSupportedKeys() as $providerName ) {
			$provider = $this->providerFactory->newProvider( $providerName );
			if ( $provider->getOptionValue( IConfigurationProvider::OPTION_SKIP_DASHBOARD_LISTING ) ) {
				continue;
			}
			$lowerCaseProviderName = strtolower( $providerName );
			$availableProviders[] = [
				'href' => $this->getParentTitle()->getSubpage( $providerName )->getLinkURL(),
				'title' => $this->msg( 'communityconfiguration-' . $lowerCaseProviderName . '-title' ),
				'description' => $this->msg( 'communityconfiguration-' . $lowerCaseProviderName . '-description' )
			];
		}
		return $availableProviders;
	}

	/**
	 * @inheritDoc
	 */
	public function execute( ?string $subpage ): void {
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
	}
}

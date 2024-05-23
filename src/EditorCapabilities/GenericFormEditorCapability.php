<?php

namespace MediaWiki\Extension\CommunityConfiguration\EditorCapabilities;

use LogicException;
use MediaWiki\Context\IContextSource;
use MediaWiki\Extension\CommunityConfiguration\Provider\ConfigurationProviderFactory;
use MediaWiki\Extension\CommunityConfiguration\Provider\IConfigurationProvider;
use MediaWiki\Html\Html;
use MediaWiki\Language\FormatterFactory;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\Status\StatusFormatter;
use MediaWiki\Title\Title;
use StatusValue;

class GenericFormEditorCapability extends AbstractEditorCapability {

	private ConfigurationProviderFactory $providerFactory;
	private LinkRenderer $linkRenderer;
	private StatusFormatter $statusFormatter;
	private IConfigurationProvider $provider;

	public function __construct(
		IContextSource $ctx,
		Title $parentTitle,
		ConfigurationProviderFactory $providerFactory,
		LinkRenderer $linkRenderer,
		FormatterFactory $formatterFactory
	) {
		parent::__construct( $ctx, $parentTitle );

		$this->providerFactory = $providerFactory;
		$this->linkRenderer = $linkRenderer;
		$this->statusFormatter = $formatterFactory->getStatusFormatter( $ctx );
	}

	/**
	 * @param StatusValue $validationError
	 * @return void
	 */
	private function displayValidationError( StatusValue $validationError ): void {
		$out = $this->getContext()->getOutput();
		$infoPageLinkTarget = $this->provider->getStore()->getInfoPageLinkTarget();
		$infoPageLink = $infoPageLinkTarget !== null ? $this->linkRenderer->makeLink(
			$infoPageLinkTarget
		) : null;

		$out->addHTML( implode( "\n", [
			// Add a generic headline
			Html::rawElement(
				'p', [ 'class' => 'error' ],
				$this->msg(
					$infoPageLink !== null ?
						'communityconfiguration-invalid-stored-config-error-with-link'
						: 'communityconfiguration-invalid-stored-config-error'
				)
					->params(
						$this->provider->getName( $this->getContext() )->text(),
						$this->provider->getId()
					)
					->rawParams( $infoPageLink )
					->parse()
			),

			// Add detailed information about the error
			Html::element( 'h2', [], $this->msg(
				'communityconfiguration-invalid-stored-config-error-details-headline'
			)->text() ),
			$this->statusFormatter->getHTML( $validationError )
		] ) );
	}

	/**
	 * @inheritDoc
	 */
	public function execute( ?string $subpage ): void {
		if ( $subpage === null ) {
			throw new LogicException(
				__CLASS__ . ' does not support $subpage param being null'
			);
		}

		$this->provider = $this->providerFactory->newProvider( $subpage );

		$out = $this->getContext()->getOutput();

		$linkTarget = $this->provider->getStore()->getInfoPageLinkTarget();
		if ( $linkTarget !== null ) {
			$out->getSkin()->setRelevantTitle( Title::newFromLinkTarget( $linkTarget ) );
		}

		$out->setPageTitleMsg( $this->msg(
			'communityconfigurationeditor',
			$this->provider->getName( $this )
		) );
		$out->addSubtitle( '&lt; ' . $this->linkRenderer->makeLink(
			$this->getParentTitle()
		) );

		$helpPage = $this->provider->getOptionValue( 'helpPage' );
		$helpURL = $this->provider->getOptionValue( 'helpURL' );

		if ( $helpPage ) {
			$out->addHelpLink( $helpPage );
		} elseif ( $helpURL ) {
			$out->addHelpLink( $helpURL, true );
		}

		$config = $this->provider->loadValidConfigurationUncached();
		if ( !$config->isOK() ) {
			$this->displayValidationError( $config );
			$this->logger->error(
				'Failed to load valid config from ' . $subpage,
				[
					'errors' => $config->getErrors()
				]
			);
			return;
		}

		$validationWarnings = $config->getMessages();
		if ( $validationWarnings !== [] ) {
			$this->logger->warning(
				__METHOD__ . ': Loaded config with warnings for {subpage}',
				[
					'subpage' => $subpage,
					'warnings' => $validationWarnings
				]
			);
		}

		$out->addJsConfigVars( [
			'communityConfigurationData' => [
				'providerId' => $subpage,
				'schema' => $this->provider->getValidator()->getSchemaBuilder()->getRootSchema(),
				'data' => $config->getValue(),
				'config' => [
					'i18nPrefix' => "communityconfiguration-" . strtolower( $subpage ),
					'bugReportToolURL' => $this->getContext()->getConfig()
						->get( 'CommunityConfigurationBugReportingToolURL' )
				]
			]
		] );
		$infoTextKey = 'communityconfiguration-' . strtolower( $subpage ) . '-info-text';
		if ( !$this->msg( $infoTextKey )->isDisabled() ) {
			$out->addHTML( Html::rawElement(
				'div',
				[ 'class' => 'communityconfiguration-info-section' ],
				$this->msg( $infoTextKey )->parseAsBlock()
			) );
		}
		$out->addModuleStyles( [ 'ext.communityConfiguration.Editor.styles' ] );
		$out->addModules( [ 'ext.communityConfiguration.Editor' ] );

		$out->addHTML( Html::rawElement(
			'div',
			[ 'class' => 'ext-communityConfiguration-LoadingBar' ],
			implode( "\n", [
				Html::rawElement(
					'p',
					[],
					$this->msg( 'communityconfiguration-editor-loading-info-text' )
				),
				Html::rawElement(
					'div',
					[],
					Html::rawElement(
						'div',
						[
							'class' => 'cdx-progress-bar',
							'role' => 'progressbar'
						],
						Html::rawElement(
							'div',
							[ 'class' => 'cdx-progress-bar__bar' ]
						)
					)
				)
			] )
		) );
		$out->addHTML( Html::rawElement(
			'p',
			[ 'class' => 'ext-communityConfiguration-NoJSFallback' ],
			$this->msg( 'communityconfiguration-editor-nojs-fallback-text' )
		) );
		$out->addHTML( Html::element( 'div', [ 'id' => 'ext-communityConfiguration-app-root' ] ) );
	}
}

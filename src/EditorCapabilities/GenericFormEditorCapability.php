<?php

namespace MediaWiki\Extension\CommunityConfiguration\EditorCapabilities;

use LogicException;
use MediaWiki\Config\Config;
use MediaWiki\Context\IContextSource;
use MediaWiki\Extension\CommunityConfiguration\Hooks\HookRunner;
use MediaWiki\Extension\CommunityConfiguration\Provider\IConfigurationProvider;
use MediaWiki\Html\Html;
use MediaWiki\Language\FormatterFactory;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\Status\StatusFormatter;
use MediaWiki\Title\Title;
use StatusValue;

class GenericFormEditorCapability extends AbstractEditorCapability {

	private LinkRenderer $linkRenderer;
	private StatusFormatter $statusFormatter;
	private IConfigurationProvider $provider;
	private HookRunner $hookRunner;
	private MessagesProcessor $messagesProcessor;
	private Config $config;

	public function __construct(
		IContextSource $ctx,
		Title $parentTitle,
		LinkRenderer $linkRenderer,
		FormatterFactory $formatterFactory,
		HookRunner $hookRunner,
		MessagesProcessor $messagesProcessor,
		Config $config
	) {
		parent::__construct( $ctx, $parentTitle );

		$this->linkRenderer = $linkRenderer;
		$this->statusFormatter = $formatterFactory->getStatusFormatter( $ctx );
		$this->hookRunner = $hookRunner;
		$this->messagesProcessor = $messagesProcessor;
		$this->config = $config;
	}

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
			$this->statusFormatter->getHTML( $validationError ),
		] ) );
	}

	/**
	 * @inheritDoc
	 */
	public function execute( ?IConfigurationProvider $provider, ?string $subpage = null ): void {
		if ( $provider === null ) {
			throw new LogicException( __CLASS__ . ' does not support $provider being null' );
		}

		$this->provider = $provider;

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
				'Failed to load valid config from ' . $this->provider->getId(),
				[
					'errors' => $config->getErrors(),
				]
			);
			return;
		}

		$validationWarnings = $config->getMessages();
		if ( $validationWarnings !== [] ) {
			$this->logger->warning(
				__METHOD__ . ': Loaded config with warnings for {provider}',
				[
					'provider' => $this->provider->getId(),
					'warnings' => $validationWarnings,
				]
			);
		}
		$rootSchema = $this->provider->getValidator()->getSchemaBuilder()->getRootSchema();
		$this->hookRunner->onCommunityConfigurationSchemaBeforeEditor( $this->provider, $rootSchema );
		$canEdit = $this->provider->getStore()->definitelyCanEdit( $this->getContext()->getAuthority() );
		$namespaceSelectorOptions = Html::namespaceSelectorOptions();
		$out->addJsConfigVars( [
			'communityConfigurationData' => [
				'providerId' => $this->provider->getId(),
				'schema' => $rootSchema,
				'data' => $config->getValue(),
				'config' => [
					'i18nPrefix' => "communityconfiguration-" . strtolower( $this->provider->getId() ),
					'i18nMessages' => $this->messagesProcessor->getMessages(
						$this->provider->getId(),
						$this->provider->getValidator()->getSchemaIterator(),
						'communityconfiguration'
					),
					'feedbackURL' => $this->getContext()->getConfig()
						->get( 'CommunityConfigurationFeedbackURL' ),
					'canEdit' => $canEdit,
					'namespaceSelectorOptions' => $namespaceSelectorOptions,
					'commonsApiURL' => $this->config->get( 'CommunityConfigurationCommonsApiURL' ),
				],
			],
		] );
		$infoTextKey = 'communityconfiguration-' . strtolower( $this->provider->getId() ) . '-info-text';
		if ( !$this->msg( $infoTextKey )->isDisabled() ) {
			$out->addHTML( Html::rawElement(
				'div',
				[ 'class' => 'communityconfiguration-info-section' ],
				$this->msg( $infoTextKey )->parseAsBlock()
			) );
		}
		$out->addModuleStyles( [ 'ext.communityConfiguration.Editor.styles' ] );
		$out->addModules( [
			'ext.communityConfiguration.Editor.common',
			'ext.communityConfiguration.Editor',
		] );

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
							'role' => 'progressbar',
						],
						Html::rawElement(
							'div',
							[ 'class' => 'cdx-progress-bar__bar' ]
						)
					)
				),
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

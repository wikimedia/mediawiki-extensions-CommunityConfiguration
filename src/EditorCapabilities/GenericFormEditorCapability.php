<?php

namespace MediaWiki\Extension\CommunityConfiguration\EditorCapabilities;

use LogicException;
use MediaWiki\Config\Config;
use MediaWiki\Context\IContextSource;
use MediaWiki\Extension\CommunityConfiguration\Hooks\HookRunner;
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
	private HookRunner $hookRunner;
	private MessagesProcessor $messagesProcessor;
	private Config $config;

	public function __construct(
		IContextSource $ctx,
		Title $parentTitle,
		ConfigurationProviderFactory $providerFactory,
		LinkRenderer $linkRenderer,
		FormatterFactory $formatterFactory,
		HookRunner $hookRunner,
		MessagesProcessor $messagesProcessor,
		Config $config
	) {
		parent::__construct( $ctx, $parentTitle );

		$this->providerFactory = $providerFactory;
		$this->linkRenderer = $linkRenderer;
		$this->statusFormatter = $formatterFactory->getStatusFormatter( $ctx );
		$this->hookRunner = $hookRunner;
		$this->messagesProcessor = $messagesProcessor;
		$this->config = $config;
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
			$this->statusFormatter->getHTML( $validationError ),
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
		$providerId = $this->provider->getId();

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
				'Failed to load valid config from ' . $providerId,
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
					'provider' => $providerId,
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
				'providerId' => $providerId,
				'schema' => $rootSchema,
				'data' => $config->getValue(),
				'config' => [
					'i18nPrefix' => "communityconfiguration-" . strtolower( $subpage ),
					'i18nMessages' => $this->messagesProcessor->getMessages(
						$providerId,
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
		$infoTextKey = 'communityconfiguration-' . strtolower( $providerId ) . '-info-text';
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

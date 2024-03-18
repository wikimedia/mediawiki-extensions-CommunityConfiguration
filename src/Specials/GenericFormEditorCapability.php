<?php

namespace MediaWiki\Extension\CommunityConfiguration\Specials;

use LogicException;
use MediaWiki\Config\Config;
use MediaWiki\Context\IContextSource;
use MediaWiki\Extension\CommunityConfiguration\Provider\ConfigurationProviderFactory;
use MediaWiki\Html\Html;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\Title\Title;

class GenericFormEditorCapability extends AbstractEditorCapability {

	private ConfigurationProviderFactory $providerFactory;
	private LinkRenderer $linkRenderer;
	private Config $wikiConfig;

	public function __construct(
		IContextSource $ctx,
		Title $parentTitle,
		ConfigurationProviderFactory $providerFactory,
		LinkRenderer $linkRenderer,
		Config $wikiConfig
	) {
		parent::__construct( $ctx, $parentTitle );

		$this->providerFactory = $providerFactory;
		$this->linkRenderer = $linkRenderer;
		$this->wikiConfig = $wikiConfig;
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

		$out = $this->getContext()->getOutput();
		$out->setPageTitle( $this->msg( 'communityconfigurationeditor', $subpage ) );
		$out->addSubtitle( '&lt; ' . $this->linkRenderer->makeLink(
			$this->getParentTitle()
		) );

		$provider = $this->providerFactory->newProvider( $subpage );
		$config = $provider->loadValidConfigurationUncached();

		if ( !$config->isGood() ) {
			$out->addHTML( Html::element( 'p', [ 'class' => 'error' ], $this->msg(
				'communityconfiguration-invalid-stored-config-error'
			)->text() ) );
			$this->logger->error(
				'Failed to load valid config from ' . $subpage,
				[
					'errors' => $config->getErrors()
				]
			);
			return;
		}

		$out->addJsConfigVars( [
			'communityConfigurationData' => [
				'providerName' => $subpage,
				'schema' => $provider->getValidator()->getSchemaBuilder()->getRootSchema(),
				'data' => $config->getValue(),
				'config' => [
					'i18nPrefix' => "communityconfiguration-" . strtolower( $subpage ),
					'bugReportToolURL' => $this->wikiConfig->get( 'CommunityConfigurationBugReportingToolURL' )
				]
			]
		] );
		$out->addModules( [ 'ext.communityConfiguration.Editor' ] );
		$out->addHTML( Html::element( 'div', [ 'id' => 'ext-communityConfiguration-app-root' ] ) );
	}
}

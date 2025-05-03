<?php

namespace MediaWiki\Extension\CommunityConfiguration\EditorCapabilities;

use LogicException;
use MediaWiki\Context\IContextSource;
use MediaWiki\Extension\CommunityConfiguration\CommunityConfigurationServices;
use MediaWiki\Extension\CommunityConfiguration\Provider\IConfigurationProvider;
use MediaWiki\MediaWikiServices;
use MediaWiki\Message\Message;
use MediaWiki\Title\Title;
use MessageLocalizer;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Wikimedia\Message\MessageSpecifier;

abstract class AbstractEditorCapability implements IEditorCapability, MessageLocalizer {
	use LoggerAwareTrait;

	private IContextSource $ctx;
	private Title $parentTitle;

	public function __construct( IContextSource $ctx, Title $parentTitle ) {
		$this->ctx = $ctx;
		$this->parentTitle = $parentTitle;

		$this->setLogger( new NullLogger() );
	}

	/**
	 * Return the context
	 *
	 * @return IContextSource
	 */
	final protected function getContext(): IContextSource {
		return $this->ctx;
	}

	/**
	 * Return the parent page's title
	 *
	 * The capability is included in a special page; this method returns the root title for that
	 * page, disregarding any subpages.
	 *
	 * @return Title
	 */
	final protected function getParentTitle(): Title {
		return $this->parentTitle;
	}

	/**
	 * Wrapper around wfMessage that sets the current context.
	 *
	 * @param string|string[]|MessageSpecifier $key
	 * @param mixed ...$params
	 * @return Message
	 * @see wfMessage
	 */
	public function msg( $key, ...$params ) {
		return $this->getContext()->msg( $key, ...$params );
	}

	public function execute( ?string $subpage ): void {
		// HACK: temporary, to prevent an infinite loop
		$alreadyHere = func_num_args() > 1 ? (bool)func_get_arg( 1 ) : false;
		if ( $alreadyHere ) {
			throw new LogicException( 'One of execute() and executeNew() must be implemented' );
		}

		$parsedSubpage = explode( '/', $subpage ?? '', 2 );
		$provider = CommunityConfigurationServices::wrap( MediaWikiServices::getInstance() )
			->getConfigurationProviderFactory()
			->newProvider( $parsedSubpage[0] );
		$this->executeNew( $provider, $parsedSubpage[1] ?? null, true );
	}

	/** @inheritDoc */
	public function executeNew(
		?IConfigurationProvider $provider, ?string $subpage = null
	): void {
		// HACK: temporary, to prevent an infinite loop
		$alreadyHere = func_num_args() > 2 ? (bool)func_get_arg( 2 ) : false;
		if ( $alreadyHere ) {
			throw new LogicException( 'One of execute() and executeNew() must be implemented' );
		}

		$fullSubpage = implode( '/', array_filter( [
			$provider ? $provider->getId() : null,
			$subpage ]
		) );
		$this->execute( $fullSubpage, true );
	}
}

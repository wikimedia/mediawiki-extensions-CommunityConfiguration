<?php

namespace MediaWiki\Extension\CommunityConfiguration\EditorCapabilities;

use MediaWiki\Context\IContextSource;
use MediaWiki\Message\Message;
use MediaWiki\Title\Title;
use MessageLocalizer;
use MessageSpecifier;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

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
}

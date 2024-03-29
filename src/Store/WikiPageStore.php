<?php

namespace MediaWiki\Extension\CommunityConfiguration\Store;

use MediaWiki\Extension\CommunityConfiguration\Store\WikiPage\Loader;
use MediaWiki\Extension\CommunityConfiguration\Store\WikiPage\Writer;
use MediaWiki\Linker\LinkTarget;
use MediaWiki\Permissions\Authority;
use MediaWiki\Permissions\PermissionStatus;
use MediaWiki\Status\Status;
use MediaWiki\Title\MalformedTitleException;
use MediaWiki\Title\Title;
use MediaWiki\Title\TitleFactory;
use StatusValue;

class WikiPageStore implements IConfigurationStore {

	private ?string $configLocation;
	private ?Title $configTitle = null;
	private TitleFactory $titleFactory;
	private Loader $loader;
	private Writer $writer;

	/**
	 * @param string|null $configLocation
	 * @param TitleFactory $titleFactory
	 * @param Loader $loader
	 * @param Writer $writer
	 */
	public function __construct(
		?string $configLocation,
		TitleFactory $titleFactory,
		Loader $loader,
		Writer $writer
	) {
		$this->configLocation = $configLocation;
		$this->titleFactory = $titleFactory;
		$this->loader = $loader;
		$this->writer = $writer;
	}

	/**
	 * @throws MalformedTitleException
	 */
	public function getConfigurationTitle(): Title {
		if ( $this->configTitle === null && $this->configLocation ) {
			$this->configTitle = $this->titleFactory->newFromTextThrow( $this->configLocation );
		}
		return $this->configTitle;
	}

	/**
	 * @inheritDoc
	 */
	public function getInfoPageLinkTarget(): ?LinkTarget {
		return $this->getConfigurationTitle();
	}

	/**
	 * @inheritDoc
	 */
	public function invalidate(): void {
		$this->loader->invalidate( $this->getConfigurationTitle() );
	}

	/**
	 * @inheritDoc
	 */
	public function loadConfigurationUncached(): StatusValue {
		return $this->loader->load(
			$this->getConfigurationTitle(),
			ICustomReadConstants::READ_UNCACHED
		);
	}

	/**
	 * @inheritDoc
	 */
	public function loadConfiguration(): StatusValue {
		return $this->loader->load( $this->getConfigurationTitle() );
	}

	/**
	 * @inheritDoc
	 */
	public function doStoreConfiguration(
		$config,
		Authority $authority,
		string $summary = ''
	): StatusValue {
		$status = $this->writer->save(
			$this->getConfigurationTitle(),
			$config,
			$authority,
			$summary
		)->getStatusValue();
		$this->invalidate();
		return $status;
	}

	/**
	 * @inheritDoc
	 */
	public function storeConfiguration(
		$config,
		Authority $authority,
		string $summary = ''
	): StatusValue {
		$permissionStatus = PermissionStatus::newGood();
		if ( !$authority->authorizeWrite( 'edit', $this->getConfigurationTitle(), $permissionStatus ) ) {
			return Status::wrap( $permissionStatus );
		}

		return $this->doStoreConfiguration( $config, $authority, $summary );
	}
}

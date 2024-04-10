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

	public const VERSION_FIELD_NAME = '$version';

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

	public static function removeVersionDataFromStatus( StatusValue $status ): StatusValue {
		$data = $status->getValue();
		if ( $data ) {
			unset( $data->{self::VERSION_FIELD_NAME} );
			$status->setResult( $status->isOK(), $data );
		}
		return $status;
	}

	/**
	 * @inheritDoc
	 */
	public function getVersion(): ?string {
		$status = $this->loader->load( $this->getConfigurationTitle() );
		if ( !$status->isOK() ) {
			return null;
		}
		return $status->getValue()->{self::VERSION_FIELD_NAME} ?? null;
	}

	/**
	 * @inheritDoc
	 */
	public function loadConfigurationUncached(): StatusValue {
		return self::removeVersionDataFromStatus( $this->loader->load(
			$this->getConfigurationTitle(),
			ICustomReadConstants::READ_UNCACHED
		) );
	}

	/**
	 * @inheritDoc
	 */
	public function loadConfiguration(): StatusValue {
		return self::removeVersionDataFromStatus(
			$this->loader->load( $this->getConfigurationTitle() )
		);
	}

	/**
	 * @inheritDoc
	 */
	public function storeConfiguration(
		$config,
		?string $version,
		Authority $authority,
		string $summary = ''
	): StatusValue {
		$permissionStatus = PermissionStatus::newGood();
		if ( !$authority->authorizeWrite( 'edit', $this->getConfigurationTitle(), $permissionStatus ) ) {
			return Status::wrap( $permissionStatus );
		}

		if ( $version ) {
			$config->{self::VERSION_FIELD_NAME} = $version;
		}

		$status = $this->writer->save(
			$this->getConfigurationTitle(),
			$config,
			$authority,
			$summary
		)->getStatusValue();
		$this->invalidate();
		return $status;
	}
}

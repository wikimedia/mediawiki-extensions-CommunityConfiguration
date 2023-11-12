<?php

namespace MediaWiki\Extension\CommunityConfiguration\Store;

use MediaWiki\Config\Config;
use MediaWiki\Extension\CommunityConfiguration\Store\WikiPage\Loader;
use MediaWiki\Extension\CommunityConfiguration\Store\WikiPage\Writer;
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
	 */
	public function __construct(
		Config $mainConfig,
		string $name,
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

	public function getConfigurationTitle(): Title {
		if ( $this->configTitle === null ) {
			$this->configTitle = $this->titleFactory->newFromTextThrow( $this->configLocation );
		}
		return $this->configTitle;
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
	public function storeConfiguration( array $config ): StatusValue {
		// TODO: Implement storeConfiguration() method.
		return StatusValue::newFatal( 'june' );
	}
}
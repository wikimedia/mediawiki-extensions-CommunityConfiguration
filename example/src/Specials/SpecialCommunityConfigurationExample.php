<?php
namespace MediaWiki\Extension\CommunityConfiguration\Example\Specials;

use MediaWiki\Config\Config;
use SpecialPage;

class SpecialCommunityConfigurationExample extends SpecialPage {

	private Config $wikiConfig;

	public function __construct(
		Config $wikiConfig
	) {
		parent::__construct( 'CommunityConfigurationExample' );

		$this->wikiConfig = $wikiConfig;
	}

	/**
	 * @param string $subPage
	 */
	public function execute( $subPage ): void {
		parent::execute( $subPage );

		$stringConfigNames = [ 'CCExample_String' ];
		foreach ( $stringConfigNames as $configName ) {
			$this->showStringConfig( $configName );
		}
	}

	private function showStringConfig( string $configName ): void {
		$exampleString = $this->wikiConfig->get( $configName );
		$this->getOutput()->addWikiTextAsInterface( $configName . ': "' . $exampleString . '"' );
		$this->getOutput()->addWikiTextAsInterface( 'Length: ' . strlen( $exampleString ) );
	}

}

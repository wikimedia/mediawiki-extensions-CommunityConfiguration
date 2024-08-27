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
		$this->getOutput()->addWikiTextAsInterface(
			<<<'STRING_INTRO'
			(Note that string-length is calculated in a multi-byte way.
			For English copy that usually means that each character is counted as "1".
			But this may be less true for other scripts and it is not true for emoji.
			Try "🏴󠁧󠁢󠁥󠁮󠁧󠁿": it is counted as having a length of 7.)
			STRING_INTRO
		);
		foreach ( $stringConfigNames as $configName ) {
			$this->showStringConfig( $configName );
		}
		$this->showNumbersFromObject( 'CCExample_Numbers' );
		$this->showMultiselectEnumConfig();
	}

	private function showStringConfig( string $configName ): void {
		$exampleString = $this->wikiConfig->get( $configName );
		$this->getOutput()->addWikiTextAsInterface( $configName . ': "' . $exampleString . '"' );
		$this->getOutput()->addWikiTextAsInterface( 'Length: ' . mb_strlen( $exampleString ) );
	}

	private function showMultiselectEnumConfig(): void {
		$colorConfigValues = $this->wikiConfig->get( 'CCExample_FavoriteColors' );
		$this->getOutput()->addWikiTextAsInterface( '==== Favorite colors: ====' );
		foreach ( $colorConfigValues as $color ) {
			$this->getOutput()->addHTML( '<div style="background-color: ' . $color . ';">&nbsp;</div>' );
			$this->getOutput()->addWikiTextAsInterface( 'Color: ' . $color );
		}
	}

	private function showNumbersFromObject( string $configName ): void {
		$this->getOutput()->addWikiTextAsInterface( '==== Numbers: ====' );
		$this->getOutput()->addWikiTextAsInterface(
			'Note that each values has a default, so there will always be a number here.'
		);
		$exampleNumberObject = $this->wikiConfig->get( $configName );
		$fieldNames = [ 'IntegerNumber', 'DecimalNumber' ];
		foreach ( $fieldNames as $fieldName ) {
			$this->getOutput()->addWikiTextAsInterface(
				$configName . ' ' . $fieldName . ': ' . $exampleNumberObject->{$fieldName}
			);
		}
	}

}

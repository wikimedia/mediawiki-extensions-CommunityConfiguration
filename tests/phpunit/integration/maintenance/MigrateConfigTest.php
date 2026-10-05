<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\CommunityConfiguration\Tests\Integration;

use MediaWiki\Extension\CommunityConfiguration\Maintenance\MigrateConfig;
use MediaWiki\Page\WikiPage;
use MediaWiki\Tests\Maintenance\MaintenanceBaseTestCase;
use MediaWiki\Title\Title;

/**
 * @group Database
 * @covers \MediaWiki\Extension\CommunityConfiguration\Maintenance\MigrateConfig
 */
class MigrateConfigTest extends MaintenanceBaseTestCase {
	protected function getMaintenanceClass(): string {
		return MigrateConfig::class;
	}

	public function testUpgrade(): void {
		$this->markTestSkippedIfExtensionNotLoaded( 'CommunityConfigurationExample' );

		$pageStatus = $this->editPage( 'MediaWiki:CommunityConfigurationExample.json', '{
	"$version": "1.0.0",
	"CCExample_OnOff": "on"
}' );
		$this->assertStatusGood( $pageStatus );

		$this->maintenance->loadParamsAndArgs(
			null,
			[],
			[ 'CommunityConfigurationExample' ]
		);
		$result = $this->maintenance->execute();
		$this->assertTrue( $result );

		$expectedConfig = (object)[
			'$version' => '1.1.0',
			"CCExample_OnOff" => true,
			'CCExample_String' => '',
			'CCExample_FavoriteColors' => [],
			'CCExample_Numbers' => (object)[
				'IntegerNumber' => 0,
				'DecimalNumber' => 0.6,
			],
			"CCExample_RelevantPages" => [],
			'CCExample_CustomControl' => 0,
			'CCExample_ValueA' => 0,
			'CCExample_ValueB' => '',
		];

		/** @var WikiPage $wikipage */
		$wikipage = $this->getServiceContainer()->get( 'WikiPageFactory' )->newFromTitle(
			Title::newFromText( 'MediaWiki:CommunityConfigurationExample.json' )
		);
		$actualDataStatus = $wikipage->getContent()->getData();
		$this->assertStatusGood( $actualDataStatus );
		$this->assertEquals( $expectedConfig, $actualDataStatus->getValue() );
	}

	public function testDowngrade(): void {
		$this->markTestSkippedIfExtensionNotLoaded( 'CommounityConfigurationExample' );

		$pageStatus = $this->editPage( 'MediaWiki:CommunityConfigurationExample.json', '{
	"$version": "1.1.0",
	"CCExample_OnOff": true
}' );
		$this->assertStatusGood( $pageStatus );

		$this->maintenance->loadParamsAndArgs(
			null,
			[ 'version' => '1.0.0' ],
			[ 'CommunityConfigurationExample' ]
		);

		$result = $this->maintenance->execute();
		$this->assertTrue( $result );

		$expectedConfig = (object)[
			'$version' => '1.0.0',
			"CCExample_OnOff" => "on",
			'CCExample_String' => '',
			'CCExample_FavoriteColors' => [],
			'CCExample_Numbers' => (object)[
				'IntegerNumber' => 0,
				'DecimalNumber' => 0.6,
			],
			"CCExample_RelevantPages" => [],
		];

		/** @var WikiPage $wikipage */
		$wikipage = $this->getServiceContainer()->get( 'WikiPageFactory' )->newFromTitle(
			Title::newFromText( 'MediaWiki:CommunityConfigurationExample.json' )
		);
		$actualDataStatus = $wikipage->getContent()->getData();
		$this->assertStatusGood( $actualDataStatus );
		$this->assertEquals( $expectedConfig, $actualDataStatus->getValue() );
	}
}

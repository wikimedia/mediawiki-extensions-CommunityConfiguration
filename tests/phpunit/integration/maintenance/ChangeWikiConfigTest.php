<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\CommunityConfiguration\Tests\Integration;

use MediaWiki\Extension\CommunityConfiguration\CommunityConfigurationServices;
use MediaWiki\Extension\CommunityConfiguration\Maintenance\ChangeWikiConfig;
use MediaWiki\Maintenance\MaintenanceFatalError;
use MediaWiki\Tests\Maintenance\MaintenanceBaseTestCase;
use MediaWiki\Title\Title;
use stdClass;
use Wikimedia\Rdbms\IDBAccessObject;

/**
 * @covers \MediaWiki\Extension\CommunityConfiguration\Maintenance\ChangeWikiConfig
 * @group Database
 */
class ChangeWikiConfigTest extends MaintenanceBaseTestCase {

	protected function getMaintenanceClass(): string {
		return ChangeWikiConfig::class;
	}

	public static function setValueSuccessCases(): array {
		return [
			'string' => [ 'CCExample_String', '"expectedValue"' ],
			'set full array' => [ 'CCExample_FavoriteColors', '["crimson","firebrick","gold"]' ],
			'full object' => [ 'CCExample_Numbers', '{"IntegerNumber":7,"DecimalNumber":0.9}' ],
			'sub-field of object' => [
				'CCExample_Numbers.IntegerNumber',
				42,
				'{}',
				'{"CCExample_Numbers":{"IntegerNumber":42}}',
			],
		];
	}

	/**
	 * @dataProvider setValueSuccessCases
	 */
	public function testSucceedsSettingValue(
		string $fieldName,
		$newValue,
		string $initialConfigValue = '{}',
		?string $expectedConfigValue = null
	): void {
		if ( $expectedConfigValue === null ) {
			$expectedConfigValue = "{\"$fieldName\":$newValue}";
		}
		$initialEditStatus = $this->editPage( 'MediaWiki:CommunityConfigurationExample.json', $initialConfigValue );
		$this->assertStatusGood( $initialEditStatus );
		$this->maintenance->loadParamsAndArgs(
			null,
			[ 'summary' => '<custom summary here>' ],
			[ 'CommunityConfigurationExample', $fieldName, $newValue ]
		);

		$result = $this->maintenance->execute();

		$this->assertTrue( $result );
		$actualConfig = $this->getValidConfig();
		$this->assertEquals( $expectedConfigValue, json_encode( $actualConfig ) );
		$latestRevision = $this->getServiceContainer()->getRevisionLookup()
			->getRevisionById(
				Title::newFromText( 'MediaWiki:CommunityConfigurationExample.json' )
					->getLatestRevID( IDBAccessObject::READ_LATEST )
			);
		$actualSummary = $latestRevision->getComment()->text;
		$this->assertSame( 'Config changed by maintenance script: <custom summary here>', $actualSummary );
	}

	public static function deletingValueCases(): iterable {
		yield 'delete top-level property' => [
			'{ "CCExample_String": "value" }',
			"CCExample_String",
			'{}',
		];

		yield 'delete sub-field of object' => [
			'{ "CCExample_Numbers": { "IntegerNumber": 42 } }',
			"CCExample_Numbers.IntegerNumber",
			'{"CCExample_Numbers":{}}',
		];

		yield 'delete sub-field of every object in array' => [
			'{ "CCExample_RelevantPages": [
			{ "text": "foo", "title": "Page_1", "id": "Q123" },
			{ "text": "bar", "title": "Page_2" },
			{ "text": "baz", "title": "Page_3", "id": "Q456" }
			] }',
			"CCExample_RelevantPages.id",
			'{ "CCExample_RelevantPages": [
			{ "text": "foo", "title": "Page_1" },
			{ "text": "bar", "title": "Page_2" },
			{ "text": "baz", "title": "Page_3" }
			] }',
		];
	}

	/**
	 * @dataProvider deletingValueCases
	 */
	public function testSucceedsDeletingValue(
		string $initialConfigValue,
		string $fieldName,
		string $expectedConfigValue
	): void {
		$initialEditStatus = $this->editPage( 'MediaWiki:CommunityConfigurationExample.json', $initialConfigValue );
		$this->assertStatusGood( $initialEditStatus );
		$this->maintenance->loadParamsAndArgs(
			null,
			[
				'summary' => '(custom summary here)',
				'delete' => '',
			],
			[ 'CommunityConfigurationExample', $fieldName ]
		);

		$result = $this->maintenance->execute();

		$this->assertTrue( $result );
		$actualConfig = $this->getValidConfig();
		$expectedConfigWithoutWhitespace = preg_replace( '/\s+/', '', $expectedConfigValue );
		$this->assertEquals( $expectedConfigWithoutWhitespace, json_encode( $actualConfig ) );
		$latestRevision = $this->getServiceContainer()->getRevisionLookup()
			->getRevisionById(
				Title::newFromText( 'MediaWiki:CommunityConfigurationExample.json' )
					->getLatestRevID( IDBAccessObject::READ_LATEST )
			);
		$actualSummary = $latestRevision->getComment()->text;
		$this->assertSame( 'Config changed by maintenance script: (custom summary here)', $actualSummary );
	}

	public static function noopCases() {
		yield 'setting value that exists' => [
			'{ "CCExample_String": "value" }',
			"CCExample_String",
			'"value"',
		];

		yield 'delete property that does not exist' => [
			'{ "CCExample_String": "value" }',
			"NonExistingProperty",
		];
	}

	/**
	 * @dataProvider noopCases
	 */
	public function testNoops(
		string $initialConfigValue,
		string $fieldName,
		?string $newValueAsJson = null
	): void {
		$isDelete = $newValueAsJson === null;

		$initialEditStatus = $this->editPage(
			'MediaWiki:CommunityConfigurationExample.json',
			$initialConfigValue,
			'summary of initial edit'
		);
		$this->assertStatusGood( $initialEditStatus );

		$arguments = [ 'CommunityConfigurationExample', $fieldName ];
		$options = [ 'summary' => '<custom summary here>' ];
		if ( $isDelete ) {
			$options['delete'] = '';
		} else {
			$arguments[] = $newValueAsJson;
		}
		$this->maintenance->loadParamsAndArgs(
			null,
			$options,
			$arguments
		);

		$result = $this->maintenance->execute();

		$this->assertTrue( $result );
		$actualConfig = $this->getValidConfig();
		$initialConfigValueWithoutWhitespace = preg_replace( '/\s+/', '', $initialConfigValue );
		$this->assertEquals( $initialConfigValueWithoutWhitespace, json_encode( $actualConfig ) );
		$latestRevision = $this->getServiceContainer()->getRevisionLookup()
			->getRevisionById(
				Title::newFromText( 'MediaWiki:CommunityConfigurationExample.json' )
					->getLatestRevID( IDBAccessObject::READ_LATEST )
			);
		$actualSummary = $latestRevision->getComment()->text;
		$this->assertSame( 'summary of initial edit', $actualSummary );
	}

	public function testFailsOnInvalidConfig(): void {
		$initialEditStatus = $this->editPage( 'MediaWiki:CommunityConfigurationExample.json', '{}' );
		$this->assertStatusGood( $initialEditStatus );
		$this->maintenance->loadParamsAndArgs(
			null,
			[ 'summary' => '(not actually saved)' ],
			[ 'CommunityConfigurationExample', 'NonExistingProperty', '"bar"' ]
		);

		$result = $this->maintenance->execute();

		$this->assertFalse( $result );
		$actualConfig = $this->getValidConfig();
		$this->assertEquals( '{}', json_encode( $actualConfig ) );
		$expectedOutputLines = [
			// phpcs:ignore Generic.Files.LineLength.TooLong
			'Error: DRAFT: The property NonExistingProperty is not defined and the definition does not allow additional properties. Key: ',
		];
		$this->assertOutputPrePostShutdown( implode( "\n", $expectedOutputLines ) . "\n", false );
	}

	private function getValidConfig(): stdClass {
		$provider = CommunityConfigurationServices::wrap( $this->getServiceContainer() )
			->getConfigurationProviderFactory()
			->newProvider( 'CommunityConfigurationExample' );
		$actualConfigStatus = $provider->getStore()->loadConfigurationUncached();
		$this->assertStatusGood( $actualConfigStatus );
		$actualConfig = $actualConfigStatus->value;
		$validationStatus = $provider->getValidator()->validateStrictly( $actualConfig );
		$this->assertStatusGood( $validationStatus );
		return $actualConfig;
	}

	public function testFailsOnInvalidJson(): void {
		$this->maintenance->loadParamsAndArgs(
			null,
			[ 'summary' => '(not actually saved)' ],
			[ 'CommunityConfigurationExample', 'NotValidJson', '{"bar"' ]
		);

		try {
			$this->maintenance->execute();
		} catch ( MaintenanceFatalError $e ) {
			$this->assertSame( 1, $e->getCode() );
		}

		$this->assertOutputPrePostShutdown(
			"`{\"bar\"` is not valid JSON: Syntax error\n",
			false
		);
	}

	public function testNullEdit(): void {
		$initialEditStatus = $this->editPage( 'MediaWiki:CommunityConfigurationExample.json',
			'{ "CCExample_String": "pre-existing config" }'
		);
		$this->assertStatusGood( $initialEditStatus );
		$this->maintenance->loadParamsAndArgs(
			null,
			[
				'summary' => '(null-edit summary here)',
				'null-edit' => '',
			],
			[ 'CommunityConfigurationExample' ]
		);

		$result = $this->maintenance->execute();
		$this->assertTrue( $result );
		$actualConfig = $this->getValidConfig();
		$this->assertEquals( (object)[
			'CCExample_FavoriteColors' => [],
			'CCExample_String' => 'pre-existing config',
			'CCExample_Numbers' => (object)[
				'IntegerNumber' => 0,
				'DecimalNumber' => 0.6,
			],
			'CCExample_RelevantPages' => [],
		], $actualConfig );
	}
}

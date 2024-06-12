<?php
namespace MediaWiki\Extension\CommunityConfiguration\Tests;

use MediaWiki\Extension\CommunityConfiguration\EditorCapabilities\MessagesProcessor;
use MediaWiki\Extension\CommunityConfiguration\Schema\JsonSchema;
use MediaWiki\Extension\CommunityConfiguration\Schema\JsonSchemaIterator;
use MediaWiki\Extension\CommunityConfiguration\Schema\JsonSchemaReader;
use MediaWiki\Extension\CommunityConfiguration\Schemas\MediaWiki\MediaWikiDefinitions;
use MediaWiki\Message\Message;
use MediaWikiUnitTestCase;
use MessageLocalizer;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @covers \MediaWiki\Extension\CommunityConfiguration\EditorCapabilities\MessagesProcessor
 * // phpcs:disable Generic.NamingConventions.UpperCaseConstantName.ClassConstantNotUpperCase
 */
class MessagesProcessorTest extends MediaWikiUnitTestCase {

	public static function provideSchema(): iterable {
		yield 'number' => [
			new class() extends JsonSchema {
				public const Number = [
					JsonSchema::TYPE => JsonSchema::TYPE_NUMBER,
				];
			},
			[
				'pfx-pid-number-label',
				'pfx-pid-number-help-text',
				'pfx-pid-number-placeholder'
			]
		];
		yield 'integer' => [
			new class() extends JsonSchema {
				public const Integer = [
					JsonSchema::TYPE => JsonSchema::TYPE_INTEGER,
				];
			},
			[
				'pfx-pid-integer-label',
				'pfx-pid-integer-help-text',
				'pfx-pid-integer-placeholder'
			]
		];
		yield 'boolean' => [
			new class() extends JsonSchema {
				public const Boolean = [
					JsonSchema::TYPE => JsonSchema::TYPE_BOOLEAN,
				];
			},
			[
				'pfx-pid-boolean-label',
				'pfx-pid-boolean-control-label',
				'pfx-pid-boolean-help-text',
			]
		];
		yield 'enum string' => [
			new class() extends JsonSchema {
				public const StringEnum = [
					JsonSchema::TYPE => JsonSchema::TYPE_STRING,
					JsonSchema::ENUM => [ 'fire', 'earth', 'water' ],
				];
			},
			[
				'pfx-pid-stringenum-label',
				'pfx-pid-stringenum-help-text',
				'pfx-pid-stringenum-option-fire-label',
				'pfx-pid-stringenum-option-earth-label',
				'pfx-pid-stringenum-option-water-label',
			]
		];
		yield 'enum number' => [
			new class() extends JsonSchema {
				public const NumberEnum = [
					JsonSchema::TYPE => JsonSchema::TYPE_NUMBER,
					JsonSchema::ENUM => [ 1, 7, 13 ],
				];
			},
			[
				'pfx-pid-numberenum-label',
				'pfx-pid-numberenum-help-text',
				'pfx-pid-numberenum-option-1-label',
				'pfx-pid-numberenum-option-7-label',
				'pfx-pid-numberenum-option-13-label',
			]
		];
		yield 'array of number' => [
			new class() extends JsonSchema {
				public const NumberArray = [
					JsonSchema::TYPE => JsonSchema::TYPE_ARRAY,
					JsonSchema::ITEMS => [
						JsonSchema::TYPE => JsonSchema::TYPE_NUMBER,
					]
				];
			},
			[
				'pfx-pid-numberarray-label',
				'pfx-pid-numberarray-help-text',
				'pfx-pid-numberarray-item-label',
				'pfx-pid-numberarray-add-element-button-label',
				'communityconfiguration-editor-array-fallback-add-element-button-label',
			],
			[]
		];
		yield 'array of string' => [
			new class() extends JsonSchema {
				public const StringArray = [
					JsonSchema::TYPE => JsonSchema::TYPE_ARRAY,
					JsonSchema::ITEMS => [
						JsonSchema::TYPE => JsonSchema::TYPE_STRING,
					]
				];
			},
			[
				'pfx-pid-stringarray-label',
				'pfx-pid-stringarray-help-text',
			],
			[
				'mw-widgets-titlesmultiselect-placeholder',
				'communityconfiguration-editor-chip-control-aria-chip-description',
			]
		];
		yield 'object' => [
			new class() extends JsonSchema {
				public const ExampleObject = [
					JsonSchema::TYPE => JsonSchema::TYPE_OBJECT,
					JsonSchema::PROPERTIES => [
						'foo' => [
							JsonSchema::TYPE => JsonSchema::TYPE_STRING,
						],
						'bar' => [
							JsonSchema::TYPE => JsonSchema::TYPE_NUMBER,
						]
					]
				];
			},
			[
				'pfx-pid-exampleobject-label',
				'pfx-pid-exampleobject-help-text',
			],
			[
				'pfx-pid-exampleobject-foo-label',
				'pfx-pid-exampleobject-foo-help-text',
				'pfx-pid-exampleobject-foo-placeholder',
				'pfx-pid-exampleobject-bar-label',
				'pfx-pid-exampleobject-bar-help-text',
				'pfx-pid-exampleobject-bar-placeholder'
			]
		];
		yield 'array of objects' => [
			new class() extends JsonSchema {
				public const ExampleArray = [
					JsonSchema::TYPE => JsonSchema::TYPE_ARRAY,
					JsonSchema::ITEMS => [
						JsonSchema::TYPE => JsonSchema::TYPE_OBJECT,
						JsonSchema::PROPERTIES => [
							'foo' => [
								JsonSchema::TYPE => JsonSchema::TYPE_STRING,
							],
							'bar' => [
								JsonSchema::TYPE => JsonSchema::TYPE_NUMBER,
							]
						]
					]
				];
			},
			[
				'pfx-pid-examplearray-label',
				'pfx-pid-examplearray-item-label',
				'pfx-pid-examplearray-help-text',
				'pfx-pid-examplearray-add-element-button-label',
				'communityconfiguration-editor-array-fallback-add-element-button-label',
			],
			[
				'pfx-pid-examplearray-foo-label',
				'pfx-pid-examplearray-foo-help-text',
				'pfx-pid-examplearray-foo-placeholder',
				'pfx-pid-examplearray-bar-label',
				'pfx-pid-examplearray-bar-help-text',
				'pfx-pid-examplearray-bar-placeholder'
			]
		];
		yield 'pagetitle' => [
			new class() extends JsonSchema {
				public const ExamplePage = [
					JsonSchema::REF => [
						'class' => MediaWikiDefinitions::class, 'field' => 'PageTitle'
					]
				];
			},
			[
				'pfx-pid-examplepage-label',
				'pfx-pid-examplepage-help-text',
				'pfx-pid-examplepage-placeholder',
			],
			[
				'communityconfiguration-page-title-control-no-results'
			]
		];
		yield 'pagetitles' => [
			new class() extends JsonSchema {
				public const ExamplePages = [
					JsonSchema::REF => [
						'class' => MediaWikiDefinitions::class, 'field' => 'PageTitles'
					]
				];
			},
			[
				'pfx-pid-examplepages-label',
				'pfx-pid-examplepages-help-text',
			],
			[
				'mw-widgets-titlesmultiselect-placeholder',
				'communityconfiguration-editor-chip-control-aria-chip-description',
			]
		];
		yield 'Namespaces' => [
			new class() extends JsonSchema {
				public const Namespaces = [
					JsonSchema::REF => [
						'class' => MediaWikiDefinitions::class, 'field' => 'Namespaces'
					]
				];
			},
			[
				'pfx-pid-namespaces-label',
				'pfx-pid-namespaces-help-text',
			],
			[
				'mw-widgets-titlesmultiselect-placeholder',
				'communityconfiguration-editor-chip-control-aria-chip-description'
			]
		];
	}

	/**
	 * @return MessageLocalizer|MockObject
	 */
	private function getMockMessageLocalizer() {
		$localizer = $this->getMockBuilder( MessageLocalizer::class )
			->onlyMethods( [ 'msg' ] )
			->getMockForAbstractClass();
		$localizer->method( 'msg' )
			->willReturnCallback( function ( $key, ...$params ) {
				$message = $this->createMock( Message::class );
				$message->method( 'exists' )->willReturn( true );
				$message->method( 'plain' )->willReturnCallback(
					static function () use ( $key ) {
						return $key;
					}
				);
				return $message;
			} );
		return $localizer;
	}

	/**
	 * @dataProvider provideSchema
	 */
	public function testGetMessages( $cls, $expected, $expectedSubControl = [] ) {
		$reader = new JsonSchemaReader( $cls );
		$iterator = new JsonSchemaIterator( $reader );
		$processor = new MessagesProcessor( $this->getMockMessageLocalizer() );
		$result = $processor->getMessages( 'pid', $iterator, 'pfx' );
		$this->assertEqualsCanonicalizing(
			array_merge( $expected, $expectedSubControl ),
			array_keys( $result )
		);
	}

}

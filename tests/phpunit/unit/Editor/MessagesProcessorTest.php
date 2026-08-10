<?php
namespace MediaWiki\Extension\CommunityConfiguration\Tests;

use MediaWiki\Extension\CommunityConfiguration\EditorCapabilities\MessagesProcessor;
use MediaWiki\Extension\CommunityConfiguration\Schema\JsonSchema;
use MediaWiki\Extension\CommunityConfiguration\Schema\JsonSchemaIterator;
use MediaWiki\Extension\CommunityConfiguration\Schema\JsonSchemaReader;
use MediaWiki\Extension\CommunityConfiguration\Schema\UISchema;
use MediaWiki\Extension\CommunityConfiguration\Schemas\MediaWiki\MediaWikiDefinitions;
use MediaWiki\Language\MessageLocalizer;
use MediaWiki\Message\Message;
use MediaWikiUnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;

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
				'pfx-pid-number-placeholder',
				'pfx-pid-number-description',
			],
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
				'pfx-pid-integer-placeholder',
				'pfx-pid-integer-description',
			],
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
				'pfx-pid-boolean-description',
			],
		];
		yield 'enum string' => [
			new class() extends JsonSchema {
				public const StringEnum = [
					JsonSchema::TYPE => JsonSchema::TYPE_STRING,
					JsonSchema::ENUM => [ 'fire', 'earth', 'water', 'UPPERCASE' ],
				];
			},
			[
				'pfx-pid-stringenum-label',
				'pfx-pid-stringenum-help-text',
				'pfx-pid-stringenum-description',
				'pfx-pid-stringenum-option-fire-label',
				'pfx-pid-stringenum-option-earth-label',
				'pfx-pid-stringenum-option-water-label',
				'pfx-pid-stringenum-option-uppercase-label',
			],
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
				'pfx-pid-numberenum-description',
				'pfx-pid-numberenum-option-1-label',
				'pfx-pid-numberenum-option-7-label',
				'pfx-pid-numberenum-option-13-label',
			],
		];
		yield 'array of number' => [
			new class() extends JsonSchema {
				public const NumberArray = [
					JsonSchema::TYPE => JsonSchema::TYPE_ARRAY,
					JsonSchema::ITEMS => [
						JsonSchema::TYPE => JsonSchema::TYPE_NUMBER,
					],
				];
			},
			[
				'pfx-pid-numberarray-label',
				'pfx-pid-numberarray-help-text',
				'pfx-pid-numberarray-item-label',
				'pfx-pid-numberarray-description',
				'pfx-pid-numberarray-add-element-button-label',
				'communityconfiguration-editor-array-fallback-add-element-button-label',
			],
			[],
		];
		yield 'array of string' => [
			new class() extends JsonSchema {
				public const StringArray = [
					JsonSchema::TYPE => JsonSchema::TYPE_ARRAY,
					JsonSchema::ITEMS => [
						JsonSchema::TYPE => JsonSchema::TYPE_STRING,
					],
				];
			},
			[
				'pfx-pid-stringarray-label',
				'pfx-pid-stringarray-help-text',
				'pfx-pid-stringarray-description',
			],
			[
				'mw-widgets-titlesmultiselect-placeholder',
				'communityconfiguration-editor-chip-control-aria-chip-description',
			],
		];
		yield 'array of enum' => [
			new class() extends JsonSchema {
				public const EnumArray = [
					JsonSchema::TYPE => JsonSchema::TYPE_ARRAY,
					JsonSchema::ITEMS => [
						JsonSchema::TYPE => JsonSchema::TYPE_STRING,
						JsonSchema::ENUM => [ "foo", "bar" ],
					],
				];
			},
			[
				'pfx-pid-enumarray-label',
				'pfx-pid-enumarray-help-text',
				'pfx-pid-enumarray-description',
				'pfx-pid-enumarray-option-foo-label',
				'pfx-pid-enumarray-option-bar-label',
			],
			[
				'communityconfiguration-editor-error-validation-array-items-max',
				'communityconfiguration-editor-error-validation-array-items-min',
				'communityconfiguration-editor-error-validation-enum-invalid-value',
				'mw-widgets-titlesmultiselect-placeholder',
				'communityconfiguration-editor-chip-control-aria-chip-description',
			],
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
						],
					],
				];
			},
			[
				'pfx-pid-exampleobject-label',
				'pfx-pid-exampleobject-help-text',
				'pfx-pid-exampleobject-description',
				'communityconfiguration-editor-error-validation-string-too-short',
				'communityconfiguration-editor-error-validation-string-too-long',
			],
			[
				'pfx-pid-exampleobject-foo-label',
				'pfx-pid-exampleobject-foo-help-text',
				'pfx-pid-exampleobject-foo-placeholder',
				'pfx-pid-exampleobject-foo-description',
				'pfx-pid-exampleobject-bar-label',
				'pfx-pid-exampleobject-bar-help-text',
				'pfx-pid-exampleobject-bar-placeholder',
				'pfx-pid-exampleobject-bar-description',
			],
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
							],
						],
					],
				];
			},
			[
				'pfx-pid-examplearray-label',
				'pfx-pid-examplearray-item-label',
				'pfx-pid-examplearray-help-text',
				'pfx-pid-examplearray-description',
				'pfx-pid-examplearray-add-element-button-label',
				'communityconfiguration-editor-array-fallback-add-element-button-label',
				'communityconfiguration-editor-error-validation-string-too-short',
				'communityconfiguration-editor-error-validation-string-too-long',
			],
			[
				'pfx-pid-examplearray-foo-label',
				'pfx-pid-examplearray-foo-help-text',
				'pfx-pid-examplearray-foo-placeholder',
				'pfx-pid-examplearray-foo-description',
				'pfx-pid-examplearray-bar-label',
				'pfx-pid-examplearray-bar-help-text',
				'pfx-pid-examplearray-bar-placeholder',
				'pfx-pid-examplearray-bar-description',
			],
		];
		yield 'pagetitle' => [
			new class() extends JsonSchema {
				public const ExamplePage = [
					JsonSchema::REF => [
						'class' => MediaWikiDefinitions::class, 'field' => 'PageTitle',
					],
				];
			},
			[
				'pfx-pid-examplepage-label',
				'pfx-pid-examplepage-help-text',
				'pfx-pid-examplepage-placeholder',
				'pfx-pid-examplepage-description',
			],
			[
				'communityconfiguration-page-title-control-no-results',
			],
		];
		yield 'pagetitles' => [
			new class() extends JsonSchema {
				public const ExamplePages = [
					JsonSchema::REF => [
						'class' => MediaWikiDefinitions::class, 'field' => 'PageTitles',
					],
				];
			},
			[
				'pfx-pid-examplepages-label',
				'pfx-pid-examplepages-help-text',
				'pfx-pid-examplepages-description',
			],
			[
				'mw-widgets-titlesmultiselect-placeholder',
				'communityconfiguration-editor-chip-control-aria-chip-description',
			],
		];
		yield 'Namespaces' => [
			new class() extends JsonSchema {
				public const Namespaces = [
					JsonSchema::REF => [
						'class' => MediaWikiDefinitions::class, 'field' => 'Namespaces',
					],
				];
			},
			[
				'pfx-pid-namespaces-label',
				'pfx-pid-namespaces-help-text',
				'pfx-pid-namespaces-description',
			],
			[
				'mw-widgets-titlesmultiselect-placeholder',
				'communityconfiguration-editor-chip-control-aria-chip-description',
			],
		];
	}

	/**
	 * @param string[]|null $existingKeys Keys that exist, or null when every key exists
	 * @return MessageLocalizer|MockObject
	 */
	private function getMockMessageLocalizer( ?array $existingKeys = null ) {
		$localizer = $this->getMockBuilder( MessageLocalizer::class )
			->onlyMethods( [ 'msg' ] )
			->getMockForAbstractClass();
		$localizer->method( 'msg' )
			->willReturnCallback( function ( $key, ...$params ) use ( $existingKeys ) {
				$message = $this->createMock( Message::class );
				$message->method( 'exists' )->willReturn(
					$existingKeys === null || in_array( $key, $existingKeys, true )
				);
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
		$processor = new MessagesProcessor( new NullLogger(), $this->getMockMessageLocalizer() );
		$result = $processor->getMessages( 'pid', $iterator, 'pfx' );
		array_unshift( $expected, 'pfx-pid-title' );
		$this->assertEqualsCanonicalizing(
			array_merge( $expected, $expectedSubControl ),
			array_keys( $result )
		);
	}

	public static function provideUiSchema(): iterable {
		yield 'no elements' => [ [], [] ];
		yield 'scope strings only' => [
			[ UISchema::ELEMENTS => [ '#/properties/Foo' ] ],
			[],
		];
		yield 'group with a label' => [
			[ UISchema::ELEMENTS => [ [
				UISchema::TYPE => UISchema::TYPE_GROUP,
				UISchema::LABEL => 'Eligibility',
				UISchema::ELEMENTS => [ '#/properties/Foo' ],
			] ] ],
			[ 'pfx-pid-eligibility-section-label', 'pfx-pid-eligibility-section-description' ],
		];
		yield 'group without a label' => [
			[ UISchema::ELEMENTS => [ [
				UISchema::TYPE => UISchema::TYPE_GROUP,
				UISchema::ELEMENTS => [ '#/properties/Foo' ],
			] ] ],
			[],
		];
		yield 'nested group' => [
			[ UISchema::ELEMENTS => [ [
				UISchema::TYPE => UISchema::TYPE_GROUP,
				UISchema::LABEL => 'Outer',
				UISchema::ELEMENTS => [ [
					UISchema::TYPE => UISchema::TYPE_GROUP,
					UISchema::LABEL => 'Inner',
					UISchema::ELEMENTS => [ '#/properties/Foo' ],
				] ],
			] ] ],
			[
				'pfx-pid-outer-section-label',
				'pfx-pid-outer-section-description',
				'pfx-pid-inner-section-label',
				'pfx-pid-inner-section-description',
			],
		];
		yield 'control messages' => [
			[ UISchema::ELEMENTS => [ [
				UISchema::TYPE => UISchema::TYPE_CONTROL,
				UISchema::SCOPE => '#/properties/Foo',
				UISchema::CONTROL => 'Test.Lookup',
				UISchema::MESSAGES => [ 'test-lookup-no-results', 'test-lookup-placeholder' ],
			] ] ],
			[ 'test-lookup-no-results', 'test-lookup-placeholder' ],
		];
		yield 'control messages inside a group' => [
			[ UISchema::ELEMENTS => [ [
				UISchema::TYPE => UISchema::TYPE_GROUP,
				UISchema::LABEL => 'Section',
				UISchema::ELEMENTS => [ [
					UISchema::TYPE => UISchema::TYPE_CONTROL,
					UISchema::SCOPE => '#/properties/Foo',
					UISchema::MESSAGES => [ 'test-lookup-no-results' ],
				] ],
			] ] ],
			[
				'pfx-pid-section-section-label',
				'pfx-pid-section-section-description',
				'test-lookup-no-results',
			],
		];
	}

	/**
	 * @param array $uiSchema
	 * @param array $expected
	 * @dataProvider provideUiSchema
	 */
	public function testGetUiSchemaMessages( array $uiSchema, array $expected ) {
		$processor = new MessagesProcessor( new NullLogger(), $this->getMockMessageLocalizer() );

		$this->assertEqualsCanonicalizing(
			$expected,
			array_keys( $processor->getUiSchemaMessages( 'pid', $uiSchema, 'pfx' ) )
		);
	}

	public function testGetUiSchemaMessagesSkipsMessagesThatDoNotExist() {
		// A group commonly has a heading without a description, so a missing message is normal
		// rather than an error.
		$processor = new MessagesProcessor(
			new NullLogger(),
			$this->getMockMessageLocalizer( [ 'pfx-pid-eligibility-section-label' ] )
		);

		$this->assertSame(
			[ 'pfx-pid-eligibility-section-label' => 'pfx-pid-eligibility-section-label' ],
			$processor->getUiSchemaMessages( 'pid', [ UISchema::ELEMENTS => [ [
				UISchema::TYPE => UISchema::TYPE_GROUP,
				UISchema::LABEL => 'Eligibility',
			] ] ], 'pfx' )
		);
	}

}

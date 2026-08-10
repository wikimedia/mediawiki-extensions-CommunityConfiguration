<?php

namespace MediaWiki\Extension\CommunityConfiguration\Tests;

use MediaWiki\Extension\CommunityConfiguration\Schema\UISchema;
use MediaWikiUnitTestCase;

/**
 * @covers \MediaWiki\Extension\CommunityConfiguration\Schema\UISchema
 */
class UISchemaTest extends MediaWikiUnitTestCase {

	public function testFlattenElementsWithEmptyLayout() {
		$this->assertSame( [], UISchema::flattenElements( [] ) );
		$this->assertSame( [], UISchema::flattenElements( [ UISchema::ELEMENTS => [] ] ) );
	}

	public function testFlattenElementsDropsScopeStrings() {
		// A scope string cannot hold a control, options or messages, so it is of no interest to
		// the callers of this function.
		$this->assertSame( [], UISchema::flattenElements( [
			UISchema::ELEMENTS => [ '#/properties/Foo', '#/properties/Bar' ],
		] ) );
	}

	public function testFlattenElementsKeepsDocumentOrder() {
		$first = [
			UISchema::TYPE => UISchema::TYPE_CONTROL,
			UISchema::SCOPE => '#/properties/Foo',
		];
		$second = [
			UISchema::TYPE => UISchema::TYPE_CONTROL,
			UISchema::SCOPE => '#/properties/Bar',
		];

		$this->assertSame(
			[ $first, $second ],
			UISchema::flattenElements( [ UISchema::ELEMENTS => [ $first, '#/properties/Baz', $second ] ] )
		);
	}

	public function testFlattenElementsGoesIntoNestedGroups() {
		$inner = [
			UISchema::TYPE => UISchema::TYPE_CONTROL,
			UISchema::SCOPE => '#/properties/Foo',
			UISchema::CONTROL => 'Test.Lookup',
		];
		$innerGroup = [
			UISchema::TYPE => UISchema::TYPE_GROUP,
			UISchema::LABEL => 'inner',
			UISchema::ELEMENTS => [ $inner ],
		];
		$outerGroup = [
			UISchema::TYPE => UISchema::TYPE_GROUP,
			UISchema::LABEL => 'outer',
			UISchema::ELEMENTS => [ '#/properties/Bar', $innerGroup ],
		];

		// A Group stays in the result, because it carries its own label.
		$this->assertSame(
			[ $outerGroup, $innerGroup, $inner ],
			UISchema::flattenElements( [ UISchema::ELEMENTS => [ $outerGroup ] ] )
		);
	}

	public function testFlattenElementsWithGroupWithoutElements() {
		$group = [
			UISchema::TYPE => UISchema::TYPE_GROUP,
			UISchema::LABEL => 'lonely',
		];

		$this->assertSame(
			[ $group ],
			UISchema::flattenElements( [ UISchema::ELEMENTS => [ $group ] ] )
		);
	}
}

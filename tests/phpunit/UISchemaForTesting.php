<?php

namespace MediaWiki\Extension\CommunityConfiguration\Tests;

use MediaWiki\Extension\CommunityConfiguration\Schema\UISchema;

class UISchemaForTesting extends UISchema {
	public const ROOT = [
		self::ELEMENTS => [
			[
				self::TYPE => self::TYPE_GROUP,
				self::LABEL => 'a-group',
				self::ELEMENTS => [
					'#/properties/NumberWithDefault',
				],
			],
			[
				self::TYPE => self::TYPE_CONTROL,
				self::SCOPE => '#/properties/Mentors',
				self::CONTROL => 'Testing.MentorList',
				self::OPTIONS => [ 'showUsernames' => true ],
				self::MESSAGES => [ 'communityconfiguration-testing-mentor-list-no-results' ],
			],
		],
	];
}

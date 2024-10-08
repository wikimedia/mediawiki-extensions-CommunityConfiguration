<?php

namespace MediaWiki\Extension\CommunityConfiguration\Schemas\MediaWiki;

use MediaWiki\Extension\CommunityConfiguration\Controls\CommonsFileControl;
use MediaWiki\Extension\CommunityConfiguration\Controls\NamespacesControl;
use MediaWiki\Extension\CommunityConfiguration\Controls\PageTitleControl;
use MediaWiki\Extension\CommunityConfiguration\Controls\PageTitlesControl;
use MediaWiki\Extension\CommunityConfiguration\Schema\JsonSchema;

// phpcs:disable Generic.NamingConventions.UpperCaseConstantName.ClassConstantNotUpperCase
class MediaWikiDefinitions extends JsonSchema {

	public const CommonsFile = [
		self::TYPE => self::TYPE_OBJECT,
		self::PROPERTIES => [
			'title' => [
				self::TYPE => self::TYPE_STRING,
				self::DEFAULT => '',
			],
			'url' => [
				self::TYPE => self::TYPE_STRING,
				self::DEFAULT => '',
			],
		],
		self::DEFAULT => [
			'title' => '',
			'url' => '',
		],
		'control' => CommonsFileControl::class,
	];

	public const PageTitle = [
		self::TYPE => self::TYPE_STRING,
		self::DEFAULT => '',
		'control' => PageTitleControl::class,
	];

	public const PageTitles = [
		self::TYPE => self::TYPE_ARRAY,
		self::ITEMS => [
			self::TYPE => self::TYPE_STRING,
			self::DEFAULT => '',
		],
		self::DEFAULT => [],
		'control' => PageTitlesControl::class,
	];

	public const Namespaces = [
		self::TYPE => self::TYPE_ARRAY,
		self::ITEMS => [
			self::TYPE => self::TYPE_INTEGER,
		],
		self::DEFAULT => [],
		'control' => NamespacesControl::class,
	];
}

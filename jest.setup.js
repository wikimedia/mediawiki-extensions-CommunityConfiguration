/* global jest:false */
'use strict';

function MWMessageMock() {}
MWMessageMock.prototype.exists = jest.fn();

// Mock MW object
const mw = {
	log: {
		error: jest.fn(),
		warn: jest.fn()
	},
	config: {
		get: jest.fn()
	},
	Message: MWMessageMock,
	user: {
		getId: jest.fn(),
		getName: jest.fn(),
		isAnon: jest.fn().mockReturnValue( true ),
		options: {
			get: jest.fn()
		}
	}
};

global.mw = mw;

// eslint-disable-next-line jsdoc/require-returns,jsdoc/require-param
/**
 * this provides defaults only for the functionality by mediawiki
 */
global.getGlobalMediaWikiMountingOptions = function ( provide = {}, directives = {}, mocks = {} ) {
	return {
		directives: {
			'i18n-html': jest.fn(),
			...directives
		},
		mocks: {
			$i18n: jest.fn( ( key ) => ( {
				text: () => key,
				toString: () => key
			} ) ),
			...mocks
		},
		provide: {
			i18n: jest.fn( ( key ) => ( {
				text: () => key,
				toString: () => key,
				exists: jest.fn( () => true )
			} ) ),
			...provide
		}
	};
};

// eslint-disable-next-line jsdoc/require-returns,jsdoc/require-param
/**
 * this provides defaults for the data and functionality provided both in init.js and by mediawiki
 */
global.getGlobalAppMountingOptions = function ( provide = {}, directives = {}, mocks = {} ) {
	return global.getGlobalMediaWikiMountingOptions(
		{
			CONFIG_DATA: {},
			JSON_SCHEMA: {},
			PROVIDER_ID: 'SomeProvider',
			EDITOR_FORM_CONFIG: {},
			CAN_EDIT: true,
			WRITING_REPOSITORY: {
				writeConfigurationData: jest.fn()
			},
			...provide
		},
		directives,
		mocks
	);
};

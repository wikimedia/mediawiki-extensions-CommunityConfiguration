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

function fakeMessageRendering( key, ...params ) {
	if ( params.length === 0 ) {
		return key;
	}
	return key + ': ' + params.join( ', ' );
}

// eslint-disable-next-line jsdoc/require-param
/**
 * simplified version of core's i18n-html directive
 */
function fakeRenderI18nHtml( el, binding ) {
	let message;

	if ( Array.isArray( binding.value ) ) {
		if ( binding.arg === undefined ) {
			// v-i18n-html="[ ...params ]" (error)
			throw new Error( 'v-i18n-html used with parameter array but without message key' );
		}
		// v-i18n-html:messageKey="[ ...params ]"
		message = fakeMessageRendering( binding.arg, ...binding.value );
	} else if ( binding.value instanceof mw.Message ) {
		// v-i18n-html="mw.message( '...' ).params( [ ... ] )"
		message = binding.value;
	} else {
		// v-i18n-html:foo or v-i18n-html="'foo'"
		message = binding.arg || binding.value;
	}

	el.innerHTML = message;
}

// eslint-disable-next-line jsdoc/require-returns,jsdoc/require-param
/**
 * this provides defaults only for the functionality by mediawiki
 */
global.getGlobalMediaWikiMountingOptions = function ( provide = {}, directives = {}, mocks = {} ) {
	return {
		directives: {
			'i18n-html': {
				mounted: fakeRenderI18nHtml,
				updated: fakeRenderI18nHtml
			},
			...directives
		},
		mocks: {
			$i18n: jest.fn( ( key, ...params ) => ( {
				text: () => fakeMessageRendering( key, ...params ),
				toString: () => fakeMessageRendering( key, ...params )
			} ) ),
			...mocks
		},
		provide: {
			i18n: jest.fn( ( key, ...params ) => ( {
				text: () => fakeMessageRendering( key, ...params ),
				toString: () => fakeMessageRendering( key, ...params ),
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

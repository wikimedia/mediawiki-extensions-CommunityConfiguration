'use strict';
jest.mock( './icons.json', () => ( {
	cdxIconLinkExternal: 'link external'
} ), { virtual: true } );
const { mount } = require( '@vue/test-utils' );
// FIXME the mock needs to be set before the require(App.vue)
// statement, move the read of mw.config.get( 'wgFormattedNamespaces' )
// in NamespacesControl out of the component load code path.
global.mw.config.get.mockImplementation( ( key ) => {
	switch ( key ) {
		case 'wgFormattedNamespaces':
			return {
				0: '',
				1: 'Talk',
				2: 'User',
				3: 'User talk',
				4: 'Dev',
				5: 'Dev talk',
				6: 'File',
				7: 'File talk',
				8: 'MediaWiki',
				9: 'MediaWiki talk',
				10: 'Template',
				11: 'Template talk',
				12: 'Help',
				13: 'Help talk',
				14: 'Category',
				15: 'Category talk',
				2600: 'Topic',
				'-2': 'Media',
				'-1': 'Special'
			};
		default:
			throw new Error( 'Unkown key: ' + key );
	}
} );
const App = require( './App.vue' );
const { JsonForm } = require( '../lib/json-form/form/index.js' );

describe( 'ext.communityConfiguration.Editor App', () => {
	const CONFIG_DATA = { some: 'data' };
	const JSON_SCHEMA = { some: 'schema', properties: {} };
	it( 'displays the app', () => {
		const wrapper = mount( App, {
			global: {
				mocks: {
					// TODO provide it as global mock/provider in jest config
					$i18n: jest.fn( () => ( {
						text: jest.fn()
					} ) )
				},
				provide: {
					CONFIG_DATA,
					JSON_SCHEMA,
					PROVIDER_ID: 'SomeProvider',
					EDITOR_FORM_CONFIG: {},
					// TODO provide it as global mock/provider in jest config
					i18n: jest.fn()
				}
			}
		} );
		expect( wrapper.getComponent( JsonForm ).props( 'schema' ) ).toEqual( JSON_SCHEMA );
		expect( wrapper.getComponent( JsonForm ).props( 'data' ) ).toEqual( CONFIG_DATA );
	} );
} );

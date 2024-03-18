jest.mock( './icons.json', () => ( {
	cdxIconLinkExternal: 'link external'
} ), { virtual: true } );
const { mount } = require( '@vue/test-utils' );
const App = require( './App.vue' );
const { JsonForm } = require( './lib/json-form/form/index.js' );

describe( 'ext.communityConfiguration.Editor App', () => {
	const CONFIG_DATA = { some: 'data' };
	const JSON_SCHEMA = { some: 'schema' };
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
					PROVIDER_NAME: 'SomeProvider',
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

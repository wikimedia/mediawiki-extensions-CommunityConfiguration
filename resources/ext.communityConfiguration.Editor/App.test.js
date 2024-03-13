const { mount } = require( '@vue/test-utils' );
const App = require( './App.vue' );
const { JsonForm } = require( './lib/json-form/form/index.js' );

describe( 'ext.communityConfiguration.Editor App', () => {
	const CONFIG_DATA = { some: 'data' };
	const JSON_SCHEMA = { some: 'schema' };
	it( 'displays the app', () => {
		const wrapper = mount( App, {
			global: {
				provide: {
					CONFIG_DATA,
					JSON_SCHEMA
				}
			}
		} );
		expect( wrapper.getComponent( JsonForm ).props( 'schema' ) ).toEqual( JSON_SCHEMA );
		expect( wrapper.getComponent( JsonForm ).props( 'data' ) ).toEqual( CONFIG_DATA );
	} );
} );

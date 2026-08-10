'use strict';
// This mounts App rather than JsonForm, so that the test follows the same path as the editor:
// init.js provides UI_SCHEMA and CUSTOM_RENDERERS, App passes them on, and DispatchRenderer
// selects the control. Passing renderers straight to JsonForm as a prop would not do, because
// vue-test-utils holds the props of the component under test in a reactive object, and Vue warns
// about a component inside one.
jest.mock( './icons.json', () => ( {
	cdxIconLinkExternal: 'link external',
} ), { virtual: true } );
jest.mock( '../lib/icons.json', () => ( {} ), { virtual: true } );

const { mount } = require( '@vue/test-utils' );

global.mw.config.get.mockImplementation( ( key ) => {
	switch ( key ) {
		case 'wgFormattedNamespaces':
			return {};
		default:
			return null;
	}
} );
global.mw.Message = jest.fn( ( messages, key ) => ( {
	exists: jest.fn( () => true ),
	text: jest.fn( () => key ),
	parse: jest.fn( () => key ),
} ) );

const App = require( './App.vue' );
const GroupLayout = require( '../lib/json-form/controls-codex/src/layouts/GroupLayout.vue' );
const ChipStringsControl = require( '../lib/json-form/controls-codex/src/complex/ChipStringsControl.vue' );
const { rankRenderer, rendererProps, uiControlIs } = require( '../lib/json-form/index.js' );

const SCHEMA = {
	type: 'object',
	additionalProperties: false,
	properties: {
		Functions: { type: 'array', items: { type: 'string' } },
		Enabled: { type: 'boolean' },
	},
};

// Stands in for a control that another extension registers.
const MockLookupControl = {
	name: 'MockLookupControl',
	props: Object.assign( {}, rendererProps() ),
	template: '<div class="mock-lookup"></div>',
};

const LOOKUP_ELEMENT = {
	type: 'Control',
	scope: '#/properties/Functions',
	control: 'Test.Lookup',
	options: { outputType: 'Z89' },
};

function mountApp( uiSchema = null, customRenderers = [] ) {
	return mount( App, {
		global: global.getGlobalAppMountingOptions( {
			JSON_SCHEMA: SCHEMA,
			CONFIG_DATA: { Functions: [ 'Z801' ], Enabled: true },
			EDITOR_FORM_CONFIG: { i18nPrefix: 'test-provider' },
			UI_SCHEMA: uiSchema,
			CUSTOM_RENDERERS: customRenderers,
		} ),
	} );
}

const lookupRenderer = () => rankRenderer( 10, MockLookupControl, uiControlIs( 'Test.Lookup' ) );

describe( 'the editor with a UI schema', () => {
	it( 'renders a control of the data schema when there is no UI schema', () => {
		const wrapper = mountApp();

		expect( wrapper.findComponent( ChipStringsControl ).exists() ).toBe( true );
		expect( wrapper.findComponent( GroupLayout ).exists() ).toBe( false );
	} );

	it( 'renders a control that another extension supplies', () => {
		// The acceptance criterion of ADR 0002: a control that an extension registers reaches
		// the form through the UI schema, with no change to the tree of CommunityConfiguration.
		const wrapper = mountApp( { elements: [ LOOKUP_ELEMENT ] }, [ lookupRenderer() ] );

		expect( wrapper.findComponent( MockLookupControl ).exists() ).toBe( true );
		// Rank 10 beats the rank 2 of ChipStringsControl, which would otherwise take an array
		// of strings.
		expect( wrapper.findComponent( ChipStringsControl ).exists() ).toBe( false );
	} );

	it( 'passes the options of the UI schema to the control', () => {
		const wrapper = mountApp( { elements: [ LOOKUP_ELEMENT ] }, [ lookupRenderer() ] );

		expect( wrapper.findComponent( MockLookupControl ).props( 'uischema' ).options )
			.toEqual( { outputType: 'Z89' } );
	} );

	it( 'falls back to a control of the data schema when the control is not available', () => {
		// This is what happens when the module of the control fails to load: the renderer is
		// simply absent, so no tester matches the control name.
		const wrapper = mountApp( { elements: [ LOOKUP_ELEMENT ] } );

		expect( wrapper.findComponent( MockLookupControl ).exists() ).toBe( false );
		expect( wrapper.findComponent( ChipStringsControl ).exists() ).toBe( true );
	} );

	it( 'renders a Group of the UI schema as a fieldset', () => {
		const wrapper = mountApp( { elements: [ {
			type: 'Group',
			label: 'section',
			elements: [ '#/properties/Functions' ],
		} ] } );

		const group = wrapper.findComponent( GroupLayout );
		expect( group.exists() ).toBe( true );
		expect( group.findComponent( ChipStringsControl ).exists() ).toBe( true );
	} );
} );

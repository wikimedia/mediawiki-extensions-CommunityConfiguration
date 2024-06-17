'use strict';
const { mount } = require( '@vue/test-utils' );
const { ref } = require( 'vue' );

const mwMessageFake = jest.fn( ( textReturnValue ) => {
	return {
		exists: jest.fn( () => true ),
		text: jest.fn( () => textReturnValue )
	};
} );

const ArrayFieldName = 'ExampleArraySchema';
const ArrayControl = require( './ArrayControl.vue' );
const DispatchRenderer = require( '../../../form/components/DispatchRenderer.vue' );

function getMountOptions( configDataObject = null, schema = null ) {
	const uischema = {
		name: ArrayFieldName,
		scope: `#/properties/${ ArrayFieldName }`,
		type: 'Control',
		label: mwMessageFake( 'Array label' )
	};
	if ( schema === null ) {
		schema = {
			type: 'array',
			items: {
				type: 'object',
				properties: {
					aNestedProp: {
						type: 'string'
					}
				}
			}
		};
	}

	const jsonform = {
		schema,
		uischema,
		config: {
			i18nPrefix: 'i18n-'
		},
		data: configDataObject || {},
		renderers: [],
		errors: ref( [] )
	};
	return {
		props: {
			schema,
			uischema,
			renderers: []
		},
		global: {
			...global.getGlobalMediaWikiMountingOptions( { jsonform } )
		}
	};
}

describe( 'ArrayControl', () => {
	it( 'should display as many items as data items received', () => {
		const wrapper = mount( ArrayControl, getMountOptions( {
			[ ArrayFieldName ]: [
				{ aNestedProp: 'String1' },
				{ aNestedProp: 'String2' }
			]
		} ) );
		expect( wrapper.findAllComponents( DispatchRenderer ).length ).toBe( 2 );
	} );
	it( 'adds a wrapper box to object items', () => {
		const wrapper = mount( ArrayControl, getMountOptions( {
			[ ArrayFieldName ]: [
				{ aNestedProp: 'String1' },
				{ aNestedProp: 'String2' }
			]
		} ) );
		expect(
			wrapper.find( '.ext-communityConfiguration-ArrayControl__item-wrapper' ).exists()
		).toBe( true );
	} );
} );

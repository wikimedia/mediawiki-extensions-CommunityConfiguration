'use strict';
const { mount } = require( '@vue/test-utils' );
const { ref, reactive } = require( 'vue' );
jest.mock( '../../../../icons.json', () => ( {
	cdxIconAdd: 'cdxIconAdd',
	cdxIconTrash: 'cdxIconTrash'
} ), { virtual: true } );

const mwMessageFake = jest.fn( ( textReturnValue ) => {
	return {
		exists: jest.fn( () => true ),
		text: jest.fn( () => textReturnValue )
	};
} );
global.mw.Message = mwMessageFake;

const ArrayFieldName = 'ExampleArraySchema';
const ArrayControl = require( './ArrayControl.vue' );
const ObjectControl = require( './ObjectControl.vue' );
const StringControl = require( '../controls/StringControl.vue' );
const { CdxTextInput } = require( '@wikimedia/codex' );

function getMountOptions( configDataObject = null, schema = null ) {
	const uischema = {
		name: ArrayFieldName,
		scope: `#/properties/${ ArrayFieldName }`,
		type: 'Control',
		label: mwMessageFake( 'Array label' ),
		addElementButtonLabel: mwMessageFake( 'Add another element' )
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

	const stringRenderer = {
		renderer: StringControl,
		tester: ( _, testerSchema ) => {
			return testerSchema.type === 'string' ? 1 : false;
		}
	};
	const objectRenderer = {
		renderer: ObjectControl,
		tester: ( _, testerSchema ) => {
			return testerSchema.type === 'object' ? 1 : false;
		}
	};

	const jsonform = {
		schema,
		uischema,
		config: {
			i18nPrefix: 'i18n-'
		},
		data: reactive( configDataObject || {} ),
		renderers: [ stringRenderer, objectRenderer ],
		errors: ref( [] )
	};
	return {
		props: {
			schema,
			uischema,
			renderers: [ stringRenderer, objectRenderer ]
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
		expect( wrapper.findAllComponents( ObjectControl ).length ).toBe( 2 );
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

	it( 'adds another element if the add element button is clicked', async () => {
		const configDataObject = {
			[ ArrayFieldName ]: [
				{ aNestedProp: 'String1' },
				{ aNestedProp: 'String2' }
			]
		};
		const wrapper = mount( ArrayControl, getMountOptions( configDataObject ) );
		expect( wrapper.findAllComponents( ObjectControl ).length ).toBe( 2 );

		await wrapper.get( '[data-test-id="array-control-add-element-button"]' ).trigger( 'click' );

		expect( configDataObject[ ArrayFieldName ].length ).toBe( 3 );
		expect( wrapper.findAllComponents( ObjectControl ).length ).toBe( 3 );
	} );

	it( 'removes an element if the its delete button is clicked', async () => {
		const secondElementValue = 'String2';
		const configDataObject = {
			[ ArrayFieldName ]: [
				{ aNestedProp: 'String1' },
				{ aNestedProp: secondElementValue }
			]
		};
		const wrapper = mount( ArrayControl, getMountOptions( configDataObject ) );
		expect( wrapper.findAllComponents( ObjectControl ).length ).toBe( 2 );

		await wrapper.get( '[data-test-id="array-control-delete-element-button"]' ).trigger( 'click' );

		expect( configDataObject[ ArrayFieldName ].length ).toBe( 1 );
		expect( configDataObject[ ArrayFieldName ][ 0 ].aNestedProp ).toBe( secondElementValue );
		expect( wrapper.findAllComponents( ObjectControl ).length ).toBe( 1 );
		expect( wrapper.findComponent( CdxTextInput ).props( 'modelValue' ) ).toBe( secondElementValue );
	} );

	it( 'disables the add-more-elements button if the maximum number of elements is reached', async () => {
		const schema = {
			type: 'array',
			items: {
				type: 'object',
				properties: {
					aNestedProp: {
						type: 'string'
					}
				}
			},
			maxItems: 3
		};
		const wrapper = mount( ArrayControl, getMountOptions( {
			[ ArrayFieldName ]: [
				{ aNestedProp: 'String1' },
				{ aNestedProp: 'String2' }
			]
		}, schema ) );
		expect( wrapper.get( '[data-test-id="array-control-add-element-button"]' ).attributes().disabled ).toBe( undefined );

		await wrapper.get( '[data-test-id="array-control-add-element-button"]' ).trigger( 'click' );

		expect( wrapper.get( '[data-test-id="array-control-add-element-button"]' ).attributes().disabled ).toBe( '' );
	} );
} );

'use strict';
const { mount } = require( '@vue/test-utils' );
const { ref } = require( 'vue' );

const mwMessageFake = jest.fn( ( textReturnValue ) => ( {
	exists: jest.fn( () => true ),
	text: jest.fn( () => textReturnValue )
} ) );

const BooleanControl = require( './BooleanControl.vue' );
const { CdxCheckbox, CdxField } = require( '@wikimedia/codex' );

describe( 'BooleanControl', () => {

	const BoolFieldName = 'TestBool';
	const CONFIG_DATA = { [ BoolFieldName ]: true };

	it( 'shows the control label and can be toggled', async () => {
		const controlLabelText = 'localized text on the label for the checkbox';
		const labelText = 'localized title text for the section containing the checkbox';
		const uischema = {
			name: BoolFieldName,
			scope: `#/properties/${ BoolFieldName }`,
			type: 'Control',
			controlLabel: mwMessageFake( controlLabelText ),
			label: mwMessageFake( labelText )
		};
		const schema = {
			type: 'boolean',
			default: null
		};
		const jsonform = {
			uischema,
			renderers: null,
			schema,
			config: { i18nPrefix: 'prefix' },
			data: CONFIG_DATA,
			errors: ref( [] )
		};
		const wrapper = mount( BooleanControl, {
			props: {
				renderers: null,
				schema: schema,
				uischema: uischema
			},
			global: {
				...global.getGlobalMediaWikiMountingOptions( { jsonform } )
			}
		} );

		const checkboxId = wrapper.find( 'input[type="checkbox"]' ).getRootNodes()[ 0 ].id;
		expect( wrapper.get( `[for=${ checkboxId }]` ).getRootNodes()[ 0 ].textContent ).toBe( controlLabelText );

		expect( wrapper.getComponent( CdxField ).props( 'isFieldset' ) ).toEqual( true );
		expect( wrapper.getComponent( CdxField ).attributes( 'id' ) ).toEqual( BoolFieldName );
		expect( wrapper.get( 'legend' ).getRootNodes()[ 0 ].textContent ).toBe( labelText );

		expect( wrapper.getComponent( CdxCheckbox ).props( 'modelValue' ) ).toEqual( true );

		await wrapper.find( 'input[type="checkbox"]' ).setValue( false );

		expect( CONFIG_DATA[ BoolFieldName ] ).toBe( false );
	} );
} );

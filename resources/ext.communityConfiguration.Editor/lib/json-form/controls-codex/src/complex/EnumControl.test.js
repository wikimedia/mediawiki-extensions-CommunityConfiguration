'use strict';
const { mount } = require( '@vue/test-utils' );
const { ref } = require( 'vue' );

const mwMessageFake = jest.fn( ( textReturnValue ) => {
	return {
		exists: jest.fn( () => true ),
		text: jest.fn( () => textReturnValue )
	};
} );

const EnumControl = require( './EnumControl.vue' );
const { CdxRadio, CdxField } = require( '@wikimedia/codex' );

const EnumFieldName = 'TestElementSelector';
const labelText = 'localized title text for the section containing the enum';

function getMountOptions( configDataObject = null, schema = null ) {
	const uischema = {
		name: EnumFieldName,
		scope: `#/properties/${ EnumFieldName }`,
		type: 'Control',
		enumLabels: {
			fire: 'fire-label-key'
		},
		label: mwMessageFake( labelText )
	};
	if ( schema === null ) {
		schema = {
			type: 'string',
			enum: [ 'fire', 'water', 'earth', 'air' ],
			default: 'ignored'
		};
	}

	const jsonform = {
		uischema,
		renderers: null,
		schema,
		data: configDataObject || {},
		errors: ref( [] )
	};

	return {
		props: {
			renderers: null,
			schema: schema,
			uischema: uischema
		},
		global: {
			mocks: {
				// TODO provide it as global mock/provider in jest config
				$i18n: jest.fn( ( key ) => ( {
					text: jest.fn().mockReturnValue( `i18n text for <${ key }>` )
				} ) )
			},
			provide: {
				jsonform
			}
		}
	};
}
describe( 'EnumControl', () => {

	it( 'has an id on its field', () => {
		const wrapper = mount( EnumControl, getMountOptions() );
		expect( wrapper.getComponent( CdxField ).attributes( 'id' ) ).toEqual( EnumFieldName );
	} );

	it( 'gets the labels from the uischema', () => {
		const wrapper = mount( EnumControl, getMountOptions() );

		const radioId = wrapper.get( 'input[value="fire"]' ).getRootNodes()[ 0 ].id;
		expect( wrapper.get( `[for=${ radioId }]` ).getRootNodes()[ 0 ].textContent ).toBe( 'i18n text for <fire-label-key>' );

		expect( wrapper.getComponent( CdxField ).props( 'isFieldset' ) ).toEqual( true );
		expect( wrapper.get( 'legend' ).getRootNodes()[ 0 ].textContent ).toBe( labelText );
	} );

	it( 'is unselected without default value and can be changed', async () => {
		const CONFIG_DATA = {};
		const wrapper = mount( EnumControl, getMountOptions( CONFIG_DATA ) );
		expect( wrapper.find( 'input:checked' ).exists() ).toBe( false );

		await wrapper.get( 'input[value="air"]' ).setValue( true );

		expect( CONFIG_DATA[ EnumFieldName ] ).toBe( 'air' );
	} );

	it( 'loads with the preexisting config value and can be changed', async () => {
		const CONFIG_DATA = { [ EnumFieldName ]: 'earth' };

		const wrapper = mount( EnumControl, getMountOptions( CONFIG_DATA ) );

		expect( wrapper.get( 'input:checked' ).element.value ).toBe( 'earth' );
		expect( wrapper.getComponent( CdxRadio ).props( 'modelValue' ) ).toEqual( 'earth' );

		await wrapper.get( 'input[value="water"]' ).setValue( true );

		expect( CONFIG_DATA[ EnumFieldName ] ).toBe( 'water' );
	} );

	it( 'works fine with numbers as values', async () => {
		const CONFIG_DATA = { [ EnumFieldName ]: 6 };

		const wrapper = mount( EnumControl, getMountOptions(
			CONFIG_DATA,
			{
				type: 'number',
				enum: [ 1, 6, 7, 9 ]
			}
		) );

		expect( wrapper.get( 'input:checked' ).element.value ).toBe( '6' );
		expect( wrapper.getComponent( CdxRadio ).props( 'modelValue' ) ).toBe( 6 );

		await wrapper.get( 'input[value="7"]' ).setValue( true );

		expect( CONFIG_DATA[ EnumFieldName ] ).toBe( 7 );
	} );
} );

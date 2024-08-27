'use strict';

const { mount } = require( '@vue/test-utils' );
const { ref, reactive } = require( 'vue' );
const NumberControl = require( './NumberControl.vue' );

function getMountOptions( subSchemaOverrides = {}, data = null ) {
	const uischema = {
		name: 'NumberFieldName',
		scope: '#/properties/NumberFieldName',
		type: 'Control'
	};

	const numberSubSchema = {
		type: 'number',
		default: null,
		...subSchemaOverrides
	};
	const schema = {
		type: 'object',
		properties: {
			NumberFieldName: numberSubSchema
		}
	};
	const jsonform = {
		uischema,
		renderers: null,
		schema,
		config: {
			i18nPrefix: 'i18n-prefix'
		},
		data: data || reactive( {} ),
		errors: ref( [] )
	};

	return {
		props: {
			renderers: null,
			uischema,
			schema: numberSubSchema
		},
		global: {
			...global.getGlobalMediaWikiMountingOptions( { jsonform } )
		}
	};
}

describe( 'NumberControl', () => {
	it( 'renders a number field without min/max if none are given', () => {
		const wrapper = mount( NumberControl, getMountOptions() );

		expect( wrapper.get( 'input[type="number"]' ).attributes( 'min' ) ).toBe( undefined );
		expect( wrapper.get( 'input[type="number"]' ).attributes( 'max' ) ).toBe( undefined );
	} );

	it( 'renders a number field with min/max if they are given in the schema', () => {
		const wrapper = mount( NumberControl, getMountOptions( {
			minimum: 0,
			maximum: 10
		} ) );

		expect( wrapper.get( 'input[type="number"]' ).attributes( 'min' ) ).toBe( '0' );
		expect( wrapper.get( 'input[type="number"]' ).attributes( 'max' ) ).toBe( '10' );
	} );

	it( 'shows an error if min/max are given and the value is out of bounds', async () => {
		const wrapper = mount( NumberControl, getMountOptions( {
			minimum: 0,
			maximum: 10
		} ) );

		await wrapper.get( 'input[type="number"]' ).setValue( 11 );

		expect( wrapper.get( '.cdx-message--error' ) ).toBeTruthy();
	} );

	describe( 'step', () => {

		it( 'sets step to `1` for integer fields', async () => {
			const wrapper = mount( NumberControl, getMountOptions( {
				type: 'integer'
			} ) );

			expect( wrapper.get( 'input[type="number"]' ).attributes( 'step' ) ).toBe( '1' );
			await wrapper.get( 'input[type="number"]' ).setValue( 0.5 );

			expect( wrapper.get( '.cdx-message--error' ) ).toBeTruthy();
		} );

		it( 'sets step to `any` for number fields', async () => {
			const wrapper = mount( NumberControl, getMountOptions( {
				type: 'number'
			} ) );

			expect( wrapper.get( 'input[type="number"]' ).attributes( 'step' ) ).toBe( 'any' );
			await wrapper.get( 'input[type="number"]' ).setValue( 0.5 );

			expect( wrapper.find( '.cdx-message--error' ).exists() ).toBeFalsy();
		} );
	} );
} );

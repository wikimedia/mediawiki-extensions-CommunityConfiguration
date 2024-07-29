'use strict';

const { mount } = require( '@vue/test-utils' );
const { ref } = require( 'vue' );
const NumberControl = require( './NumberControl.vue' );

describe( 'NumberControl', () => {
	it( 'renders a number field without min/max if none are given', () => {
		const uischema = {
			name: 'NumberFieldName',
			scope: '#/properties/NumberFieldName',
			type: 'Control'
		};
		const schema = {
			type: 'number',
			default: null
		};
		const jsonform = {
			uischema,
			renderers: null,
			schema,
			config: {
				i18nPrefix: 'i18n-prefix'
			},
			data: {},
			errors: ref( [] )
		};

		const wrapper = mount( NumberControl, {
			props: {
				renderers: null,
				uischema,
				schema
			},
			global: {
				...global.getGlobalMediaWikiMountingOptions( { jsonform } )
			}
		} );

		expect( wrapper.get( 'input[type="number"]' ).attributes( 'min' ) ).toBe( undefined );
		expect( wrapper.get( 'input[type="number"]' ).attributes( 'max' ) ).toBe( undefined );
	} );

	it( 'renders a number field with min/max if they are given in the schema', () => {
		const uischema = {
			name: 'NumberFieldName',
			scope: '#/properties/NumberFieldName',
			type: 'Control'
		};
		const schema = {
			type: 'number',
			minimum: 0,
			maximum: 10,
			default: null
		};
		const jsonform = {
			uischema,
			renderers: null,
			schema,
			config: {
				i18nPrefix: 'i18n-prefix'
			},
			data: {},
			errors: ref( [] )
		};

		const wrapper = mount( NumberControl, {
			props: {
				renderers: null,
				uischema,
				schema
			},
			global: {
				...global.getGlobalMediaWikiMountingOptions( { jsonform } )
			}
		} );

		expect( wrapper.get( 'input[type="number"]' ).attributes( 'min' ) ).toBe( '0' );
		expect( wrapper.get( 'input[type="number"]' ).attributes( 'max' ) ).toBe( '10' );
	} );
} );

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

	it( 'shows an error if min/max are given and the value is out of bounds', async () => {
		const uischema = {
			name: 'NumberFieldName',
			scope: '#/properties/NumberFieldName',
			type: 'Control'
		};
		const numberSubSchema = {
			type: 'number',
			minimum: 0,
			maximum: 10,
			default: null
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
			data: {},
			errors: ref( [] )
		};

		const wrapper = mount( NumberControl, {
			props: {
				renderers: null,
				uischema,
				schema: numberSubSchema
			},
			global: {
				...global.getGlobalMediaWikiMountingOptions( { jsonform } )
			}
		} );

		await wrapper.get( 'input[type="number"]' ).setValue( 11 );

		expect( wrapper.get( '.cdx-message--error' ) ).toBeTruthy();
	} );
} );

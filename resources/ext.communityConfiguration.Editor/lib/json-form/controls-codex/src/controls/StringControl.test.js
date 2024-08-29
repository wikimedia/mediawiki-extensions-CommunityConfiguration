'use strict';

const { mount } = require( '@vue/test-utils' );
const { ref, reactive } = require( 'vue' );
const StringControl = require( './StringControl.vue' );

function getMountOptions( min, max, data ) {
	const uischema = {
		name: 'StringFieldName',
		scope: '#/properties/StringFieldName',
		type: 'Control',
	};
	const StringSubSchema = {
		type: 'string',
		default: null,
	};
	if ( typeof min === 'number' ) {
		StringSubSchema.minLength = min;
	}
	if ( typeof max === 'number' ) {
		StringSubSchema.maxLength = max;
	}

	const schema = {
		type: 'object',
		properties: {
			StringFieldName: StringSubSchema,
		},
	};
	const jsonform = {
		uischema,
		renderers: null,
		schema,
		config: {
			i18nPrefix: 'i18n-prefix',
		},
		data: data || reactive( {} ),
		errors: ref( [] ),
	};

	return {
		props: {
			renderers: null,
			uischema,
			schema: StringSubSchema,
		},
		global: {
			...global.getGlobalMediaWikiMountingOptions( { jsonform } ),
		},
	};
}

describe( 'StringControl', () => {
	it( 'trims the value before updating the store', async () => {
		const data = reactive( {} );
		const wrapper = mount( StringControl, getMountOptions( null, null, data ) );
		await wrapper.get( 'input' ).setValue( '  abc  ' );
		expect( data.StringFieldName ).toBe( 'abc' );
	} );

	describe( 'validation', () => {
		it( 'renders a String field with minlength and maxlength not as HTML attributes', () => {
			const wrapper = mount( StringControl, getMountOptions( 1, 50 ) );

			expect( wrapper.get( 'input' ).attributes( 'minlength' ) ).toBe( undefined );
			expect( wrapper.get( 'input' ).attributes( 'maxlength' ) ).toBe( undefined );
		} );

		it( 'shows an error if minLength is given and the value is out of bounds', async () => {
			const wrapper = mount( StringControl, getMountOptions( 1 ) );

			await wrapper.get( 'input' ).setValue( '' );

			expect( wrapper.get( '.cdx-message--error' ).text() ).toBe( 'communityconfiguration-editor-error-validation-string-too-short: 1' );
		} );

		it( 'calculates the length for min-length by characters', async () => {
			const wrapper = mount( StringControl, getMountOptions( 10 ) );

			await wrapper.get( 'input' ).setValue( '🏴󠁧󠁢󠁥󠁮󠁧󠁿' ); // 7
			expect( wrapper.get( '.cdx-message--error' ).text() ).toBe( 'communityconfiguration-editor-error-validation-string-too-short: 10' );

			await wrapper.get( 'input' ).setValue( '🏴󠁧󠁢󠁥󠁮󠁧󠁿🏴󠁧󠁢󠁥󠁮󠁧󠁿' ); // 14
			expect( wrapper.find( '.cdx-message--error' ).exists() ).toBe( false );
		} );

		it( 'shows an error if maxLength is given and the value is out of bounds', async () => {
			const wrapper = mount( StringControl, getMountOptions( null, 3 ) );

			await wrapper.get( 'input' ).setValue( 'abcd' );

			expect( wrapper.get( '.cdx-message--error' ).text() ).toBe( 'communityconfiguration-editor-error-validation-string-too-long: 3' );
		} );

		it( 'calculates the length for max-length by characters', async () => {
			const wrapper = mount( StringControl, getMountOptions( null, 10 ) );

			await wrapper.get( 'input' ).setValue( '🏴󠁧󠁢󠁥󠁮󠁧󠁿🏴󠁧󠁢󠁥󠁮󠁧󠁿' ); // 7
			expect( wrapper.get( '.cdx-message--error' ).text() ).toBe( 'communityconfiguration-editor-error-validation-string-too-long: 10' );

			await wrapper.get( 'input' ).setValue( '🏴󠁧󠁢󠁥󠁮󠁧󠁿' ); // 14
			expect( wrapper.find( '.cdx-message--error' ).exists() ).toBe( false );
		} );

		it( 'trims the string before validation', async () => {
			const wrapper = mount( StringControl, getMountOptions( 1, 4 ) );

			await wrapper.get( 'input' ).setValue( ' ' );
			expect( wrapper.get( '.cdx-message--error' ).text() ).toBe( 'communityconfiguration-editor-error-validation-string-too-short: 1' );

			await wrapper.get( 'input' ).setValue( '  abc  ' );
			expect( wrapper.find( '.cdx-message--error' ).exists() ).toBe( false );
		} );

	} );
} );

'use strict';

const { mount } = require( '@vue/test-utils' );
const { ref, reactive } = require( 'vue' );
const MultiselectEnumControl = require( './MultiselectEnumControl.vue' );

function getMountOptions( multiselectSubSchema, data ) {
	const uischema = {
		name: 'MultiselectEnumFieldName',
		scope: '#/properties/MultiselectEnumFieldName',
		type: 'Control',
		enumLabels: {},
	};
	const rootSchema = {
		type: 'object',
		properties: {
			MultiselectEnumFieldName: multiselectSubSchema,
		},
	};
	const jsonform = {
		uischema,
		renderers: null,
		schema: rootSchema,
		config: {
			i18nPrefix: 'i18n-prefix',
		},
		data,
		errors: ref( [] ),
	};

	return {
		props: {
			renderers: null,
			uischema,
			schema: multiselectSubSchema,
		},
		global: {
			...global.getGlobalMediaWikiMountingOptions( { jsonform } ),
		},
	};
}

describe( 'MultiSelectEnumControl', () => {
	it( 'allows selecting an option from the menu with the keyboard', async () => {
		const subSchema = {
			type: 'array',
			items: {
				type: 'string',
				enum: [ 'one', 'two', 'three' ],
			},
			default: [],
		};
		const data = reactive( {} );
		const wrapper = mount( MultiselectEnumControl, getMountOptions( subSchema, data ) );
		const inputEl = wrapper.get( 'input' );

		await inputEl.trigger( 'click' );
		expect( wrapper.get( '.cdx-menu' ).isVisible() ).toBe( true );

		await inputEl.trigger( 'keydown.ArrowDown' );
		await inputEl.trigger( 'keydown.ArrowDown' );
		await inputEl.trigger( 'keydown.Enter' );

		expect( data ).toStrictEqual( { MultiselectEnumFieldName: [ 'two' ] } );
	} );

	describe( 'client validation errors', () => {
		it( 'shows an error if an options has been entered that is not in the list', async () => {
			const subSchema = {
				type: 'array',
				items: {
					type: 'string',
					enum: [ 'one', 'two', 'three' ],
				},
				default: [],
			};
			const data = reactive( {} );
			const wrapper = mount( MultiselectEnumControl, getMountOptions( subSchema, data ) );

			const inputEl = wrapper.get( 'input' );
			await inputEl.setValue( 'four' );
			await inputEl.trigger( 'keydown.Enter' );

			expect( data ).toStrictEqual( { MultiselectEnumFieldName: [ 'four' ] } );
			expect( wrapper.get( '.cdx-message--error' ).text() ).toBe( 'communityconfiguration-editor-error-validation-enum-invalid-value: one, two, three' );
		} );

		it( 'shows an error if too many options have been entered', async () => {
			const subSchema = {
				type: 'array',
				items: {
					type: 'string',
					enum: [ 'one', 'two', 'three' ],
				},
				default: [],
				maxItems: 1,
			};
			const data = reactive( {} );
			const wrapper = mount( MultiselectEnumControl, getMountOptions( subSchema, data ) );

			const inputEl = wrapper.get( 'input' );
			await inputEl.setValue( 'one' );
			await inputEl.trigger( 'keydown.Enter' );
			await inputEl.setValue( 'two' );
			await inputEl.trigger( 'keydown.Enter' );

			expect( data ).toStrictEqual( { MultiselectEnumFieldName: [ 'one', 'two' ] } );
			expect( wrapper.get( '.cdx-message--error' ).text() ).toBe( 'communityconfiguration-editor-error-validation-array-items-max: 1' );
		} );

		it( 'shows an error if too few options have been entered', async () => {
			const subSchema = {
				type: 'array',
				items: {
					type: 'string',
					enum: [ 'one', 'two', 'three' ],
				},
				default: [],
				minItems: 2,
			};
			const data = reactive( { MultiselectEnumFieldName: [ 'one', 'two' ] } );
			const wrapper = mount(
				MultiselectEnumControl,
				getMountOptions( subSchema, data ),
			);
			const firstChip = wrapper.getComponent( '.cdx-input-chip' );

			await firstChip.get( 'button' ).trigger( 'click' );

			expect( data ).toStrictEqual( { MultiselectEnumFieldName: [ 'two' ] } );
			expect( wrapper.get( '.cdx-message--error' ).text() ).toBe( 'communityconfiguration-editor-error-validation-array-items-min: 2' );
		} );
	} );
} );

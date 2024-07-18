'use strict';
const { mount } = require( '@vue/test-utils' );
const PageTitleControl = require( './PageTitleControl.vue' );
const { ref, reactive } = require( 'vue' );

// TODO: move to jest.setup.js
const mwMessageFake = jest.fn( ( textReturnValue ) => {
	return {
		exists: jest.fn( () => true ),
		text: jest.fn( () => textReturnValue )
	};
} );

jest.useFakeTimers();
describe( 'PageTitleControl', () => {
	it( 'sets the field value to the empty string if input is blanked', async () => {
		const initialValue = 'initial value';

		class fakeApi {
			get() {
				const fakeResponse = {
					query: {
						prefixsearch: [
							{ title: initialValue }
						]
					}
				};
				return Promise.resolve( fakeResponse );
			}
		}
		mw.Api = fakeApi;

		const schema = {
			type: 'string',
			control: 'MediaWiki\\Extension\\CommunityConfiguration\\Controls\\PageTitleControl',
			default: ''
		};
		const fieldName = 'learnmore';
		const uischema = {
			name: fieldName,
			scope: `#/properties/${ fieldName }`,
			type: 'Control',
			controlLabel: null,
			label: mwMessageFake( 'labelText' )
		};

		const reactiveData = reactive( {
			[ fieldName ]: initialValue
		} );
		const jsonform = {
			schema,
			uischema,
			config: {
				i18nPrefix: 'i18n-'
			},
			data: reactiveData,
			renderers: null,
			errors: ref( [] )
		};
		const wrapper = mount( PageTitleControl, {
			props: {
				renderers: null,
				schema,
				uischema
			},
			global: {
				...global.getGlobalMediaWikiMountingOptions( { jsonform } )
			}
		} );
		jest.runAllTimers();

		expect( wrapper.get( 'input' ).element.value ).toBe( initialValue );

		await wrapper.get( 'input' ).setValue( '' );
		await wrapper.get( 'input' ).trigger( 'input', '' );

		expect( reactiveData[ fieldName ] ).toBe( '' );
	} );
} );

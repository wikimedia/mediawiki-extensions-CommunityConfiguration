'use strict';
const { mount } = require( '@vue/test-utils' );
const PageTitlesControl = require( './PageTitlesControl.vue' );
const { ref, reactive } = require( 'vue' );

// TODO: DRY this up, it is repeated in multiple tests.
const mwMessageFake = jest.fn( ( textReturnValue ) => ( {
	exists: jest.fn( () => true ),
	text: jest.fn( () => textReturnValue ),
} ) );

jest.useFakeTimers();
describe( 'PageTitlesControl', () => {
	const userInput = 'sturdy potato';
	const schema = {
		type: 'string',
		control: 'MediaWiki\\Extension\\CommunityConfiguration\\Controls\\PageTitlesControl',
		default: '',
	};
	const uischema = {
		name: 'GEInfoboxTemplates',
		scope: '#/properties/GEInfoboxTemplates',
		type: 'Control',
		label: mwMessageFake( 'communityconfiguration-growthsuggestededits-geinfoboxtemplates-label' ),
	};
	const initialTemplateTitles = [
		'Template 0',
		'Template 1',
	];
	const initialTemplates = {
		GEInfoboxTemplates: initialTemplateTitles,
	};
	it( 'allows the user to select custom input when there are no search results', async () => {
		class fakeApi {
			get() {
				return Promise.resolve( { query: { prefixsearch: [] } } );
			}
		}

		mw.Api = fakeApi;

		const reactiveData = reactive( initialTemplates );
		const jsonform = {
			schema,
			uischema,
			config: {
				i18nPrefix: 'communityconfiguration-growthsuggestededits',
			},
			data: reactiveData,
			renderers: null,
			errors: ref( [] ),
		};
		const wrapper = mount( PageTitlesControl, {
			props: {
				renderers: null,
				uischema,
				schema,
			},
			global: {
				...global.getGlobalMediaWikiMountingOptions( { jsonform } ),
			},
		} );

		jest.runAllTimers();

		// set user input
		await wrapper.get( 'input' ).setValue( userInput );
		await wrapper.get( 'input' ).trigger( 'keyup', { key: ' ' } );

		jest.advanceTimersByTime( 300 );

		const updatedTitles = initialTemplateTitles.concat( [ userInput ] );
		await wrapper.vm.onTitlesUpdated( updatedTitles );

		// data should now include the selected custom input
		expect( reactiveData.GEInfoboxTemplates ).toContain( userInput );
	} );

	it( 'allows the user to select custom input when there are search results', async () => {
		class fakeApi {
			get() {
				return Promise.resolve( { query: { prefixsearch: [ 'fake result 1', 'fake result 2' ] } } );
			}
		}

		mw.Api = fakeApi;

		const reactiveData = reactive( initialTemplates );
		const jsonform = {
			schema,
			uischema,
			config: {
				i18nPrefix: 'communityconfiguration-growthsuggestededits',
			},
			data: reactiveData,
			renderers: null,
			errors: ref( [] ),
		};
		const wrapper = mount( PageTitlesControl, {
			props: {
				renderers: null,
				uischema,
				schema,
			},
			global: {
				...global.getGlobalMediaWikiMountingOptions( { jsonform } ),
			},
		} );

		jest.runAllTimers();

		// set user input
		await wrapper.get( 'input' ).setValue( userInput );
		await wrapper.get( 'input' ).trigger( 'keyup', { key: ' ' } );

		jest.advanceTimersByTime( 300 );

		const updatedTitles = initialTemplateTitles.concat( [ userInput ] );
		await wrapper.vm.onTitlesUpdated( updatedTitles );

		// data should now include the selected custom input
		expect( reactiveData.GEInfoboxTemplates ).toContain( userInput );
	} );

} );

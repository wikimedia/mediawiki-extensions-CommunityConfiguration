'use strict';
const { mount } = require( '@vue/test-utils' );
const NamespacesControl = require( './NamespacesControl.vue' );
const { ref, reactive } = require( 'vue' );

// TODO: DRY this up, it is repeated in multiple tests.
const mwMessageFake = jest.fn( ( textReturnValue ) => ( {
	exists: jest.fn( () => true ),
	text: jest.fn( () => textReturnValue ),
} ) );

jest.useFakeTimers();
describe( 'NamespacesControl', () => {
	const schema = {
		type: 'object',
		control: 'MediaWiki\\Extension\\CommunityConfiguration\\Controls\\NamespacesControl',
		default: '',
	};
	const uischema = {
		name: 'GEHelpPanelExcludedNamespaces',
		scope: '#/properties/GEHelpPanelExcludedNamespaces',
		type: 'Control',
		label: mwMessageFake( 'growthexperiments-edit-config-help-panel-disabled-namespaces' ),
	};
	const initialNamespaces = {
		GEHelpPanelExcludedNamespaces: [],
	};
	it( 'allows the user to select input from the dropdown menu', async () => {
		const userInput = 'Talk';
		const reactiveData = reactive( initialNamespaces );
		const jsonform = {
			schema,
			uischema,
			config: {
				canEdit: true,
				i18nPrefix: 'communityconfiguration-helppanel',
				i18nMessages: { 'communityconfiguration-helppanel-title': 'Help panel' },
				namespaceSelectorOptions: [
					'(Main)',
					'Talk',
					'User',
				],
			},
			data: reactiveData,
			renderers: [],
			errors: ref( [] ),
		};
		const wrapper = mount( NamespacesControl, {
			props: {
				id: 'GEHelpPanelExcludedNamespaces',
				renderers: null,
				uischema,
				schema,
			},
			global: {
				...global.getGlobalMediaWikiMountingOptions( { jsonform,
					EDITOR_FORM_CONFIG: {
						namespaceSelectorOptions: {
							0: '(Main)',
							1: 'Talk',
							2: 'User',
						},
					},
				} ),
			},
		} );

		jest.runAllTimers();

		await wrapper.get( 'input' ).setValue( userInput );

		jest.advanceTimersByTime( 300 );

		await wrapper.vm.onInput( userInput );
		const updatedTitles = [ userInput ];
		await wrapper.vm.onNamespacesUpdated( updatedTitles );

		// data should now include the selected input
		expect( reactiveData.GEHelpPanelExcludedNamespaces ).toContain( 1 );
	} );
} );

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
	it( 'allows the user to select input from the dropdown menu', async () => {
		const initialNamespaces = {
			GEHelpPanelExcludedNamespaces: [],
		};
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
		await wrapper.vm.onNamespacesUpdated( [ 1 ] );

		// data should now include the selected input
		expect( reactiveData.GEHelpPanelExcludedNamespaces ).toContain( 1 );
	} );
	it( 'allows the user to query results via text input', async () => {
		const initialNamespaces = {
			GEHelpPanelExcludedNamespaces: [],
		};
		let userInput = '';
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

		userInput = 'Talq';
		await wrapper.get( 'input' ).setValue( userInput );

		jest.advanceTimersByTime( 300 );

		// Types non-existent namespace
		await wrapper.vm.onInput( userInput );
		// data should now include the selected input
		expect( wrapper.vm.menuItems ).toEqual( [] );

		userInput = 'Talk';
		await wrapper.get( 'input' ).setValue( userInput );
		jest.advanceTimersByTime( 300 );

		// Types existent namespace
		await wrapper.vm.onInput( userInput );
		// data should now include the selected input
		expect( wrapper.vm.menuItems ).toEqual( [ {
			value: 1,
			label: 'Talk',
		} ] );
	} );
	it( 'allows the user to add a namespaces via menu item click', async () => {
		const initialNamespaces = {
			GEHelpPanelExcludedNamespaces: [ 1 ],
		};
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

		await wrapper.get( 'input' ).trigger( 'focus' );

		// data should now include all configured namesapces
		expect( wrapper.vm.menuItems ).toHaveLength( 3 );

		await wrapper.findComponent( '.cdx-menu-item' ).trigger( 'click' );
		expect( reactiveData.GEHelpPanelExcludedNamespaces ).toEqual( [ 1, 0 ] );
	} );
} );

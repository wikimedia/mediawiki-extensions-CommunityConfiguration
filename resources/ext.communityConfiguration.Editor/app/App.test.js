'use strict';
jest.mock( './icons.json', () => ( {
	cdxIconLinkExternal: 'link external',
} ), { virtual: true } );
jest.mock( '../lib/icons.json', () => ( {} ), { virtual: true } );
const { mount } = require( '@vue/test-utils' );
// FIXME the mock needs to be set before the require(App.vue)
// statement, move the read of mw.config.get( 'wgFormattedNamespaces' )
// in NamespacesControl out of the component load code path.
global.mw.config.get.mockImplementation( ( key ) => {
	switch ( key ) {
		case 'wgFormattedNamespaces':
			return {
				0: '',
				1: 'Talk',
				2: 'User',
				3: 'User talk',
				4: 'Dev',
				5: 'Dev talk',
				6: 'File',
				7: 'File talk',
				8: 'MediaWiki',
				9: 'MediaWiki talk',
				10: 'Template',
				11: 'Template talk',
				12: 'Help',
				13: 'Help talk',
				14: 'Category',
				15: 'Category talk',
				2600: 'Topic',
				'-2': 'Media',
				'-1': 'Special',
			};
		default:
			throw new Error( 'Unkown key: ' + key );
	}
} );
const App = require( './App.vue' );
const { JsonForm } = require( '../lib/json-form/form/index.js' );
const EditSummaryDialog = require( './components/EditSummaryDialog.vue' );
const EditorMessage = require( './components/EditorMessage.vue' );

describe( 'ext.communityConfiguration.Editor App', () => {
	const CONFIG_DATA = { some: 'data' };
	const JSON_SCHEMA = { some: 'schema', properties: {} };
	it( 'displays the app', () => {
		const wrapper = mount( App, {
			global: global.getGlobalAppMountingOptions(
				{ CONFIG_DATA, JSON_SCHEMA },
			),
		} );
		expect( wrapper.getComponent( JsonForm ).props( 'schema' ) ).toEqual( JSON_SCHEMA );
		expect( wrapper.getComponent( JsonForm ).props( 'data' ) ).toEqual( CONFIG_DATA );
	} );
} );

describe( 'Notice Messages', () => {
	it( 'displays notice message when user cannot edit', () => {
		const wrapper = mount( App, {
			global: global.getGlobalAppMountingOptions(
				{ CAN_EDIT: false },
			),
		} );

		expect( wrapper.html() ).toContain( 'communityconfiguration-editor-client-notice-message' );
		expect( wrapper.html() ).toContain( 'communityconfiguration-editor-client-notice-footer-message' );
	} );
} );

describe( 'submit results', () => {
	it( 'shows a success message if submitting was successful', async () => {
		const wrapper = mount( App, {
			global: global.getGlobalAppMountingOptions(
				{
					WRITING_REPOSITORY: {
						writeConfigurationData: jest.fn().mockResolvedValue( {} ),
					},
				},
			),
		} );

		await wrapper.getComponent( EditSummaryDialog ).vm.$emit( 'primary' );
		await wrapper.vm.$nextTick();

		expect( wrapper.html() ).toContain( 'communityconfiguration-editor-client-success-message' );
		expect( wrapper.getComponent( EditorMessage ).props( 'status' ) ).toBe( 'success' );
	} );

	it( 'shows a generic error if there is an internal server error', async () => {
		const errorCode = 'internal_api_error_RuntimeException';
		const htmlErrorMessage = '[183c02467dd6df6e8690dc68] Exception caught: Did not work';
		const trace = 'Trace';
		const response = {
			errors: [
				{
					code: errorCode,
					html: htmlErrorMessage,
					data: {
						errorclass: 'RuntimeException',
					},
				},
			],
			trace,
		};
		const wrapper = mount( App, {
			global: global.getGlobalAppMountingOptions(
				{
					WRITING_REPOSITORY: {
						writeConfigurationData: jest.fn().mockRejectedValue(
							[ errorCode, response ],
						),
					},
				},
			),
		} );

		await wrapper.getComponent( EditSummaryDialog ).vm.$emit( 'primary' );
		// It really needs two. ¯\_(ツ)_/¯
		await wrapper.vm.$nextTick();
		await wrapper.vm.$nextTick();

		expect( wrapper.html() ).toContain( 'communityconfiguration-editor-client-data-submission-error' );
		expect( wrapper.html() ).toContain( errorCode );
		expect( wrapper.html() ).toContain( htmlErrorMessage );
		expect( wrapper.html() ).toContain( trace );
		// TODO: assert feedback URL?
		expect( wrapper.getComponent( EditorMessage ).props( 'status' ) ).toBe( 'error' );
	} );

	it( 'shows a generic error if there is no network', async () => {
		const xhr = {};
		const textStatus = 'error';
		const exception = '';
		const errorCode = 'http';
		const wrapper = mount( App, {
			global: global.getGlobalAppMountingOptions(
				{
					WRITING_REPOSITORY: {
						writeConfigurationData: jest.fn().mockRejectedValue(
							[ errorCode, { xhr, textStatus, exception } ],
						),
					},
				},
			),
		} );

		await wrapper.getComponent( EditSummaryDialog ).vm.$emit( 'primary' );
		await wrapper.vm.$nextTick();
		await wrapper.vm.$nextTick();

		expect( wrapper.html() ).toContain( 'communityconfiguration-editor-client-data-submission-error' );
		expect( wrapper.html() ).toContain( errorCode );
		expect( wrapper.getComponent( EditorMessage ).props( 'status' ) ).toBe( 'error' );
	} );

	it( 'shows a permissions error if user is missing rights', async () => {
		const response = {
			errors: [
				{
					code: 'protectednamespace-interface',
					html: 'This page provides interface text for the software on this wiki, and is protected to prevent abuse. To add or change translations for all wikis, please use <a rel="nofollow" class="external text" href="https://translatewiki.net/">translatewiki.net</a>, the MediaWiki localisation project.',
					module: 'communityconfigurationedit',
				},
				{
					code: 'sitejsonprotected',
					html: 'You do not have permission to edit this JSON page because it may affect all visitors.',
					module: 'communityconfigurationedit',
				},
			],
		};
		const wrapper = mount( App, {
			global: global.getGlobalAppMountingOptions(
				{
					WRITING_REPOSITORY: {
						writeConfigurationData: jest.fn().mockRejectedValue(
							[ 'protectednamespace-interface', response ],
						),
					},
				},
			),
		} );

		await wrapper.getComponent( EditSummaryDialog ).vm.$emit( 'primary' );
		await wrapper.vm.$nextTick();
		await wrapper.vm.$nextTick();

		expect( wrapper.html() ).toContain( response.errors[ 0 ].html );
		expect( wrapper.html() ).toContain( response.errors[ 0 ].html );
		expect( wrapper.getComponent( EditorMessage ).props( 'status' ) ).toBe( 'error' );
	} );
} );

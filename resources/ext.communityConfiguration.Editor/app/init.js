( function () {
	'use strict';
	const Vue = require( 'vue' );
	const { getEditorTextKeys } = require( '../lib/json-form/core/index.js' );

	/**
	 * Load Editor messages from the MW api
	 *
	 * @param {Array<string>} messages List of messages to load
	 * @return {jQuery.Promise}
	 */
	function loadMessages( messages ) {
		const amlang = mw.config.get( 'wgUserLanguage' );
		return new mw.Api().getMessages( messages, { amlang } ).then( ( existingMessages ) => {
			Object.keys( existingMessages ).forEach( ( k ) => {
				mw.messages.set( k, existingMessages[ k ] );
			} );
		} );
	}

	/**
	 * Maybe retrieve server exported data for the editor
	 *
	 * @return {Promise<{ schema: Object, data: Object }>} The community configuration server
	 * exported JsVars. Includes the JSON schema and configuration data.
	 */
	function getServerData() {
		const config = mw.config.get( 'communityConfigurationData' );
		if ( !config ) {
			throw new Error( 'ext.communityConfiguration.Editor can only run with a config' );
		}
		const editorMessages = getEditorTextKeys( config.schema, config.data, {
			i18nTextKeyPrefix: config.config.i18nPrefix
		} );
		return loadMessages( editorMessages ).then( () => config );
	}

	/**
	 * Setup the Editor Vue application.
	 *
	 * Defines global Vue providers:
	 * - {Object} CONFIG_DATA: the stored data for the configuration
	 * - {Object} JSON_SCHEMA: the JSON schema for the configuration
	 * - {string} PROVIDER_NAME: the name of the provider that registered
	 * the configuration
	 *
	 * @param {Object} data Impact Vue application condiff
	 * @param {string} data.schema the JSON schema for the configuration
	 * Must exist in the document before calling this function.
	 * @param {Object} data.data the stored data for the configuration
	 * @param {Object} data.config the editor configuration (eg: i18n prefix)
	 * @return {Object} A Vue app instance
	 */
	const createApp = ( data ) => {
		const wrapper = require( './App.vue' );
		const app = Vue.createMwApp( wrapper );
		app.provide( 'CONFIG_DATA', data.data );
		app.provide( 'JSON_SCHEMA', data.schema );
		app.provide( 'PROVIDER_ID', data.providerId );
		app.provide( 'EDITOR_FORM_CONFIG', data.config );
		app.provide( 'CAN_EDIT', data.config.canEdit );
		app.mount( '#ext-communityConfiguration-app-root' );
		return app;
	};

	getServerData().then(
		( serverData ) => {
			// Hide loading animation
			document.getElementsByClassName( 'ext-communityConfiguration-LoadingBar' )
				.item( 0 )
				.remove();

			createApp( serverData );
		},
		// eslint-disable-next-line no-console
		( err ) => console.error( err )
	);

}() );

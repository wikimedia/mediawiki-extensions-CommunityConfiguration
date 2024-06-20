( function () {
	'use strict';
	const Vue = require( 'vue' );
	const MwApiCommunityConfigurationWritingRepository = require( './MwApiCommunityConfigurationWritingRepository.js' );

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
		const writingRepository = new MwApiCommunityConfigurationWritingRepository( new mw.Api() );
		const wrapper = require( './App.vue' );
		const app = Vue.createMwApp( wrapper );
		app.provide( 'WRITING_REPOSITORY', writingRepository );
		app.provide( 'CONFIG_DATA', data.data );
		app.provide( 'JSON_SCHEMA', data.schema );
		app.provide( 'PROVIDER_ID', data.providerId );
		app.provide( 'EDITOR_FORM_CONFIG', data.config );
		app.provide( 'CAN_EDIT', data.config.canEdit );
		app.mount( '#ext-communityConfiguration-app-root' );
		return app;
	};

	// Retrieve the server exported data
	const communityConfigurationJSConfig = mw.config.get( 'communityConfigurationData' );
	if ( !communityConfigurationJSConfig ) {
		throw new Error( 'ext.communityConfiguration.Editor can only run with a JS config' );
	}
	// Hide loading animation
	document.getElementsByClassName( 'ext-communityConfiguration-LoadingBar' )
		.item( 0 )
		.remove();

	// Add the provider-editor translation messages
	mw.messages.set( communityConfigurationJSConfig.config.i18nMessages );

	createApp( communityConfigurationJSConfig );

}() );

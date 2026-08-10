( function () {
	'use strict';
	const Vue = require( 'vue' );
	const MwApiCommunityConfigurationWritingRepository = require( './MwApiCommunityConfigurationWritingRepository.js' );
	const { loadCustomRenderers } = require( './customRenderers.js' );

	/**
	 * Setup the Editor Vue application.
	 *
	 * Defines global Vue providers:
	 * - {Object} CONFIG_DATA: the stored data for the configuration
	 * - {Object} JSON_SCHEMA: the JSON schema for the configuration
	 * - {Object|null} UI_SCHEMA: the layout of the form, when the schema has one
	 * - {Array} CUSTOM_RENDERERS: the controls that other extensions supply
	 * - {string} PROVIDER_NAME: the name of the provider that registered
	 * the configuration
	 *
	 * @param {Object} data Impact Vue application condiff
	 * @param {string} data.schema the JSON schema for the configuration
	 * Must exist in the document before calling this function.
	 * @param {Object} data.data the stored data for the configuration
	 * @param {Object} data.config the editor configuration (eg: i18n prefix)
	 * @param {Object|null} data.uiSchema the layout of the form
	 * @param {Array} customRenderers the controls that other extensions supply
	 * @return {Object} A Vue app instance
	 */
	const createApp = ( data, customRenderers ) => {
		const writingRepository = new MwApiCommunityConfigurationWritingRepository( new mw.Api() );
		const wrapper = require( './App.vue' );
		const app = Vue.createMwApp( wrapper );

		let errorLoggedDuringSession = false;
		app.config.errorHandler = ( err, instance, info ) => {
			if ( errorLoggedDuringSession ) {
				// Vue is prone to also bubble downstream errors => only log first error
				return;
			}
			errorLoggedDuringSession = true;
			const context = {};
			/* eslint-disable camelcase */
			context.vue_component_name = instance._ && instance._.type.name;
			context.vue_info = info;
			err.error_context = context;
			/* eslint-enable camelcase */
			mw.errorLogger.logError( err, 'error.communityconfiguration.editor' );
		};

		app.provide( 'WRITING_REPOSITORY', writingRepository );
		app.provide( 'CONFIG_DATA', data.data );
		app.provide( 'JSON_SCHEMA', data.schema );
		app.provide( 'UI_SCHEMA', data.uiSchema || null );
		app.provide( 'CUSTOM_RENDERERS', customRenderers );
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
	// Add the provider-editor translation messages
	mw.messages.set( communityConfigurationJSConfig.config.i18nMessages );

	// The loading animation stays until the controls of other extensions are ready, or the page
	// would show a gap between the animation and the form.
	loadCustomRenderers( communityConfigurationJSConfig.controls ).then( ( customRenderers ) => {
		// The animation is decoration, so a missing element must not stop the form from
		// mounting. This code now runs after a promise, where a throw would be much harder to
		// see than it was when it ran with the module.
		const loadingBar = document.getElementsByClassName( 'ext-communityConfiguration-LoadingBar' )
			.item( 0 );
		if ( loadingBar ) {
			loadingBar.remove();
		}

		createApp( communityConfigurationJSConfig, customRenderers );
	} );

}() );

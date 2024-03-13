( function () {
	'use strict';
	const Vue = require( 'vue' );

	/**
	 * Maybe retrieve server exported data for the editor
	 *
	 * @return {{ schema: Object, data: Object }} The community configuration server exported
	 * JsVars. Includes the JSON schema and configuration data.
	 */
	function getServerData() {
		const config = mw.config.get( 'communityConfigurationData' );
		if ( !config ) {
			throw new Error( 'ext.communityConfiguration.Editor can only run with a config' );
		}
		return config;
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
	 * @return {Object} A Vue app instance
	 */
	const createApp = () => {
		const data = getServerData();
		const wrapper = require( './App.vue' );
		const app = Vue.createMwApp( wrapper );
		app.provide( 'CONFIG_DATA', data.data );
		app.provide( 'JSON_SCHEMA', data.schema );
		app.provide( 'PROVIDER_NAME', data.providerName );
		app.mount( '#ext-communityConfiguration-app-root' );
		return app;
	};

	createApp();

}() );

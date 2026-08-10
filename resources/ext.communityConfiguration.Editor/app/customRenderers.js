const {
	rankRenderer,
	uiControlIs,
} = require( 'ext.communityConfiguration.Editor.controls' );

// The rank of a control that the UI schema selects by name. See rankRenderer.
const CUSTOM_CONTROL_RANK = 10;

/**
 * Load the controls that other extensions supply, and make renderers of them.
 *
 * The server already added these modules to the page, so they arrive in the same ResourceLoader
 * batch as the editor and there is no second request. The wait is still necessary, because
 * ResourceLoader guarantees the order of dependencies but not the order in which two modules of
 * one batch run, and require() only works after the module ran.
 *
 * A module that fails to load does not stop the form. Its control is then absent from the
 * result, no tester matches the element, and the editor selects a control from the data schema
 * instead.
 *
 * @param {Object<string,{module: string, component: string}>} controls The controls of the
 * provider, indexed by control name
 * @return {Promise<Array<{renderer: Object, tester: Function}>>} Renderers to add to the
 * built-in ones
 */
function loadCustomRenderers( controls ) {
	const names = Object.keys( controls || {} );
	if ( names.length === 0 ) {
		return Promise.resolve( [] );
	}

	const modules = [ ...new Set( names.map( ( name ) => controls[ name ].module ) ) ];

	return mw.loader.using( modules )
		.then( () => names.reduce( ( renderers, name ) => {
			const { module, component } = controls[ name ];
			// The module name comes from the control registry on the server, which only keeps a
			// module that ResourceLoader has registered, so it is not free text.
			// eslint-disable-next-line security/detect-non-literal-require
			const renderer = require( module )[ component ];
			if ( !renderer ) {
				mw.log.warn(
					`json-form: the module ${module} does not export ${component}, ` +
					`so the control ${name} is not available.`,
				);
				return renderers;
			}
			renderers.push( rankRenderer( CUSTOM_CONTROL_RANK, renderer, uiControlIs( name ) ) );
			return renderers;
		}, [] ) )
		.catch( ( error ) => {
			mw.log.warn( 'json-form: could not load the controls of other extensions.', error );
			return [];
		} );
}

module.exports = exports = {
	loadCustomRenderers,
};

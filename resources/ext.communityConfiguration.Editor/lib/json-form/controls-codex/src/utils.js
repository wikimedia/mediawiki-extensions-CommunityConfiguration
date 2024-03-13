/**
 * Provides specific bindings for Codex components, including
 * the bindings for the wrapper CdxField. Adds an onChange
 * handler function for @update:model-value events.
 *
 * Access bindings for the input in the 'control' object. Access
 * bindings for the wrapper field in the 'controlWrapper' object.
 *
 * @param {Object} input the input props
 * @return {{ renderer: Object, rootSchema: Object }} Renderer bindings
 */
function useCodexControl( input ) {
	const onChange = ( newVal ) => {
		input.handleChange( newVal );
	};

	return Object.assign( {}, input, {
		onChange,
		controlWrapper: {
			name: input.control.uischema.name
		}
	} );
}

module.exports = exports = {
	useCodexControl
};

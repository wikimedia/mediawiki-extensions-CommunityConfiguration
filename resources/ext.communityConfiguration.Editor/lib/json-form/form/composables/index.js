const { inject } = require( 'vue' );

/**
 * Provides bindings for 'Control' elements.
 *
 * Access bindings via the `control` object.
 *
 * @param {Object} props
 * @return {{ control: Object }} Control bindings
 */
function useJsonFormControl( props ) {
	const jsonform = inject( 'jsonform' );

	if ( !jsonform ) {
		throw new Error( "'jsonform' couldn't be injected. Are you within <JsonForm>?" );
	}

	return {
		control: Object.assign( {}, props, {
			modelValue: jsonform.data[ props.uischema.name ]
		} ),
		handleChange( newVal ) {
			jsonform.data[ props.uischema.name ] = newVal;
		}
	};
}

/**
 * Provides bindings which can be used for any renderer, so far only 'Control'.
 *
 * Access bindings via the provided 'renderer' object.
 *
 * @param {Object} props
 * @return {{ renderer: Object, rootSchema: Object }} Renderer bindings
 */
const useJsonFormRenderer = ( props ) => {
	const jsonform = inject( 'jsonform' );

	if ( !jsonform ) {
		throw new Error( "'jsonform' couldn't be injected. Are you within <JsonForm>?" );
	}

	const renderer = {
		schema: jsonform.schema.properties[ props.uischema.name ],
		renderers: jsonform.renderers
	};

	return {
		renderer: Object.assign( {}, props, renderer ),
		rootSchema: jsonform.schema
	};
};

module.exports = exports = {
	useJsonFormControl,
	useJsonFormRenderer
};

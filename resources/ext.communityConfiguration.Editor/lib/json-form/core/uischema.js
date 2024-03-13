const { getControlTextProps } = require( './i18n.js' );

/**
 * Generates a data structure to hold information about the
 * UI the form should display.
 *
 * @param {Object} schema An object representing a JSON schema
 * @param {Object} config The editor form configuration
 * @param {string} initialScope The schema parent node defined as a "scope" string
 * @return {{ elements: Array<{ name: string, type: string }> }} An array
 * of elements describing the form controls for the properties in the schema.
 */
function buildUISchema( schema, config, initialScope = '' ) {
	const elements = [];
	for ( const prop in schema.properties ) {
		const defaults = {
			name: prop,
			type: 'Control',
			required: schema.required && schema.required.indexOf( prop ) !== -1,
			scope: initialScope ? `${initialScope}/properties/${prop}` : `#/properties/${prop}`
		};

		if ( Array.isArray( schema.required ) ) {
			defaults.required = schema.required.indexOf( prop ) !== -1;
		}

		const additionalProps = Object.assign( {}, getControlTextProps( prop, config.i18nPrefix ) );

		elements.push( Object.assign( {}, defaults, additionalProps ) );
	}
	return { elements };
}

module.exports = exports = {
	buildUISchema
};

/**
 * Generates a data structure to hold information about the
 * UI the form should display.
 *
 * @param {Object} schema An object representing a JSON schema
 * @return {{ elements: Array<{ name: string, type: string }> }} An array
 * of elements describing the form controls for the properties in the schema.
 */
function buildUISchema( schema ) {
	const elements = [];
	for ( const prop in schema.properties ) {
		elements.push( {
			name: prop,
			type: 'Control'
		} );
	}
	return { elements };
}

module.exports = exports = {
	buildUISchema
};

/**
 * TODO: The functions in this file should live someplace else
 */

/**
 * Some arrays are rendered as a single control with their items not directly addressable.
 * In that case the pointer targeting an item needs to be retargeted to the parent array instead.
 *
 * @param {Object} rootSchema
 * @param {string} pointer
 * @return {string}
 */
function adjustPointerForValidationErrors( rootSchema, pointer ) {
	function ensureTargetIsArrayNotItem( pointerToAdjust ) {
		const parts = pointerToAdjust.split( '/' );
		if ( Number.isInteger( Number.parseInt( parts.pop() ) ) ) {
			return parts.join( '/' );
		}
		return pointerToAdjust;
	}

	const path = pointer.split( '/' ).slice( 1 );
	let subSchema = rootSchema;
	for ( const p of path ) {
		if ( subSchema.type === 'object' ) {
			subSchema = subSchema.properties[ p ];
		} else if ( subSchema.type === 'array' && (
			subSchema.control || subSchema.items.type === 'string'
		) ) {
			return ensureTargetIsArrayNotItem( pointer );
		} else if ( subSchema.type === 'array' ) {
			subSchema = subSchema.items;
		}
	}
	return pointer;
}

module.exports = exports = {
	adjustPointerForValidationErrors
};

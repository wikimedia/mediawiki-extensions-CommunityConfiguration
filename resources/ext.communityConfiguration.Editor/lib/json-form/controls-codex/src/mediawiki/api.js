/**
 * Get search results.
 *
 * @param {string} searchTerm
 * @param {number} offset Optional result offset
 *
 * @return {Promise}
 */
function search( searchTerm, offset ) {
	const params = {
		action: 'query',
		list: 'prefixsearch',
		format: 'json',
		pssearch: searchTerm
	};
	if ( offset ) {
		params.set( 'continue', String( offset ) );
	}
	return new mw.Api().get( params );
}

module.exports = exports = {
	search
};

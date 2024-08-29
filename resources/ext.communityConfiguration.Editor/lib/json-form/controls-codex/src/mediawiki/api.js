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
		pssearch: searchTerm,
	};
	if ( offset ) {
		params.set( 'continue', String( offset ) );
	}
	return new mw.Api().get( params );
}

function searchCommonsFiles( searchTerm, offset ) {
	const params = {
		action: 'query',
		generator: 'prefixsearch',
		format: 'json',
		formatversion: 2,
		gpssearch: searchTerm,
		gpsnamespace: 6,
		prop: 'imageinfo',
		iiprop: 'url',
	};

	if ( offset ) {
		params.set( 'continue', String( offset ) );
	}
	return new mw.ForeignApi( 'https://commons.wikimedia.org/w/api.php', { anonymous: true } ).get( params );
}

module.exports = exports = {
	search,
	searchCommonsFiles,
};

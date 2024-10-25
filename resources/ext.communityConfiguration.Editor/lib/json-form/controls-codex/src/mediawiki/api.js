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

function MwForeignApi( baseUrl ) {
	if ( !baseUrl ) {
		const error = new Error( '[MwForeignApi] Invalid param baseUrl' );
		mw.errorLogger.logError( error, 'error.communityconfiguration' );
		throw error;
	}
	this.baseUrl = baseUrl;
	this.api = new mw.ForeignApi( this.baseUrl, { anonymous: true } );
}

MwForeignApi.prototype.searchCommonsFiles = function ( searchTerm, offset ) {
	const params = {
		action: 'query',
		generator: 'prefixsearch',
		format: 'json',
		formatversion: 2,
		gpssearch: searchTerm,
		gpsnamespace: 6,
		prop: 'imageinfo',
		iiprop: 'url',
		iiurlwidth: 80,
	};

	if ( offset ) {
		params.set( 'continue', String( offset ) );
	}
	return this.api.get( params ).catch( ( err ) => {
		err = err instanceof Error ? err : new Error( err );
		mw.errorLogger.logError( err, 'error.communityconfiguration' );
		return Promise.reject( err );
	} );
};

module.exports = exports = {
	search,
	MwForeignApi,
};

module.exports = exports = class MwApiCommunityConfigurationWritingRepository {
	constructor( mwApi ) {
		this.mwApi = mwApi;
	}

	writeConfigurationData( providerId, formData, summary ) {
		return new Promise( ( resolve, reject ) => {
			this.mwApi.postWithToken( 'csrf', {
				action: 'communityconfigurationedit',
				provider: providerId,
				content: JSON.stringify( formData ),
				summary: summary,
				formatversion: 2,
				errorformat: 'html',
			} ).then( resolve, ( ...args ) => reject( args ) );
		} );
	}
};

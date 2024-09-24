'use strict';
const api = require( './api.js' );

describe( 'API searchCommons', () => {
	it( 'handle API error', async () => {
		class fakeApi {
			get() {
				return Promise.reject( 'Some error' );
			}
		}
		mw.ForeignApi = fakeApi;
		mw.errorLogger = { logError: jest.fn() };

		await expect( api.searchCommonsFiles( 'input', 0 ) ).rejects.toThrow(
			'Some error',
		);
		expect( mw.errorLogger.logError ).toHaveBeenCalledTimes( 1 );
	} );
} );

'use strict';
const { MwForeignApi } = require( './api.js' );

describe( 'MwForeignApi searchCommons', () => {
	it( 'handle missing API URL error', async () => {
		class FakeApi {
			get() {
				return Promise.resolve( {} );
			}
		}
		mw.ForeignApi = FakeApi;
		mw.errorLogger = { logError: jest.fn() };

		expect( MwForeignApi ).toThrow( '[MwForeignApi] Invalid param baseUrl' );
		expect( mw.errorLogger.logError ).toHaveBeenCalledTimes( 1 );
	} );
	it( 'handle API error', async () => {
		class FakeApi {
			get() {
				return Promise.reject( 'Some error' );
			}
		}
		mw.config.get = jest.fn().mockReturnValueOnce( 'some.url' );
		mw.ForeignApi = FakeApi;
		mw.errorLogger = { logError: jest.fn() };

		await expect( new MwForeignApi( 'http://some.url' ).searchCommonsFiles( 'input', 0 ) ).rejects.toThrow(
			'Some error',
		);
		expect( mw.errorLogger.logError ).toHaveBeenCalledTimes( 1 );
	} );
} );

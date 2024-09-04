/**
 * Helper methods for generic MediaWiki API functionality separate from the Cypress browser context
 *
 * This file is intended to be extracted into a separate npm package,
 * so that it can be used across extensions.
 */

// needed for api-testing library, see api-testing/lib/config.js
process.env.REST_BASE_URL = process.env.MW_SERVER + process.env.MW_SCRIPT_PATH + '/';

import { clientFactory } from 'api-testing';

const state = {
	users: {},
};

function debugLog( ...args: unknown[] ): void {
	if ( !process.env.MW_DEBUG ) {
		return;
	}
	console.log( ...args );
}

module.exports = {
	mwApiCommands( cypressConfig ) {
		// TODO: replace the `any` once the api-testing library type definitions available
		// eslint-disable-next-line @typescript-eslint/no-explicit-any
		async function root(): Promise<any> {
			if ( state.users.root ) {
				return state.users.root;
			}

			debugLog( 'Getting new root user client' );
			const rootClient = clientFactory.getActionClient( null );
			await rootClient.login(
				cypressConfig.mediawikiAdminUsername,
				cypressConfig.mediawikiAdminPassword,
			);
			await rootClient.loadTokens( [ 'createaccount', 'userrights', 'csrf' ] );

			const rightsToken = await rootClient.token( 'userrights' );
			if ( rightsToken === '+\\' ) {
				throw new Error( 'Failed to get the root user tokens.' );
			}

			state.users.root = rootClient;
			return rootClient;
		}

		return {
			async 'MwApi:UpdateConfiguration'( { provider, configData, summary } ) {
				const rootUser = await root();

				const token = await rootUser.token( 'csrf' );

				const editResult = await rootUser.action( 'communityconfigurationedit', {
					provider,
					content: JSON.stringify( configData, null, 2 ),
					summary,
					token,
					formatversion: 2,
				}, true );

				if ( editResult.communityconfigurationedit.result !== 'success' ) {
					console.error( 'Configuration update failed', editResult );
					return Promise.reject( new Error( 'Configuration update failed: ' + editResult.communityconfigurationedit.result ) );
				}

				return Promise.resolve( null );
			},
		};
	},
};

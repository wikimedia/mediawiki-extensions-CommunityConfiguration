import { defineConfig } from 'cypress';
import { mwApiCommands } from './cypress/support/MwApiPlugin.ts';

const envLogDir = process.env.LOG_DIR ? process.env.LOG_DIR + '/CommunityConfiguration' : null;

if ( process.env.MW_SERVER === undefined || process.env.MW_SCRIPT_PATH === undefined ||
	process.env.MEDIAWIKI_USER === undefined || process.env.MEDIAWIKI_PASSWORD === undefined ) {
	throw new Error( 'Please define MW_SERVER, MW_SCRIPT_PATH, ' +
		'MEDIAWIKI_USER and MEDIAWIKI_PASSWORD environment variables' );
}
process.env.REST_BASE_URL = process.env.MW_SERVER + process.env.MW_SCRIPT_PATH + '/';

export default defineConfig( {
	e2e: {
		supportFile: 'cypress/support/e2e.ts',
		baseUrl: process.env.MW_SERVER + process.env.MW_SCRIPT_PATH,
		mediawikiAdminUsername: process.env.MEDIAWIKI_USER,
		mediawikiAdminPassword: process.env.MEDIAWIKI_PASSWORD,
		setupNodeEvents( on, config ) {
			on( 'task', {
				...mwApiCommands( config ),
			} );
		},
		defaultCommandTimeout: 20000,
	},
	screenshotsFolder: envLogDir || 'cypress/screenshots',
	videosFolder: envLogDir || 'cypress/videos',
	video: true,
	downloadsFolder: envLogDir || 'cypress/downloads',
} );

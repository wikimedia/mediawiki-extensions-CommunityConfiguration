const PHAB_PROJECT_NAME = 'communityconfiguration';

const wrapDescription = ( title, message, trace ) => {
	const url = `* URL: ${location.href}`;
	const version = `* Version: ${mw.config.get( 'wgVersion' )}`;
	const msg = `\`\`\`name=message\n${message}\n${title}\`\`\``;
	const stack = `\`\`\`name=trace,lines=10\n${trace}\`\`\``;
	return [
		'==== Error ====',
		msg,
		stack,
		'==== Impact ====',
		'==== Notes ====',
		url,
		version
	].join( '\n' );
};

module.exports = exports = {
	configurePhabricatorURL( baseURL, title, message, trace ) {
		// Configure only Phabricator URLs, assume it points to a task template,
		// eg: https://phabricator.wikimedia.org/maniphest/task/edit/form/43
		if ( baseURL.indexOf( 'phabricator.wikimedia.org' ) !== -1 ) {
			if ( baseURL.endsWith( '/' ) ) {
				throw new Error( 'CommunityConfigurationBugReportingToolURL should not contain a trailing slash' );
			}
			const params = new URLSearchParams( {
				title,
				description: wrapDescription( title, message, trace ),
				projects: PHAB_PROJECT_NAME,
				priority: 'triage'
			} );

			return baseURL + '/?' + params.toString();
		}
		return baseURL;
	}
};

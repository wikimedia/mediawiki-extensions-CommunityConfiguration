const { MEDIAWIKI_DEFINITIONS } = require( './constants.js' );
const PageTitleControl = require( './PageTitleControl.vue' );
const PageTitlesControl = require( './PageTitlesControl.vue' );
const NamespacesControl = require( './NamespacesControl.vue' );
const CommonsFileControl = require( './CommonsFileControl.vue' );

const {
	rankWith,
	schemaControlIs,
} = require( '../../config/index.js' );

const rankRenderer = ( rank, renderer, tester ) => ( {
	renderer,
	tester: rankWith( rank, tester ),
} );

const isPageTitleControl = schemaControlIs( MEDIAWIKI_DEFINITIONS.PAGE_TITLE );
const isPageTitlesControl = schemaControlIs( MEDIAWIKI_DEFINITIONS.PAGE_TITLES );
const isNamespacesControl = schemaControlIs( MEDIAWIKI_DEFINITIONS.NAMESPACES );
const isCommonsFileControl = schemaControlIs( MEDIAWIKI_DEFINITIONS.COMMONS_FILE );

module.exports = exports = {
	mediawiki: [
		// renderers ranked based on schema refs need to have
		// > 2 rank to precede basic type renderers (rank 1) and enums (rank 2)
		rankRenderer( 3, PageTitleControl, isPageTitleControl ),
		rankRenderer( 3, PageTitlesControl, isPageTitlesControl ),
		rankRenderer( 3, NamespacesControl, isNamespacesControl ),
		rankRenderer( 3, CommonsFileControl, isCommonsFileControl ),
	],
};

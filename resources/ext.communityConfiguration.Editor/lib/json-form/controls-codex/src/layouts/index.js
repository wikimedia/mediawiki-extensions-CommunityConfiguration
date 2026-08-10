const GroupLayout = require( './GroupLayout.vue' );
const {
	rankRenderer,
	uiTypeIs,
} = require( 'ext.communityConfiguration.Editor.controls' );

const isGroupLayout = uiTypeIs( 'Group' );

module.exports = exports = {
	layouts: [
		// A layout goes above the controls, because the tester of a control looks at the data
		// schema, which for a Group element is the schema of the parent. See rankRenderer.
		rankRenderer( 5, GroupLayout, isGroupLayout ),
	],
};

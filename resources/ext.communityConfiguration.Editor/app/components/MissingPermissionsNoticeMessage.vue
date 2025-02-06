<template>
	<editor-message
		status="notice"
		:is-dismissable="false"
	>
		<p>{{ $i18n( 'communityconfiguration-editor-client-notice-message' ).text() }}</p>
	</editor-message>
</template>

<script>
const EditorMessage = require( './EditorMessage.vue' );

// @vue/component
module.exports = exports = {
	name: 'MissingPermissionsNoticeMessage',
	components: {
		EditorMessage,
	},
};
</script>

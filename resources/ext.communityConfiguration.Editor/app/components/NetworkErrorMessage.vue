<template>
	<editor-message
		status="error"
		:is-dismissable="true"
	>
		<p>{{ $i18n( 'communityconfiguration-editor-client-data-submission-error' ) }}</p>
	</editor-message>
</template>

<script>
const EditorMessage = require( './EditorMessage.vue' );

// @vue/component
module.exports = exports = {
	name: 'NetworkErrorMessage',
	components: {
		EditorMessage,
	},
};
</script>

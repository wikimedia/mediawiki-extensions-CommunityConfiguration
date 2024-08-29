<template>
	<editor-message
		status="success"
		:is-dismissable="true"
	>
		<p>{{ $i18n( 'communityconfiguration-editor-client-success-message' ) }}</p>
	</editor-message>
</template>

<script>
const EditorMessage = require( './EditorMessage.vue' );

// @vue/component
module.exports = exports = {
	name: 'SuccessMessage',
	components: {
		EditorMessage,
	},
};
</script>

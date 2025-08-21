<template>
	<cdx-message
		:allow-user-dismiss="isDismissable"
		:dismiss-button-label="isDismissable ? $i18n(
			'communityconfiguration-editor-message-dismiss-button-label' ).text() : ''"
		:type="status"
		:fade-in="true"
		:auto-dismiss="status === 'success'"
	>
		<slot></slot>
	</cdx-message>
</template>

<script>
const { CdxMessage } = require( '../../codex.js' );
// @vue/component
module.exports = exports = {
	name: 'EditorMessage',
	components: {
		CdxMessage,
	},
	props: {
		// Allowed values: 'notice', 'warning', 'error', 'success'
		status: {
			type: String,
			default: 'notice',
		},
		isDismissable: {
			type: Boolean,
			default: false,
		},
	},
};

</script>

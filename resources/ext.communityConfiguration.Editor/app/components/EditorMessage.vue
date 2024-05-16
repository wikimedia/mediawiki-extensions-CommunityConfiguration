<template>
	<cdx-message
		class="ext-communityConfiguration-EditorMessage"
		:dismiss-button-label="isDismissable && $i18n(
			'communityconfiguration-editor-message-dismiss-button-label' ).text()"
		:type="status"
		:fade-in="true"
		:auto-dismiss="status === 'success'"
	>
		<slot name="success">
		</slot>
		<slot name="error">
		</slot>
		<slot></slot>
	</cdx-message>
</template>

<script>
const { CdxMessage } = require( '@wikimedia/codex' );
// @vue/component
module.exports = exports = {
	name: 'EditorMessage',
	components: {
		CdxMessage
	},
	props: {
		// Allowed values: 'notice', 'warning', 'error', 'success'
		status: {
			type: String,
			default: 'notice'
		},
		isDismissable: {
			type: Boolean,
			default: false
		}
	}
};

</script>

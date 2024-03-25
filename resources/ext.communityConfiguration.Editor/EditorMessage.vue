<template>
	<cdx-message
		class="ext-communityConfiguration-EditorMessage"
		:type="status"
		:fade-in="true"
		:auto-dismiss="true"
	>
		<p><strong>{{ $i18n( 'communityconfiguration-editor-client-generic-error' ).text() }}</strong></p>
		<p>{{ message }}</p>
		<p v-if="messageDetail.stack">
			{{ messageDetail.stack }}
		</p>
		<p
			v-if="bugURL"
			v-i18n-html:communityconfiguration-editor-client-file-bug="[ bugURL ]"
		>
		</p>
	</cdx-message>
</template>

<script>
const { CdxMessage } = require( '@wikimedia/codex' );
const { configurePhabricatorURL } = require( './utils.js' );

// @vue/component
module.exports = exports = {
	name: 'EditorMessage',
	components: {
		CdxMessage
	},
	props: {
		fileBugUrl: {
			type: String,
			default: null
		},
		message: {
			type: String,
			required: true
		},
		messageDetail: {
			type: Object,
			default: () => ( {} )
		},
		// Allowed values: 'notice', 'warning', 'error', 'success'
		status: {
			type: Boolean,
			default: false
		}
	},
	setup( props ) {
		const bugURL = configurePhabricatorURL(
			props.fileBugUrl,
			// Use the JS error message as the title rather than the
			// user readable message
			props.messageDetail.toString(),
			props.message,
			// Strip first 800 chars of the stacktrace to prevent to long URLs
			`${props.messageDetail.stack.slice( 0, 800 )}...`
		);
		return {
			bugURL
		};
	}
};

</script>

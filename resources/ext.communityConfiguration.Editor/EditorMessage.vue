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
		<p v-if="bugURL">
			{{ $i18n( 'communityconfiguration-editor-client-file-bug' ).text() }}
			<a
				class="ext-communityConfiguration-EditorMessage__link"
				target="_blank"
				:href="bugURL"
			>
				{{ $i18n( 'communityconfiguration-editor-client-file-bug-link-content' ).text() }}
				<cdx-icon :icon="cdxIconLinkExternal"></cdx-icon>
			</a>
		</p>
	</cdx-message>
</template>

<script>
const { CdxMessage, CdxIcon } = require( '@wikimedia/codex' );
const { cdxIconLinkExternal } = require( './icons.json' );
const { configurePhabricatorURL } = require( './utils.js' );

// @vue/component
module.exports = exports = {
	name: 'EditorMessage',
	components: {
		CdxMessage,
		CdxIcon
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
			bugURL,
			cdxIconLinkExternal
		};
	}
};

</script>

<style lang="less">
@import 'mediawiki.skin.variables.less';

.ext-communityConfiguration-EditorMessage {
	&__link {
		.cdx-mixin-link();
		// stylelint-disable-next-line selector-class-pattern
		.cdx-icon {
			color: inherit;
		}
	}
}
</style>

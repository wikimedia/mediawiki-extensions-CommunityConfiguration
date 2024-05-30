<template>
	<editor-message
		status="error"
		:is-dismissable="true"
	>
		<p>
			<strong>{{ i18n( 'communityconfiguration-editor-client-generic-error' ).text() }}</strong>
		</p>
		<p>
			{{
				i18n(
					'communityconfiguration-editor-client-generic-error-description',
					componentName,
					info
				).text()
			}}
		</p>
		<p v-if="err && err.stack">
			{{ err.stack }}
		</p>
		<p
			v-if="feedbackURL"
			v-i18n-html:communityconfiguration-editor-client-post-feedback="[ feedbackURL ]"
		></p>
	</editor-message>
</template>

<script>
const EditorMessage = require( './EditorMessage.vue' );

// @vue/component
module.exports = exports = {
	name: 'ClientErrorMessage',
	components: {
		EditorMessage
	},
	props: {
		componentName: {
			type: String,
			required: true
		},
		info: {
			type: String,
			required: true
		},
		err: {
			type: Object,
			required: true
		},
		feedbackURL: {
			type: String,
			default: null
		}
	}
};
</script>

<template>
	<editor-message
		status="error"
		:is-dismissable="true"
	>
		<p>{{ $i18n( 'communityconfiguration-editor-client-validation-errors', errors.length ) }}</p>
		<ul>
			<li
				v-for="( error, index ) in errors"
				:key="index"
			>
				<a :href="`#${error.formFieldId}`">{{ error.formFieldId /* TODO: replace with field label */ }}</a>
			</li>
		</ul>
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
	name: 'ValidationErrorMessage',
	components: {
		EditorMessage
	},
	props: {
		errors: {
			type: Array,
			required: true
		},
		feedbackURL: {
			type: String,
			default: null
		}
	}
};
</script>

<template>
	<editor-message
		status="error"
		:is-dismissable="true"
	>
		<p>{{ $i18n( 'communityconfiguration-editor-client-data-submission-error' ) }}</p>
		<!-- eslint-disable vue/no-v-html -- MediaWiki guarantees the HTML is safe -->
		<p>{{ errorCode }}</p>
		<ul
			v-if="errorResponse.errors"
		>
			<li
				v-for="( error, index ) in errorResponse.errors"
				:key="index"
				v-html="error.html"
			></li>
		</ul>
		<pre
			v-if="errorResponse.trace"
		>{{ errorResponse.trace }}</pre>
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
	name: 'GenericSubmitErrorMessage',
	components: {
		EditorMessage,
	},
	props: {
		errorResponse: {
			type: Object,
			required: true,
		},
		errorCode: {
			type: String,
			required: true,
		},
		feedbackURL: {
			type: String,
			default: null,
		},
	},
};
</script>

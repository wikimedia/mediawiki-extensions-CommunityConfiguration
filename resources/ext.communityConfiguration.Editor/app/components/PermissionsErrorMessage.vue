<template>
	<editor-message
		status="error"
		:is-dismissable="true"
	>
		<!-- eslint-disable vue/no-v-html -- MediaWiki guarantees the HTML is safe -->
		<p
			v-for="( error, index ) in errors"
			:key="index"
			v-html="error.html"
		>
		</p>
	</editor-message>
</template>

<script>
const EditorMessage = require( './EditorMessage.vue' );

// @vue/component
module.exports = exports = {
	name: 'PermissionsErrorMessage',
	components: {
		EditorMessage
	},
	props: {
		errors: {
			type: Array,
			required: true
		}
	}
};
</script>

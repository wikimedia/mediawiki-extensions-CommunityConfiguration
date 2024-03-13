<template>
	<div class="ext-communityConfiguration-App">
		<json-form
			:config="editorFormConfig"
			:data="configData"
			:renderers="renderers"
			:schema="schema"
			@submit="onSubmit"
		>
			<template #submit>
				<cdx-button
					action="progressive"
					weight="primary"
					:disabled="isLoading"
				>
					{{ isLoading ? 'Sending...' : 'Submit' }}
				</cdx-button>
			</template>
		</json-form>
	</div>
</template>

<script>
const { inject, ref } = require( 'vue' );
const { CdxButton } = require( '@wikimedia/codex' );
const { JsonForm } = require( './lib/json-form/form/index.js' );
const { renderers } = require( './lib/json-form/controls-codex/src/index.js' );

// @vue/component
module.exports = exports = {
	name: 'App',
	components: {
		JsonForm,
		CdxButton
	},
	setup: function () {
		const configData = inject( 'CONFIG_DATA' );
		const schema = inject( 'JSON_SCHEMA' );
		const providerName = inject( 'PROVIDER_NAME' );
		const editorFormConfig = inject( 'EDITOR_FORM_CONFIG' );
		const isLoading = ref( false );

		function onSubmit( newData ) {
			isLoading.value = true;
			new mw.Api().postWithToken( 'csrf', {
				action: 'communityconfigurationedit',
				provider: providerName,
				content: JSON.stringify( newData ),
				// TODO: instead of directly submitting the data show the Edit summary
				// dialog and prompt for summary text (T354463).
				summary: 'Editor MVP test'
			} ).then( () => {
				isLoading.value = false;
				// TODO: show edit saved toast/notification (T359928)
			} ).catch( ( err ) => {
				// eslint-disable-next-line no-console
				console.error( err );
				isLoading.value = false;
				// TODO: show error toast/notification (T359928)
			} );
		}

		return {
			configData,
			editorFormConfig,
			isLoading,
			onSubmit,
			renderers,
			schema
		};
	}
};
</script>

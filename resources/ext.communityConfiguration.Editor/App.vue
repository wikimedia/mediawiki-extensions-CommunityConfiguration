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
		<edit-summary-dialog
			v-model:open="editSummaryOpen"
			v-model:summary="summary"
			:provider-name="providerName"
			@primary="doSubmit"
		></edit-summary-dialog>
	</div>
</template>

<script>
const { inject, ref } = require( 'vue' );
const { CdxButton } = require( '@wikimedia/codex' );
const { JsonForm } = require( './lib/json-form/form/index.js' );
const { renderers } = require( './lib/json-form/controls-codex/src/index.js' );
const EditSummaryDialog = require( './EditSummaryDialog.vue' );

// @vue/component
module.exports = exports = {
	name: 'App',
	components: {
		JsonForm,
		CdxButton,
		EditSummaryDialog
	},
	setup: function () {
		const configData = inject( 'CONFIG_DATA' );
		const schema = inject( 'JSON_SCHEMA' );
		const providerName = inject( 'PROVIDER_NAME' );
		const editorFormConfig = inject( 'EDITOR_FORM_CONFIG' );
		const isLoading = ref( false );
		const editSummaryOpen = ref( false );
		const summary = ref( '' );
		let tempFormData = null;

		function onSubmit( formData ) {
			tempFormData = formData;
			editSummaryOpen.value = true;
		}

		function doSubmit() {
			isLoading.value = true;
			new mw.Api().postWithToken( 'csrf', {
				action: 'communityconfigurationedit',
				provider: providerName,
				content: JSON.stringify( tempFormData ),
				summary: summary.value
			} ).then( () => {
				isLoading.value = false;
				resetForm();
				// TODO: show edit saved toast/notification (T359928)
			} ).catch( ( err ) => {
				// eslint-disable-next-line no-console
				console.error( err );
				isLoading.value = false;
				// TODO: show error toast/notification (T359928)
			} );
		}

		function resetForm() {
			tempFormData = null;
			editSummaryOpen.value = false;
			summary.value = '';
		}

		return {
			configData,
			doSubmit,
			editSummaryOpen,
			editorFormConfig,
			isLoading,
			onSubmit,
			providerName,
			renderers,
			schema,
			summary
		};
	}
};
</script>

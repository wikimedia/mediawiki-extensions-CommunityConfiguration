<template>
	<div class="ext-communityConfiguration-App">
		<editor-message
			v-if="showMessage"
			:status="messageStatus"
			:message="message"
			:message-detail="messageDetail"
			:file-bug-url="editorFormConfig.bugReportToolURL"
		>
		</editor-message>
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
					{{ isLoading ?
						$i18n( 'communityconfiguration-editor-form-submit-button-loading-text' ).text() :
						$i18n( 'communityconfiguration-editor-form-submit-button-text' ).text()
					}}
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
const { inject, ref, onErrorCaptured } = require( 'vue' );
const { CdxButton } = require( '@wikimedia/codex' );
const { JsonForm } = require( '../lib/json-form/form/index.js' );
const { renderers } = require( '../lib/json-form/controls-codex/src/index.js' );
const EditorMessage = require( './components/EditorMessage.vue' );
const EditSummaryDialog = require( './components/EditSummaryDialog.vue' );
let errorsDisplayed = 0;

// @vue/component
module.exports = exports = {
	name: 'App',
	components: {
		CdxButton,
		EditSummaryDialog,
		EditorMessage,
		JsonForm
	},
	setup: function () {
		const i18n = inject( 'i18n' );
		const configData = inject( 'CONFIG_DATA' );
		const schema = inject( 'JSON_SCHEMA' );
		const providerName = inject( 'PROVIDER_NAME' );
		const editorFormConfig = inject( 'EDITOR_FORM_CONFIG' );
		const isLoading = ref( false );
		const editSummaryOpen = ref( false );
		const summary = ref( '' );
		const showMessage = ref( false );
		const messageStatus = ref( null );
		const message = ref( '' );
		const messageDetail = ref( null );
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

		onErrorCaptured( ( err, component, info ) => {
			// Show only the first error
			if ( errorsDisplayed ) {
				return;
			}
			// HACK: component._.type.name is an implementation detail Vue.js might
			// refactor. Could not find other way to get the component name from the instance
			const componentName = component._ && component._.type.name;
			message.value = i18n(
				'communityconfiguration-editor-client-generic-error-description',
				componentName,
				info
			).text();
			messageDetail.value = err;
			messageStatus.value = 'error';
			showMessage.value = true;
			errorsDisplayed++;
		} );

		return {
			configData,
			doSubmit,
			editSummaryOpen,
			editorFormConfig,
			isLoading,
			message,
			messageDetail,
			messageStatus,
			onSubmit,
			providerName,
			renderers,
			schema,
			summary,
			showMessage
		};
	}
};
</script>

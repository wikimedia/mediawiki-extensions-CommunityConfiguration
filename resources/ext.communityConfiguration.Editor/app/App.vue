<template>
	<div class="ext-communityConfiguration-App">
		<missing-permissions-notice-message v-if="!canEdit"></missing-permissions-notice-message>
		<json-form
			:config="editorFormConfig"
			:data="configData"
			:renderers="renderers"
			:schema="schema"
			@submit="onSubmit"
		>
			<template #submit>
				<component
					:is="editorStatusMessage.type"
					v-if="editorStatusMessage"
					v-bind="editorStatusMessage.props"
					class="ext-communityConfiguration-FooterMessage"
				></component>
				<cdx-message
					v-if="!canEdit"
					inline
					:icon="cdxIconInfoFilled"
					class="ext-communityConfiguration-FooterPermissionsMessage"
				>
					<p>{{ $i18n( 'communityconfiguration-editor-client-notice-footer-message' ).text() }}</p>
				</cdx-message>
				<cdx-button
					action="progressive"
					weight="primary"
					:disabled="isLoading || !canEdit"
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
			:provider-id="providerId"
			@primary="doSubmit"
		></edit-summary-dialog>
	</div>
</template>

<script>
const { inject, ref, computed, onErrorCaptured } = require( 'vue' );
const { CdxButton, CdxMessage } = require( '../../codex.js' );
const { cdxIconInfoFilled } = require( './icons.json' );
const { JsonForm, useValidationErrors } = require( '../lib/json-form/form/index.js' );
const { renderers } = require( '../lib/json-form/controls-codex/src/index.js' );
const SuccessMessage = require( './components/SuccessMessage.vue' );
const MissingPermissionsNoticeMessage = require( './components/MissingPermissionsNoticeMessage.vue' );
const ValidationErrorMessage = require( './components/ValidationErrorMessage.vue' );
const PermissionsErrorMessage = require( './components/PermissionsErrorMessage.vue' );
const GenericSubmitErrorMessage = require( './components/GenericSubmitErrorMessage.vue' );
const NetworkErrorMessage = require( './components/NetworkErrorMessage.vue' );
const ClientErrorMessage = require( './components/ClientErrorMessage.vue' );
const EditSummaryDialog = require( './components/EditSummaryDialog.vue' );
let errorsDisplayed = 0;

// @vue/component
module.exports = exports = {
	name: 'App',
	components: {
		CdxButton,
		CdxMessage,
		EditSummaryDialog,
		SuccessMessage,
		MissingPermissionsNoticeMessage,
		ValidationErrorMessage,
		PermissionsErrorMessage,
		GenericSubmitErrorMessage,
		NetworkErrorMessage,
		ClientErrorMessage,
		JsonForm
	},
	setup: function () {
		const writingRepository = inject( 'WRITING_REPOSITORY' );
		const configData = inject( 'CONFIG_DATA' );
		const schema = inject( 'JSON_SCHEMA' );
		const providerId = inject( 'PROVIDER_ID' );
		const editorFormConfig = inject( 'EDITOR_FORM_CONFIG' );
		const canEdit = inject( 'CAN_EDIT' );
		const isLoading = ref( false );
		const editSummaryOpen = ref( false );
		const summary = ref( '' );
		const clientError = ref( null );
		const submitOutcome = ref( null );
		const {
			getAllValidationErrors,
			clearValidationErrors,
			setValidationErrorsFromSubmitResponse
		} = useValidationErrors( { schema, config: editorFormConfig } );

		function isPermissionsErrorResponse( errors ) {
			if ( !errors || !Array.isArray( errors ) ) {
				return false;
			}
			return errors.length === 2 &&
				errors[ 0 ].code === 'protectednamespace-interface' &&
				errors[ 1 ].code === 'sitejsonprotected';
		}
		const editorStatusMessage = computed( () => {
			// TODO: maybe this should be an array instead so that we can show multiple messages?

			if ( clientError.value ) {
				return {
					type: 'ClientErrorMessage',
					props: {
						componentName: clientError.value.componentName,
						info: clientError.value.info,
						err: clientError.value.err,
						feedbackURL: editorFormConfig.feedbackURL
					}
				};
			}

			if ( submitOutcome.value && submitOutcome.value.success ) {
				return {
					type: 'SuccessMessage'
				};
			}

			if ( submitOutcome.value && submitOutcome.value.error ) {

				if ( submitOutcome.value.error.code === 'http' ) {
					return {
						type: 'NetworkErrorMessage'
					};
				}

				const validationErrors = getAllValidationErrors();
				if ( validationErrors.length ) {
					return {
						type: 'ValidationErrorMessage',
						props: {
							errors: validationErrors,
							feedbackURL: editorFormConfig.feedbackURL
						}
					};
				}

				const errorResponse = submitOutcome.value.error.response;
				if ( isPermissionsErrorResponse( errorResponse.errors ) ) {
					return {
						type: 'PermissionsErrorMessage',
						props: { errors: errorResponse.errors }
					};
				}

				// FIXME: make more generic -> no network error
				return {
					type: 'GenericSubmitErrorMessage',
					props: {
						errorResponse,
						errorCode: submitOutcome.value.error.code,
						feedbackURL: editorFormConfig.feedbackURL
					}
				};
			}

			return null;
		} );

		let tempFormData = null;

		function onSubmit( formData ) {
			tempFormData = formData;
			editSummaryOpen.value = true;
		}

		function doSubmit() {
			isLoading.value = true;
			submitOutcome.value = null;
			clearValidationErrors();
			writingRepository.writeConfigurationData(
				providerId, tempFormData, summary.value
			).then( () => {
				isLoading.value = false;
				submitOutcome.value = { success: true };
				resetForm();
			} ).catch( ( [ errorCode, response ] ) => {
				isLoading.value = false;
				submitOutcome.value = { error: { code: errorCode, response } };
				setValidationErrorsFromSubmitResponse( response );
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
			clientError.value = { err, info, componentName };
			errorsDisplayed++;
		} );

		return {
			canEdit,
			cdxIconInfoFilled,
			editorStatusMessage,
			configData,
			doSubmit,
			editSummaryOpen,
			editorFormConfig,
			isLoading,
			onSubmit,
			providerId,
			renderers,
			schema,
			summary
		};
	}
};
</script>

<style lang="less">
@import 'mediawiki.skin.variables.less';

.ext-communityConfiguration-FooterMessage {
	margin-bottom: @spacing-100;
}

.ext-communityConfiguration-FooterPermissionsMessage {
	margin-bottom: @spacing-50;
}
</style>

<template>
	<div class="ext-communityConfiguration-App">
		<editor-message
			v-if="showMessage"
			:status="messageStatus"
		>
			<template v-if="messageStatus === 'success'" #success>
				<p>{{ $i18n( 'communityconfiguration-editor-client-success-message' ).text() }}</p>
			</template>
			<template v-if="messageStatus === 'error'" #error>
				<p v-if="errorTitle">
					<strong>{{ errorTitle }}</strong>
				</p>
				<p>{{ message }}</p>
				<div v-if="messageDetail && messageDetail.length">
					<!-- eslint-disable vue/no-v-html -- MediaWiki guarantees the HTML is safe -->
					<p
						v-for="( error, index ) in messageDetail"
						:key="index"
						v-html="error.message"
					>
					</p>
					<!-- eslint-enable vue/no-v-html  -->
				</div>
				<div v-else>
					<p v-if="messageDetail && messageDetail.stack">
						{{ messageDetail.stack }}
					</p>
					<p v-else-if="messageDetail">
						{{ messageDetail }}
					</p>
				</div>
				<p v-if="bugURL" v-i18n-html:communityconfiguration-editor-client-file-bug="[ bugURL ]"></p>
			</template>
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
const { computed, inject, ref, unref, onErrorCaptured } = require( 'vue' );
const { CdxButton } = require( '@wikimedia/codex' );
const { JsonForm } = require( '../lib/json-form/form/index.js' );
const { renderers } = require( '../lib/json-form/controls-codex/src/index.js' );
const EditorMessage = require( './components/EditorMessage.vue' );
const EditSummaryDialog = require( './components/EditSummaryDialog.vue' );
const { configurePhabricatorURL } = require( './utils.js' );
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
		const errorTitle = ref( '' );
		const messageDetail = ref( null );
		let tempFormData = null;

		const bugURL = computed( () => {
			if ( !editorFormConfig.bugReportToolURL ) {
				return null;
			}
			return configurePhabricatorURL(
				editorFormConfig.bugReportToolURL,
				messageDetail ? unref( messageDetail ).toString() : '',
				message.value,
				messageDetail.value && messageDetail.value.stack ? `${ messageDetail.value.stack.slice( 0, 800 ) }...` : ''
			);
		} );
		function onSubmit( formData ) {
			tempFormData = formData;
			editSummaryOpen.value = true;
		}

		function showError( titleText, messageText, error ) {
			showMessage.value = true;
			messageStatus.value = 'error';
			errorTitle.value = titleText || '';
			message.value = messageText;
			messageDetail.value = error;
			// TODO: log errors to logstash
		}

		function doSubmit() {
			isLoading.value = true;
			showMessage.value = false;
			new mw.Api().postWithToken( 'csrf', {
				action: 'communityconfigurationedit',
				provider: providerName,
				content: JSON.stringify( tempFormData ),
				summary: summary.value,
				formatversion: 2,
				errorformat: 'html'
			} ).then( () => {
				isLoading.value = false;
				showMessage.value = true;
				messageStatus.value = 'success';
				resetForm();
			} ).catch( ( errorCode, response ) => {
				isLoading.value = false;
				let error = response.docref;
				if ( response.errors && response.errors.length ) {
					error = response.errors.map( ( err ) => ( {
						message: err.html || errorCode,
						isHtml: !!err.html
					} ) );
				}
				showError(
					'',
					i18n( 'communityconfiguration-editor-client-data-submission-error' ).text(),
					error
				);
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
			showError(
				i18n( 'communityconfiguration-editor-client-generic-error' ).text(),
				i18n(
					'communityconfiguration-editor-client-generic-error-description',
					componentName,
					info
				).text(),
				err
			);
			errorsDisplayed++;
		} );

		return {
			bugURL,
			configData,
			doSubmit,
			editSummaryOpen,
			editorFormConfig,
			errorTitle,
			isLoading,
			message,
			messageDetail,
			messageStatus,
			onSubmit,
			providerName,
			renderers,
			schema,
			showMessage,
			summary
		};
	}
};
</script>

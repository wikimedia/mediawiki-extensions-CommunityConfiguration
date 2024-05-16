<template>
	<div class="ext-communityConfiguration-App">
		<editor-message
			v-if="showMessage || !canEdit"
			:status="messageStatus ? messageStatus : undefined"
			:is-dismissable="canEdit"
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
				<p
					v-if="editorFormConfig.feedbackURL"
					v-i18n-html:communityconfiguration-editor-client-post-feedback="[ editorFormConfig.feedbackURL ]"
				></p>
			</template>
			<p v-if="!canEdit">
				{{ $i18n( 'communityconfiguration-editor-client-notice-message' ).text() }}
			</p>
		</editor-message>
		<json-form
			:config="editorFormConfig"
			:data="configData"
			:renderers="renderers"
			:schema="schema"
			@submit="onSubmit"
		>
			<template #submit>
				<cdx-message
					v-if="!canEdit"
					inline
					:icon="cdxIconInfoFilled"
					class="ext-communityConfiguration-FooterMessage"
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
const { inject, ref, onErrorCaptured } = require( 'vue' );
const { CdxButton, CdxMessage } = require( '@wikimedia/codex' );
const { cdxIconInfoFilled } = require( './icons.json' );
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
		CdxMessage,
		EditSummaryDialog,
		EditorMessage,
		JsonForm
	},
	setup: function () {
		const i18n = inject( 'i18n' );
		const configData = inject( 'CONFIG_DATA' );
		const schema = inject( 'JSON_SCHEMA' );
		const providerId = inject( 'PROVIDER_ID' );
		const editorFormConfig = inject( 'EDITOR_FORM_CONFIG' );
		const canEdit = inject( 'CAN_EDIT' );
		const isLoading = ref( false );
		const editSummaryOpen = ref( false );
		const summary = ref( '' );
		const showMessage = ref( false );
		const messageStatus = ref( null );
		const message = ref( '' );
		const errorTitle = ref( '' );
		const messageDetail = ref( null );
		let tempFormData = null;

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
				provider: providerId,
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
			canEdit,
			cdxIconInfoFilled,
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
			providerId,
			renderers,
			schema,
			showMessage,
			summary
		};
	}
};
</script>

<style lang="less">
@import 'mediawiki.skin.variables.less';

.ext-communityConfiguration-FooterMessage {
	margin-bottom: @spacing-50;
}
</style>

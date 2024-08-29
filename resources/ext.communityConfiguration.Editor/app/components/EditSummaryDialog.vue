<template>
	<cdx-dialog
		v-model:open="wrappedIsDialogOpen"
		:title="$i18n( 'communityconfiguration-edit-summary-dialog-title', providerName ).text()"
		:primary-action="{
			label: $i18n( 'communityconfiguration-edit-summary-dialog-save-button' ).text(), actionType: 'progressive' }"
		:default-action="{ label: $i18n( 'communityconfiguration-edit-summary-dialog-cancel-button' ).text() }"
		@primary="saveChanges"
		@default="closeDialog"
	>
		<cdx-field>
			<cdx-text-area
				v-model="wrappedEditSummary"
				:placeholder="$i18n( 'communityconfiguration-edit-summary-dialog-placeholder' ).text()"
				rows="8"
			></cdx-text-area>
			<template #label>
				{{ $i18n( 'communityconfiguration-edit-summary-dialog-label' ).text() }}
				<span class="cdx-label__label__optional-flag">
					{{ $i18n( 'word-separator' ).text() }}
					{{ $i18n( 'communityconfiguration-edit-summary-dialog-title-span' ).text() }}</span>
			</template>
			<template #help-text>
				<p v-i18n-html:communityconfiguration-edit-summary-reminder="[ currentUser ]"></p>
			</template>
		</cdx-field>
	</cdx-dialog>
</template>

<script>
const { toRef, inject, computed } = require( 'vue' );
const { CdxDialog, CdxField, CdxTextArea, useModelWrapper } = require( '../../../codex.js' );

// @vue/component
module.exports = exports = {
	name: 'EditSummaryDialog',
	components: {
		CdxDialog,
		CdxField,
		CdxTextArea,
	},
	props: {
		/**
		 * The name of the provider.
		 * Generates the title of the edit summary dialog dynamically
		 * based on the provider's name.
		 */
		providerId: {
			type: String,
			required: true,
		},
	},
	emits: [ 'update:summary', 'update:open', 'primary' ],
	setup( props, { emit } ) {
		const wrappedIsDialogOpen = useModelWrapper( toRef( props, 'open' ), emit, 'update:open' );
		const wrappedEditSummary = useModelWrapper( toRef( props, 'summary' ), emit, 'update:summary' );
		const i18n = inject( 'i18n' );
		const providerName = computed(
			() => i18n( `communityconfiguration-${props.providerId.toLowerCase()}-title` ).text()
		);

		function saveChanges() {
			emit( 'primary' );
			closeDialog();
		}

		function closeDialog() {
			wrappedIsDialogOpen.value = false;
		}

		return {
			closeDialog,
			providerName,
			saveChanges,
			wrappedEditSummary,
			wrappedIsDialogOpen,
			currentUser: mw.user,
		};

	},
};
</script>

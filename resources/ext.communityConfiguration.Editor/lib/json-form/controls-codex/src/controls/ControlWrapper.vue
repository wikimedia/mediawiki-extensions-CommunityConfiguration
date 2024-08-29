<template>
	<cdx-field
		:id="id"
		:status="statusMessages.value.error ? 'error' : 'default'"
		:messages="statusMessages.value"
		:is-fieldset="isFieldset"
	>
		<slot :control-label="( controlLabel && controlLabel.exists() ) ? controlLabel.text() : ''">
		</slot>
		<template v-if="description && description.exists()" #description>
			<span v-i18n-html="description"></span>
		</template>
		<template v-if="label && label.exists()" #label>
			{{ label.text() }}
		</template>
		<template v-if="helpText && helpText.exists()" #help-text>
			<span v-i18n-html="helpText"></span>
		</template>
	</cdx-field>
</template>

<script>
const { CdxField } = require( '../../../../../../codex.js' );

// @vue/component
module.exports = exports = {
	name: 'ControlWrapper',
	components: {
		CdxField,
	},
	props: {
		id: {
			type: String,
			required: true,
		},
		// mw.Message
		label: {
			type: [ Object, null ],
			default: null,
		},
		// mw.Message
		description: {
			type: [ Object, null ],
			default: null,
		},
		// mw.Message
		controlLabel: {
			type: [ Object, null ],
			default: null,
		},
		// mw.Message
		helpText: {
			type: Object,
			default: null,
		},
		isFieldset: {
			type: Boolean,
			default: false,
		},
		statusMessages: {
			type: Object,
			default: () => ( {} ),
		},
	},
};
</script>

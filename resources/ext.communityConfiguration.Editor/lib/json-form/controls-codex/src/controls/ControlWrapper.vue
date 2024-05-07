<template>
	<cdx-field :id="name" :is-fieldset="isFieldset">
		<slot :control-label="( controlLabel && controlLabel.exists() ) ? controlLabel.text() : ''">
		</slot>
		<template v-if="label && label.exists()" #label>
			{{ label.text() }}
		</template>
		<template v-if="helpText && helpText.exists()" #help-text>
			{{ helpText.text() }}
		</template>
	</cdx-field>
</template>

<script>
const { CdxField } = require( '@wikimedia/codex' );

// @vue/component
module.exports = exports = {
	name: 'ControlWrapper',
	components: {
		CdxField
	},
	props: {
		name: {
			type: String,
			required: true
		},
		// mw.Message
		label: {
			type: Object,
			required: true
		},
		// mw.Message
		controlLabel: {
			type: Object,
			default: null
		},
		// mw.Message
		helpText: {
			type: Object,
			default: null
		},
		isFieldset: {
			type: Boolean,
			default: false
		}
	}
};
</script>

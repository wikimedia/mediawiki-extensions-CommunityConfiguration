<template>
	<div :id="name" class="ext-communityConfiguration-ControlWrapper">
		<cdx-field>
			<slot :control-label="( controlLabel && controlLabel.exists() ) ? controlLabel.text() : ''">
			</slot>
			<template v-if="label && label.exists()" #label>
				{{ label.text() }}
			</template>
			<template v-if="helpText && helpText.exists()" #help-text>
				{{ helpText.text() }}
			</template>
		</cdx-field>
	</div>
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
		}
	}
};
</script>

<style lang="less">
@import 'mediawiki.skin.variables.less';

.ext-communityConfiguration-ControlWrapper {
	margin-top: @spacing-50;
}
</style>

<template>
	<control-wrapper
		v-bind="controlWrapper"
		:is-fieldset="true"
	>
		<cdx-radio
			v-for="enumKey in control.schema.enum"
			:key="enumKey"
			v-bind="control.otherAttrs"
			v-model="control.modelValue.value"
			:input-value="enumKey"
			:name="control.uischema.scope"
			@update:model-value="onChange"
		>
			{{
				/* eslint-disable mediawiki/no-vue-dynamic-i18n -- This is not intended to be reactive */
				$i18n( control.uischema.enumLabels[enumKey] ).text()
				/* eslint-enable */
			}}
		</cdx-radio>
	</control-wrapper>
</template>

<script>
const { CdxRadio } = require( '@wikimedia/codex' );
const ControlWrapper = require( '../controls/ControlWrapper.vue' );
const { rendererProps, useJsonFormControl } = require( '../../config/index.js' );
const { useCodexControl } = require( '../utils.js' );
const { defineComponent } = require( 'vue' );

// @vue/component
module.exports = exports = defineComponent( {
	name: 'EnumControl',
	components: {
		CdxRadio,
		ControlWrapper
	},
	props: Object.assign( {}, rendererProps(), {} ),
	setup( props ) {
		return useCodexControl( useJsonFormControl( props ) );
	}
} );
</script>

<template>
	<control-wrapper
		v-slot="slotProps"
		v-bind="controlWrapper"
		:is-fieldset="true"
	>
		<cdx-checkbox
			v-bind="control.otherAttrs"
			v-model="control.modelValue.value"
			@update:model-value="onChange"
		>
			{{ slotProps.controlLabel }}
		</cdx-checkbox>
	</control-wrapper>
</template>

<script>
const { CdxCheckbox } = require( '@wikimedia/codex' );
const {
	rendererProps,
	useJsonFormControl
} = require( '../../config/index.js' );
const { useCodexControl } = require( '../utils.js' );
const ControlWrapper = require( './ControlWrapper.vue' );

// @vue/component
module.exports = exports = {
	name: 'BooleanControl',
	components: {
		CdxCheckbox,
		ControlWrapper
	},
	props: Object.assign( {}, rendererProps() ),
	setup: function ( props ) {
		return useCodexControl( useJsonFormControl( props ) );
	}
};
</script>

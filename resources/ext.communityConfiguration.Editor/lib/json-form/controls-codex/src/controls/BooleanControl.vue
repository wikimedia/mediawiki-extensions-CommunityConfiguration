<template>
	<control-wrapper
		v-slot="slotProps"
		v-bind="controlWrapper"
		:is-fieldset="true"
	>
		<cdx-checkbox
			v-bind="control.otherAttrs"
			v-model="control.modelValue.value"
			:disabled="T370611Hack"
			@update:model-value="onChange"
		>
			{{ slotProps.controlLabel }}
		</cdx-checkbox>
	</control-wrapper>
</template>

<script>
const { CdxCheckbox } = require( '../../../../../../codex.js' );
const {
	rendererProps,
	useJsonFormControl,
} = require( '../../config/index.js' );
const { useCodexControl } = require( '../utils.js' );
const ControlWrapper = require( './ControlWrapper.vue' );

// @vue/component
module.exports = exports = {
	name: 'BooleanControl',
	components: {
		CdxCheckbox,
		ControlWrapper,
	},
	props: Object.assign( {}, rendererProps() ),
	setup: function ( props ) {

		const {
			control,
			controlWrapper,
			onChange,
		} = useCodexControl( useJsonFormControl( props ) );

		let T370611Hack = false;
		// eslint-disable-next-line vue/no-undef-properties
		if ( props.schema[ 'disabled-true-hack-T370611' ] ) {
			// HACK: for T370611. This should be removed asap, ideally before end of August 2024.
			T370611Hack = true;
			control.modelValue.value = true;
		}
		return {
			control,
			controlWrapper,
			onChange,
			T370611Hack,
		};
	},
};
</script>

<template>
	<control-wrapper v-bind="controlWrapper">
		<cdx-text-input
			v-bind="control.otherAttrs"
			v-model="control.modelValue.value"
			:placeholder="control.uischema.placeholder"
			@update:model-value="onChange"
		>
		</cdx-text-input>
	</control-wrapper>
</template>

<script>

const { defineComponent } = require( 'vue' );
const { CdxTextInput } = require( '../../../../../../codex.js' );
const {
	rendererProps,
	useJsonFormControl
} = require( '../../config/index.js' );
const { useCodexControl } = require( '../utils.js' );
const ControlWrapper = require( './ControlWrapper.vue' );

// @vue/component
module.exports = exports = defineComponent( {
	name: 'StringControl',
	components: {
		CdxTextInput,
		ControlWrapper
	},
	props: Object.assign( {}, rendererProps(), {} ),
	setup( props ) {
		return useCodexControl( useJsonFormControl( props ) );
	}
} );

</script>

<template>
	<control-wrapper v-bind="controlWrapper">
		<cdx-text-input
			v-model="control.modelValue"
			input-type="number"
			@update:model-value="onChange"
		>
		</cdx-text-input>
	</control-wrapper>
</template>

<script>
const { defineComponent } = require( 'vue' );
const { CdxTextInput } = require( '@wikimedia/codex' );
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

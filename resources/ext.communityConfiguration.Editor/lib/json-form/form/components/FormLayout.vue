<template>
	<dispatch-renderer
		v-for="( element, index ) in uischema.elements"
		:key="`${element.name}-${index}`"
		:schema="schema"
		:uischema="element"
	></dispatch-renderer>
</template>

<script>
const DispatchRenderer = require( './DispatchRenderer.vue' );
const { rendererProps } = require( '../composables/props.js' );

// @vue/component
module.exports = exports = {
	name: 'FormLayout',
	components: {
		DispatchRenderer,
	},
	props: Object.assign( {}, rendererProps() ),
};
</script>

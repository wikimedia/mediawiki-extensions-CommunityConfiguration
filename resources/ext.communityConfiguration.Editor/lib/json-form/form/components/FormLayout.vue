<template>
	<div>
		<div v-for="( element, index ) in uischema.elements" :key="`${element.name}-${index}`">
			<dispatch-renderer :schema="schema" :uischema="element">
			</dispatch-renderer>
		</div>
	</div>
</template>

<script>
const DispatchRenderer = require( './DispatchRenderer.vue' );
const { rendererProps } = require( '../composables/props.js' );

// @vue/component
module.exports = exports = {
	name: 'FormLayout',
	components: {
		DispatchRenderer
	},
	props: Object.assign( {}, rendererProps() )
};
</script>

<template>
	<component :is="determinedRenderer" v-bind="renderer"></component>
</template>

<script>
const { rendererProps } = require( '../composables/props.js' );
const { useJsonFormRenderer } = require( '../composables/useJsonForm.js' );
/**
 * Iterate over an array of elements to compute the highest ranked
 * element based on a criterion.
 *
 * @param {Array} arr The array of elements to evaluate
 * @param {Function} fn Function which is invoked for each element to generate
 * the criterion by which the value is ranked.
 * @return {Object} Returns the element with the maximum value
 */
const maxBy = ( arr, fn ) => {
	let max = 0;
	let result;
	for ( const iterator of arr ) {
		const current = fn( iterator );
		if ( current > max ) {
			max = current;
			result = iterator;
		}
	}
	return result;
};

// @vue/component
module.exports = exports = {
	name: 'DispatchRenderer',
	props: Object.assign( {}, rendererProps() ),
	setup( props ) {
		return useJsonFormRenderer( props );
	},
	computed: {
		determinedRenderer() {
			// FIXME: how to tell eslint about the props
			/* eslint-disable vue/no-undef-properties */
			const renderer = maxBy(
				this.renderer.renderers,
				( r ) => r.tester( this.renderer.uischema, this.renderer.schema, this.rootSchema )
			);
			/* eslint-enable vue/no-undef-properties */
			if (
				renderer === undefined ||
				renderer.tester(
					this.renderer.uischema,
					this.renderer.schema,
					this.rootSchema
				) === -1
			) {
				// REVIEW should we fail for unkown controls? Render something?
				return () => {};
			} else {
				return renderer.renderer;
			}
		}
	}
};
</script>

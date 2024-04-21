<template>
	<div>
		<!-- REVIEW: maybe apply same pattern than in ObjectControl and only
		wrap the fields in an cdx-field when a label is informed -->
		<cdx-field :is-fieldset="true">
			<div v-for="( element, index ) in data" :key="`${control.uischema.name}-${index}`">
				<dispatch-renderer :schema="control.schema" :uischema="indexedChildUISchema( index )">
				</dispatch-renderer>
			</div>
			<template v-if="control.uischema.label && control.uischema.label.exists()" #label>
				{{ control.uischema.label.text() }}
			</template>
		</cdx-field>
	</div>
</template>

<script>
const { unref } = require( 'vue' );
const { CdxField } = require( '@wikimedia/codex' );
const {
	rendererProps,
	DispatchRenderer,
	useJsonFormArrayControl
} = require( '../../config/index.js' );

// @vue/component
module.exports = exports = {
	name: 'ArrayControl',
	components: {
		CdxField,
		DispatchRenderer
	},
	props: Object.assign( {}, rendererProps(), {} ),
	setup( props ) {
		const arrayControl = useJsonFormArrayControl( props );
		function indexedChildUISchema( index ) {
			return Object.assign( {}, arrayControl.childUISchema, {
				scope: `${arrayControl.childUISchema.scope}/${index}`,
				label: arrayControl.childUISchema.labels[ index ]
			} );
		}
		return Object.assign( {}, arrayControl, {
			indexedChildUISchema,
			data: unref( arrayControl.control.modelValue )
		} );
	}
};
</script>

<template>
	<!-- REVIEW: maybe apply same pattern than in ObjectControl and only
	wrap the fields in an cdx-field when a label is informed -->
	<cdx-field
		:id="control.pointer"
		:status="control.statusMessages.value.error ? 'error' : 'default'"
		:messages="control.statusMessages.value"
		:is-fieldset="true"
	>
		<div
			v-for="( element, index ) in data"
			:key="`${control.uischema.name}-${index}-${forceRerenderCounter}`"
			class="ext-communityConfiguration-ArrayControl__item-wrapper"
		>
			<dispatch-renderer
				class="ext-communityConfiguration-ArrayControl__item-wrapper__item"
				:schema="control.schema"
				:uischema="indexedChildUISchema( index )"
			></dispatch-renderer>
			<cdx-button
				class="ext-communityConfiguration-ArrayControl__item-wrapper__delete-button"
				data-test-id="array-control-delete-element-button"
				:aria-label="$i18n( 'communityconfiguration-editor-array-remove-element-label-screen-reader' )"
				type="button"
				weight="quiet"
				@click="() => onDeleteElementClick( index )"
			>
				<cdx-icon :icon="cdxIconTrash"></cdx-icon>
			</cdx-button>
		</div>
		<template v-if="control.uischema.label && control.uischema.label.exists()" #label>
			{{ control.uischema.label.text() }}
		</template>
	</cdx-field>
	<cdx-button
		type="button"
		data-test-id="array-control-add-element-button"
		:disabled="isMaxNumberOfItemsReached"
		@click="onAddElementClick"
	>
		<cdx-icon :icon="cdxIconAdd"></cdx-icon>
		{{
			control.uischema.addElementButtonLabel !== null ?
				control.uischema.addElementButtonLabel.text() :
				$i18n( 'communityconfiguration-editor-array-fallback-add-element-button-label' )
		}}
	</cdx-button>
</template>

<script>
const { ref, computed } = require( 'vue' );
const { CdxField, CdxButton, CdxIcon } = require( '../../../../../../codex.js' );
const { cdxIconAdd, cdxIconTrash } = require( '../../../../icons.json' );
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
		CdxButton,
		CdxIcon,
		DispatchRenderer
	},
	props: Object.assign( {}, rendererProps(), {} ),
	setup( props ) {
		const { control, addEmptyElement, removeElement, indexedChildUISchema, data } = useJsonFormArrayControl( props );

		const forceRerenderCounter = ref( 0 );

		function onAddElementClick() {
			addEmptyElement();
		}
		function onDeleteElementClick( index ) {
			removeElement( index );
			forceRerenderCounter.value += 1;
		}

		const isMaxNumberOfItemsReached = computed( () => {
			return control.schema.maxItems && data.value.length >= control.schema.maxItems;
		} );

		return {
			control,
			isMaxNumberOfItemsReached,
			indexedChildUISchema,
			data,
			cdxIconAdd, cdxIconTrash,
			onAddElementClick,
			onDeleteElementClick,
			forceRerenderCounter
		};
	}
};
</script>

<style lang="less">
@import 'mediawiki.skin.variables.less';

.ext-communityConfiguration-ArrayControl__item-wrapper {
	border: @border-subtle;
	padding: @spacing-100;
	margin-bottom: @spacing-50;
	position: relative;

	&__delete-button {
		position: absolute;
		top: @spacing-100;
		right: @spacing-100;
	}

	&__item {
		// stylelint-disable-next-line selector-class-pattern
		.cdx-field__control {
			display: flex;
			align-items: baseline;
			justify-content: space-evenly;
			gap: @spacing-100;
			flex-wrap: wrap;

			> * {
				flex: 1;
			}
			// stylelint-disable-next-line selector-class-pattern
			.cdx-lookup {
				width: 100%;
			}
		}
	}
}
</style>

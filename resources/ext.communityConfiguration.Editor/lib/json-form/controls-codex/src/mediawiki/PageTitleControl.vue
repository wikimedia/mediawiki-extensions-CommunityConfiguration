<template>
	<control-wrapper v-bind="controlWrapper">
		<cdx-lookup
			v-model:selected="selection"
			:initial-input-value="initialValue"
			:menu-items="menuItems"
			:menu-config="menuConfig"
			:placeholder="uischema.placeholder"
			@input="onInput"
			@update:selected="onSelectionChange"
		>
			<template #no-results>
				{{ $i18n( 'communityconfiguration-page-title-control-no-results' ).text() }}
			</template>
		</cdx-lookup>
	</control-wrapper>
</template>

<script>
const { ref, unref } = require( 'vue' );
const { CdxLookup } = require( '@wikimedia/codex' );
const {
	rendererProps,
	useJsonFormControl
} = require( '../../config/index.js' );
const { search } = require( './api.js' );
const { debounce, useCodexControl } = require( '../utils.js' );
const ControlWrapper = require( '../controls/ControlWrapper.vue' );

// @vue/component
module.exports = exports = {
	name: 'PageTitleControl',
	components: {
		CdxLookup,
		ControlWrapper
	},
	props: Object.assign( {}, rendererProps(), {} ),
	setup( props ) {
		const {
			control,
			controlWrapper,
			onChange
		} = useCodexControl( useJsonFormControl( props ) );
		const initialValue = unref( control.modelValue );
		const selection = ref( initialValue );
		const menuItems = ref( [] );
		const currentSearchTerm = ref( '' );

		const onSelectionChange = ( value ) => {
			if ( value === null ) {
				// This is a workaround until we have T365145
				onChange( '' );
			} else {
				onChange( value );
			}
		};

		/**
		 * Handle lookup input.
		 *
		 * @param {string} value
		 */
		const onInput = debounce( function ( value ) {
			// Internally track the current search term.
			currentSearchTerm.value = value;

			// Do nothing if we have no input.
			if ( !value ) {
				menuItems.value = [];
				return;
			}

			search( value )
				.then( ( data ) => {
					// Make sure this data is still relevant first.
					if ( currentSearchTerm.value !== value ) {
						return;
					}

					// Reset the menu items if there are no results.
					if ( !data.query || data.query.prefixsearch.length === 0 ) {
						menuItems.value = [];
						return;
					}

					// Build an array of menu items.
					const results = data.query.prefixsearch.map( ( result ) => {
						return {
							label: result.title,
							value: result.title
						};
					} );

					// Update menuItems.
					menuItems.value = results;
				} )
				.catch( () => {
					// On error, set results to empty.
					menuItems.value = [];
				} );
		}, 300 );

		// do an initial search for menu-items to prepopulate the select field
		onInput( initialValue );

		const menuConfig = {
			visibleItemLimit: 6
		};

		return {
			onSelectionChange,
			controlWrapper,
			selection,
			initialValue,
			menuItems,
			menuConfig,
			onInput,
			uischema: control.uischema
		};
	}
};
</script>

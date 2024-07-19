<template>
	<control-wrapper v-bind="controlWrapper">
		<cdx-lookup
			v-model:selected="selection"
			:initial-input-value="initialValue"
			:menu-items="menuItems"
			:menu-config="menuConfig"
			:placeholder="uischema.placeholder"
			@input="onInput"
			@update:selected="onFileUpdated"
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
const { searchCommonsFiles } = require( './api.js' );
const { debounce, useCodexControl } = require( '../utils.js' );
const ControlWrapper = require( '../controls/ControlWrapper.vue' );

// @vue/component
module.exports = exports = {
	name: 'CommonsFileControl',
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
		const model = unref( control.modelValue );
		const initialValue = model.title ? model.title : '';
		const selection = ref( null );
		const menuItems = ref( [] );
		const currentSearchTerm = ref( '' );

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
				// This is a workaround until we have T365145
				onChange( { title: '', url: '' } );
				menuItems.value = [];
				return;
			}

			searchCommonsFiles( value )
				.then( ( data ) => {
					// Make sure this data is still relevant first.
					if ( currentSearchTerm.value !== value ) {
						return;
					}

					// Reset the menu items if there are no results.
					if ( !data.query || data.query.pages.length === 0 ) {
						menuItems.value = [];
						return;
					}

					// Build an array of menu items.
					const results = data.query.pages.map( ( result ) => {
						const hasImageInfo = !!result.imageinfo.length;
						const url = hasImageInfo ? result.imageinfo[ 0 ].url : null;
						return {
							// REVIEW maybe show namesapce "File"?
							label: result.title.replace( 'File:', '' ),
							value: result.title,
							showThumbnail: hasImageInfo,
							thumbnail: {
								url,
								width: 200,
								height: 150
							}
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

		const menuConfig = {
			showThumbnail: true,
			visibleItemLimit: 6
		};

		return {
			controlWrapper,
			onFileUpdated( newVal ) {
				const item = menuItems.value.find( ( { value } ) => value === newVal );
				onChange( {
					title: newVal || '',
					url: item ? item.thumbnail.url : ''
				} );
			},
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

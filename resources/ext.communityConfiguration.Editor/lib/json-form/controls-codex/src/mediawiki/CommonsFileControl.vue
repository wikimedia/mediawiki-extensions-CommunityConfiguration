<template>
	<control-wrapper v-bind="controlWrapper">
		<cdx-lookup
			v-model:selected="selection"
			v-model:input-value="inputValue"
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
const { ref, unref, inject } = require( 'vue' );
const { CdxLookup } = require( '../../../../../../codex.js' );
const {
	rendererProps,
	useJsonFormControl,
} = require( '../../config/index.js' );
const { MwForeignApi } = require( './api.js' );
const { debounce, useCodexControl } = require( '../utils.js' );
const ControlWrapper = require( '../controls/ControlWrapper.vue' );

// @vue/component
module.exports = exports = {
	name: 'CommonsFileControl',
	components: {
		CdxLookup,
		ControlWrapper,
	},
	props: Object.assign( {}, rendererProps(), {} ),
	setup( props ) {
		const EDITOR_FORM_CONFIG = inject( 'EDITOR_FORM_CONFIG' );
		const {
			control,
			controlWrapper,
			onChange,
		} = useCodexControl( useJsonFormControl( props ) );
		const model = unref( control.modelValue );
		const selection = ref( model.title || null );
		const inputValue = ref( model.title || '' );
		const menuItems = ref( [] );
		const currentSearchTerm = ref( '' );
		let isSearchAvailable = true;
		let api = null;
		try {
			api = new MwForeignApi( EDITOR_FORM_CONFIG.commonsApiURL );
		} catch ( err ) {
			isSearchAvailable = false;
			mw.log.error( err.message );
		}

		/**
		 * Handle lookup input.
		 *
		 * @param {string} value
		 */
		const onInput = debounce( ( value ) => {
			// Internally track the current search term.
			currentSearchTerm.value = value;
			inputValue.value = value;

			// Do nothing if we have no input.
			if ( !value ) {
				// This is a workaround until we have T365145
				onChange( { title: '', url: '' } );
				menuItems.value = [];
				return;
			}
			if ( isSearchAvailable ) {
				api.searchCommonsFiles( value )
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
							const originalImageUrl = hasImageInfo ? result.imageinfo[ 0 ].url : null;
							const thumbUrlForMenuPreview = hasImageInfo ? result.imageinfo[ 0 ].thumburl : null;
							return {
								// REVIEW maybe show namesapce "File"?
								label: result.title.replace( 'File:', '' ),
								value: result.title,
								thumbnail: {
									url: thumbUrlForMenuPreview,
									width: 80,
								},
								urlValue: originalImageUrl,
							};
						} );

						// Update menuItems.
						menuItems.value = results;
					} )
					.catch( ( err ) => {
						// On error, set results to empty.
						menuItems.value = [];
						throw err;
					} );
			}
		}, 300 );

		const menuConfig = {
			showThumbnail: true,
			visibleItemLimit: 6,
		};

		return {
			controlWrapper,
			onFileUpdated( newVal ) {
				const item = menuItems.value.find( ( { value } ) => value === newVal );
				// Update inputValue to reflect the selected filename
				if ( newVal !== null ) {
					inputValue.value = newVal;
				}
				onChange( {
					title: newVal || '',
					url: item ? item.urlValue : '',
				} );
			},
			selection,
			inputValue,
			menuItems,
			menuConfig,
			onInput,
			uischema: control.uischema,
		};
	},
};
</script>

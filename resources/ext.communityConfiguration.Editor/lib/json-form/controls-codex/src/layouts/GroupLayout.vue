<template>
	<cdx-field
		v-if="uischema.label && uischema.label.exists()"
		:is-fieldset="true"
		class="ext-communityConfiguration-GroupLayout"
	>
		<dispatch-renderer
			v-for="( element, index ) in uischema.elements"
			:key="`${element.name}-${index}`"
			:schema="schema"
			:uischema="element"
		></dispatch-renderer>
		<template #label>
			{{ uischema.label.text() }}
		</template>
		<template v-if="uischema.description && uischema.description.exists()" #description>
			<span v-i18n-html="uischema.description"></span>
		</template>
	</cdx-field>
	<dispatch-renderer
		v-for="( element, index ) in uischema.elements"
		v-else
		:key="`${element.name}-${index}`"
		:schema="schema"
		:uischema="element"
	></dispatch-renderer>
</template>

<script>
const { CdxField } = require( '../../../../../../codex.js' );
const {
	DispatchRenderer,
	rendererProps,
} = require( 'ext.communityConfiguration.Editor.controls' );

// @vue/component
module.exports = exports = {
	name: 'GroupLayout',
	components: {
		CdxField,
		DispatchRenderer,
	},
	props: Object.assign( {}, rendererProps() ),
};
</script>

<style lang="less">
@import 'mediawiki.skin.variables.less';

.ext-communityConfiguration-GroupLayout {
	margin-bottom: @spacing-150;
}
</style>

<template>
	<div class="ext-communityConfiguration-App">
		<cdx-button>
			Hello world Vue
		</cdx-button>
	</div>
</template>

<script>
const { CdxButton } = require( '@wikimedia/codex' );
// @vue/component
module.exports = exports = {
	name: 'App',
	components: {
		CdxButton
	}
};
</script>

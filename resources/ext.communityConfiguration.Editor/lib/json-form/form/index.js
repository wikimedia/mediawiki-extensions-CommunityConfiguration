const DispatchRenderer = require( './components/DispatchRenderer.vue' );
const JsonForm = require( './components/JsonForm.vue' );
const {
	rendererProps
} = require( './composables/props.js' );
const {
	useJsonFormArrayControl,
	useJsonFormControl,
	useJsonFormRenderer
} = require( './composables/index.js' );

module.exports = exports = {
	DispatchRenderer,
	JsonForm,
	rendererProps,
	useJsonFormArrayControl,
	useJsonFormControl,
	useJsonFormRenderer
};

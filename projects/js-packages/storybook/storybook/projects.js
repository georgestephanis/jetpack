// List of projects paths that contains stories
// When adding something here, also add the project slug to .extra.dependencies.build in composer.json.
const path = require( 'path' );

const projects = [
	'../../ai-client/src',
	'../../components/components',
	'../../connection/components',
	'../../idc/components',
	'../../publicize-components/src/components',
	'../../scan/src',
	'../../shared-extension-utils/src',
	'../../social-logos/src/react',
	'../../../packages/my-jetpack/_inc/components',
	'../../../packages/search/src/dashboard/components',
	'../../../plugins/protect/src/js/components',
	'../../../plugins/boost/app/assets/src/js',
	'../../../packages/videopress/src/client/admin/components',
	'../../../packages/videopress/src/client/components',
	'../../../packages/videopress/src/client/block-editor',
	'../../../plugins/jetpack/extensions/',
	'../../charts/src',
];

module.exports = projects.map( project => path.join( __dirname, project ) );

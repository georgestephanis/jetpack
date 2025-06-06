import { Path, Circle } from '@wordpress/components';
import { Fragment } from '@wordpress/element';
import { __, _x } from '@wordpress/i18n';
import renderMaterialIcon from '../../../shared/render-material-icon';
import edit from './edit';
import save from './save';

const attributes = {
	address: {
		type: 'string',
		default: '',
	},
	addressLine2: {
		type: 'string',
		default: '',
	},
	addressLine3: {
		type: 'string',
		default: '',
	},
	city: {
		type: 'string',
		default: '',
	},
	region: {
		type: 'string',
		default: '',
	},
	postal: {
		type: 'string',
		default: '',
	},
	country: {
		type: 'string',
		default: '',
	},
	linkToGoogleMaps: {
		type: 'boolean',
		default: false,
	},
};

export const name = 'address';

export const settings = {
	apiVersion: 3,
	title: __( 'Address', 'jetpack' ),
	description: __( 'Lets you add a physical address with Schema markup.', 'jetpack' ),
	keywords: [
		_x( 'location', 'block search term', 'jetpack' ),
		_x( 'direction', 'block search term', 'jetpack' ),
		_x( 'place', 'block search term', 'jetpack' ),
	],
	icon: renderMaterialIcon(
		<Fragment>
			<Path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zM7 9c0-2.76 2.24-5 5-5s5 2.24 5 5c0 2.88-2.88 7.19-5 9.88C9.92 16.21 7 11.85 7 9z" />
			<Circle cx="12" cy="9" r="2.5" />
		</Fragment>
	),
	category: 'grow',
	attributes,
	parent: [ 'jetpack/contact-info' ],
	edit,
	save,
};

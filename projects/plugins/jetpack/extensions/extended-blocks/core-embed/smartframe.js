import { SmartFrameIcon } from '@automattic/jetpack-shared-extension-utils/icons';
import { registerBlockVariation } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
/*
 * New `core/embed` block variation.
 */

const coreEmbedVariationGetty = {
	name: 'smartframe',
	title: 'SmartFrame',
	icon: SmartFrameIcon,
	keywords: [ __( 'smartframe', 'jetpack' ) ],
	description: __( 'Embed a SmartFrame Image.', 'jetpack' ),
	patterns: [ /^https?:\/\/(.*?).smartframe.(io|net)\/.*/i ],
	attributes: { providerNameSlug: 'smartframe', responsive: true },
};
registerBlockVariation( 'core/embed', coreEmbedVariationGetty );

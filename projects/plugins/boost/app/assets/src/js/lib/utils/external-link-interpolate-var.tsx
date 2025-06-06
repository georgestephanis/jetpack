import { ExternalLink } from '@wordpress/components';

/**
 * Generates an Interpolate var for an external link (with target and rel
 * attributes appropriate set).
 *
 * @param {string} href       for the link to use
 * @param {string} elementKey element key to use for this link. Default: 'link'
 */
export default function externalLinkInterpolateVar( href: string, elementKey = 'link' ) {
	return {
		[ elementKey ]: <ExternalLink href={ href } />,
	};
}

import { useSelect } from '@wordpress/data';
import { doAction, hasAction } from '@wordpress/hooks';
import { getUpgradeUrl } from '../../plan-utils';
import useAutosaveAndRedirect from '../use-autosave-and-redirect';

const noop = () => {};

const HOOK_OPEN_CHECKOUT_MODAL = 'a8c.wpcom-block-editor.openCheckoutModal';

/**
 * Use this hook when you need to implement a component that leads the user to the checkout page.
 * @param {string}   planSlug   - The plan slug to upgrade to.
 * @param {Function} onRedirect - A callback function to be called when the user is redirected to the checkout page.
 *
 * @return {Array} - An array with the following elements:
 */
export default function useUpgradeFlow( planSlug, onRedirect = noop ) {
	const { checkoutUrl, planData } = useSelect(
		select => {
			const editorSelector = select( 'core/editor' );
			const planSelector = select( 'wordpress-com/plans' );

			const { id: postId, type: postType } = editorSelector.getCurrentPost();
			const plan = planSelector && planSelector.getPlan( planSlug );

			return {
				checkoutUrl: getUpgradeUrl( { plan, planSlug, postId, postType } ),
				planData: plan,
			};
		},
		[ planSlug ]
	);

	const { autosave, autosaveAndRedirect, isRedirecting } = useAutosaveAndRedirect(
		checkoutUrl,
		onRedirect
	);

	const goToCheckoutPage = async event => {
		event.preventDefault();

		// If this action is available, the feature is enabled to open the checkout
		// in a modal rather than redirect the user there, away from the editor.
		if ( hasAction( HOOK_OPEN_CHECKOUT_MODAL ) ) {
			event.preventDefault();

			autosave( event );

			doAction( HOOK_OPEN_CHECKOUT_MODAL, { products: [ planData ] } );
			return;
		}

		autosaveAndRedirect( event );
	};

	return [ checkoutUrl, goToCheckoutPage, isRedirecting, planData ];
}

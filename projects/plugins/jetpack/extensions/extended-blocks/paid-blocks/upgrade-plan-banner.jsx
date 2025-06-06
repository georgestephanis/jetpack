import { useUpgradeFlow } from '@automattic/jetpack-shared-extension-utils';
import { Nudge } from '@automattic/jetpack-shared-extension-utils/components';
import { useSelect } from '@wordpress/data';
import { __, _x, sprintf } from '@wordpress/i18n';

export const UPGRADE_NUDGE_TITLE = __( 'Premium Block', 'jetpack' );

export const /** translators: %s: name of the plan. */
	UPGRADE_NUDGE_PLAN_DESCRIPTION = __( 'Upgrade to %s to use this premium block', 'jetpack' );

export const UPGRADE_NUDGE_DESCRIPTION = __(
	'Upgrade your plan to use this premium block',
	'jetpack'
);
export const UPGRADE_NUDGE_BUTTON_TEXT = _x(
	'Upgrade',
	'Call to action to buy a new plan',
	'jetpack'
);

const UpgradePlanBanner = ( {
	onRedirect,
	align,
	className,
	title = UPGRADE_NUDGE_TITLE,
	description = null,
	buttonText = UPGRADE_NUDGE_BUTTON_TEXT,
	visible = true,
	requiredPlan,
	context,
} ) => {
	const [ checkoutUrl, goToCheckoutPage, isRedirecting ] = useUpgradeFlow(
		requiredPlan,
		onRedirect
	);

	const upgradeDescription = useSelect(
		select => {
			if ( description ) {
				return description;
			}

			const planSelector = select( 'wordpress-com/plans' );
			const plan = planSelector && planSelector.getPlan( requiredPlan );
			if ( plan ) {
				return sprintf( UPGRADE_NUDGE_PLAN_DESCRIPTION, plan.product_name_short );
			}

			return null;
		},
		[ description, requiredPlan ]
	);

	return (
		upgradeDescription && (
			<Nudge
				align={ align }
				buttonText={ buttonText }
				checkoutUrl={ checkoutUrl }
				className={ className }
				context={ context }
				description={ upgradeDescription }
				goToCheckoutPage={ goToCheckoutPage }
				isRedirecting={ isRedirecting }
				title={ title }
				visible={ visible }
			/>
		)
	);
};

export default UpgradePlanBanner;

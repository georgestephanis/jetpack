import { action } from '@storybook/addon-actions';
import PricingCard from '../index.tsx';
import type { StoryFn, Meta } from '@storybook/react';

const meta: Meta< typeof PricingCard > = {
	title: 'JS Packages/Components/Pricing Card',
	component: PricingCard,
	argTypes: {
		onCtaClick: { action: 'clicked' },
	},
};

export default meta;

// Export additional stories using pre-defined values
const Template: StoryFn< typeof PricingCard > = args => <PricingCard { ...args } />;

const DefaultArgs = {
	title: 'Jetpack Backup',
	icon: "data:image/svg+xml,%3Csvg width='32' height='32' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath fill-rule='evenodd' clip-rule='evenodd' d='m21.092 15.164.019-1.703v-.039c0-1.975-1.803-3.866-4.4-3.866-2.17 0-3.828 1.351-4.274 2.943l-.426 1.524-1.581-.065a2.92 2.92 0 0 0-.12-.002c-1.586 0-2.977 1.344-2.977 3.133 0 1.787 1.388 3.13 2.973 3.133H22.399c1.194 0 2.267-1.016 2.267-2.4 0-1.235-.865-2.19-1.897-2.368l-1.677-.29Zm-10.58-3.204a4.944 4.944 0 0 0-.201-.004c-2.75 0-4.978 2.298-4.978 5.133s2.229 5.133 4.978 5.133h12.088c2.357 0 4.267-1.97 4.267-4.4 0-2.18-1.538-3.99-3.556-4.339v-.06c0-3.24-2.865-5.867-6.4-5.867-2.983 0-5.49 1.871-6.199 4.404Z' fill='%23000'/%3E%3C/svg%3E",
	priceBefore: 9,
	priceAfter: 4.5,
	ctaText: 'Get Jetpack Backup',
	infoText:
		'Special introductory pricing, all renewals are at full price. 14 day money back guarantee.',
	onCtaClick: action( 'onCtaClick' ),
};

// Export Default story
export const _default = Template.bind( {} );
_default.args = DefaultArgs;

export const Minimal = Template.bind( {} );
Minimal.args = {
	...DefaultArgs,
	icon: null,
	ctaText: null,
	onCtaClick: null,
	infoText: null,
};

const TemplateWithChildren = args => (
	<PricingCard { ...args }>
		<ul>
			<li>Automated real-time backups</li>
			<li>Easy one-click restores</li>
			<li>Complete list of all site changes</li>
			<li>Global server infrastructure</li>
			<li>Best-in-class support</li>
		</ul>
	</PricingCard>
);
export const WithChildren = TemplateWithChildren.bind( {} );
WithChildren.args = {
	...DefaultArgs,
};

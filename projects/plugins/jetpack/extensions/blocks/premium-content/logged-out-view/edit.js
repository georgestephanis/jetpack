import { InnerBlocks } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import Context from '../_inc/context';
import { usePremiumContentAllowedBlocks } from '../_inc/premium';

export default function Edit() {
	const allowedInnerBlocks = usePremiumContentAllowedBlocks();

	return (
		<Context.Consumer>
			{ ( { selectedTab } ) => (
				<div hidden={ selectedTab.id === 'premium' } className={ selectedTab.className }>
					<InnerBlocks
						allowedBlocks={ allowedInnerBlocks }
						templateLock={ false }
						templateInsertUpdatesSelection={ false }
						template={ [
							[ 'core/heading', { content: __( 'Subscribe to get access', 'jetpack' ), level: 3 } ],
							[
								'core/paragraph',
								{
									content: __( 'Read more of this content when you subscribe today.', 'jetpack' ),
								},
							],
							[ 'premium-content/buttons' ],
						] }
					/>
				</div>
			) }
		</Context.Consumer>
	);
}

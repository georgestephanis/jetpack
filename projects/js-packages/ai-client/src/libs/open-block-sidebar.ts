/**
 * External dependencies
 */
import { store as blockEditorStore } from '@wordpress/block-editor';
import { dispatch } from '@wordpress/data';

type CoreInterfaceDispatch = {
	enableComplementaryArea: ( area: string, slot: string ) => Promise< void >;
};

/**
 * Open the block sidebar for the given client ID.
 *
 * @param {string} clientId - The client ID of the block to open the sidebar for.
 */
export function openBlockSidebar( clientId: string ) {
	if ( ! clientId ) {
		return;
	}

	const { selectBlock } = dispatch( blockEditorStore );
	const { enableComplementaryArea } = dispatch( 'core/interface' ) as CoreInterfaceDispatch;

	selectBlock( clientId );
	// This only works for the post editor, as the SEO Assistant is only available there
	enableComplementaryArea( 'core/edit-post', 'edit-post/block' );
}

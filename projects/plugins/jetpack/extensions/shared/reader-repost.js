import { createBlock } from '@wordpress/blocks';
import { dispatch } from '@wordpress/data';
import { getQueryArgs } from '@wordpress/url';
import { waitForEditor } from './wait-for-editor';

const { url, title, text, comment_content, comment_author } = getQueryArgs( window.location.href );

// This enables functionality to "repost" from the reader, adding context like an embed of the
// original post, and any extra comment or text data if applicable.
if ( url ) {
	( async () => {
		// Wait for the editor to be initialized and the core blocks registered.
		await waitForEditor();

		const blocks = [];

		if ( text && text !== title ) {
			const link = `<a href="${ url }">${ title }</a>`;
			blocks.push(
				createBlock( 'core/quote', {
					value: `<p>${ text }</p>`,
					citation: link,
				} )
			);
		}

		if ( comment_content ) {
			blocks.push(
				createBlock( 'core/quote', { value: comment_content, citation: comment_author } )
			);
		}
		blocks.push( createBlock( 'core/embed', { url, type: 'wp-embed' } ) );

		dispatch( 'core/editor' ).resetEditorBlocks( blocks );
	} )();
}

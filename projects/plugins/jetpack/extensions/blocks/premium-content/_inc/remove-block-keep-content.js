import { select, dispatch } from '@wordpress/data';
import { PluginBlockSettingsMenuItem as DeprecatedPluginBlockSettingsMenuItem } from '@wordpress/edit-post';
import { PluginBlockSettingsMenuItem as EditorPluginBlockSettingsMenuItem } from '@wordpress/editor';
import { __ } from '@wordpress/i18n';
import metadata from '../block.json';
import { transformToCoreGroup } from './transform-to-core-group';

const PluginBlockSettingsMenuItem =
	EditorPluginBlockSettingsMenuItem || DeprecatedPluginBlockSettingsMenuItem;

function replaceBlockAndKeepContent() {
	const block = select( 'core/block-editor' ).getSelectedBlock();
	dispatch( 'core/block-editor' ).replaceBlock(
		block.clientId,
		transformToCoreGroup( block.innerBlocks )
	);
}

/**
 * Register a Menu item for Removing the block and keeping the content.
 *
 * @return {Element} Remove block and keep content menu item.
 */
export default () => (
	<PluginBlockSettingsMenuItem
		allowedBlocks={ [ metadata.name ] }
		label={ __( 'Remove block and keep content', 'jetpack' ) }
		onClick={ replaceBlockAndKeepContent }
	/>
);

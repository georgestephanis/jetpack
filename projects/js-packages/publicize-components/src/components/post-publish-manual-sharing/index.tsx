import { JetpackEditorPanelLogo } from '@automattic/jetpack-shared-extension-utils/components';
import { useSelect } from '@wordpress/data';
import { PluginPostPublishPanel as DeprecatedPluginPostPublishPanel } from '@wordpress/edit-post';
import {
	PluginPostPublishPanel as EditorPluginPostPublishPanel,
	store as editorStore,
} from '@wordpress/editor';
import { __ } from '@wordpress/i18n';
import { ManualSharingInfo } from '../manual-sharing/info';
import { ShareButtons } from '../share-buttons/share-buttons';
import styles from './styles.module.scss';

const PluginPostPublishPanel = EditorPluginPostPublishPanel || DeprecatedPluginPostPublishPanel;

/**
 * Post Publish Manual Sharing component.
 *
 * @return {import('react').JSX.Element} Post Publish Manual Sharing component.
 */
export default function PostPublishManualSharing() {
	const isCurrentPostPublished = useSelect(
		select => select( editorStore ).isCurrentPostPublished(),
		[]
	);

	if ( ! isCurrentPostPublished ) {
		return null;
	}

	return (
		<PluginPostPublishPanel
			initialOpen
			title={ __( 'Manual sharing', 'jetpack-publicize-components' ) }
			icon={ <JetpackEditorPanelLogo /> }
		>
			<ManualSharingInfo className={ styles.description } variant="body-small" />
			<ShareButtons />
		</PluginPostPublishPanel>
	);
}

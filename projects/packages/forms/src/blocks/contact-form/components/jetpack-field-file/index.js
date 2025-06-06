import { getJetpackExtensionAvailability } from '@automattic/jetpack-shared-extension-utils';
import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';
import { compose } from '@wordpress/compose';
import { useSelect } from '@wordpress/data';
import { useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { useFormWrapper } from '../../util/form';
import { withSharedFieldAttributes } from '../../util/with-shared-field-attributes';
import JetpackFieldControls from '../jetpack-field-controls';
import JetpackFieldLabel from '../jetpack-field-label';
import { UpsellNudge } from '../upsell-nudge';
import { useJetpackFieldStyles } from '../use-jetpack-field-styles';
import './editor.css';

const ALLOWED_BLOCKS = [
	'core/columns',
	'core/group',
	'core/heading',
	'core/html',
	'core/image',
	'core/list',
	'core/paragraph',
	'core/row',
	'core/separator',
	'core/spacer',
	'core/stack',
	'core/subhead',
];

const BLOCKS_TEMPLATE = [
	[
		'core/group',
		{
			style: {
				spacing: {
					padding: {
						top: '48px',
						bottom: '48px',
						left: '48px',
						right: '48px',
					},
					margin: {
						top: '0',
						bottom: '0',
					},
				},
				border: {
					style: 'dashed',
					width: '1px',
					color: 'rgba(125,125,125,0.3)',
				},
			},
			allowedBlocks: ALLOWED_BLOCKS,
		},
		[
			[
				'core/paragraph',
				{
					align: 'center',
					content: __(
						'<strong><a href="#">Select a file</a></strong> or drag and drop your file here.',
						'jetpack-forms'
					),
					style: {
						spacing: {
							padding: {
								top: '8px',
								bottom: '8px',
							},
						},
						typography: {
							fontSize: '16px',
						},
					},
				},
			],
		],
	],
];

const JetpackFieldFile = props => {
	const { attributes, clientId, isSelected, setAttributes, name } = props;
	const { id, label, required, requiredText, width } = attributes;
	const fieldFileAvailability = getJetpackExtensionAvailability( 'field-file' );

	useFormWrapper( { attributes, clientId, name } );
	const { blockStyle } = useJetpackFieldStyles( attributes );

	const blockProps = useBlockProps( {
		className: `jetpack-field${ isSelected ? ' is-selected' : '' }`,
		style: blockStyle,
	} );

	const isInnerBlockSelected = useSelect( select => {
		const selectedBlockClientId = select( 'core/block-editor' ).getSelectedBlockClientId();
		const blockParents = select( 'core/block-editor' ).getBlockParents( selectedBlockClientId );
		return blockParents.includes( clientId );
	} );

	const requiresCustomUpgradeNudge = useMemo( () => {
		return (
			( ! fieldFileAvailability || ! fieldFileAvailability.available ) &&
			fieldFileAvailability?.unavailableReason?.includes( 'nudge_disabled' )
		);
	}, [ fieldFileAvailability ] );

	return (
		<>
			<div { ...blockProps }>
				{ requiresCustomUpgradeNudge && ( isSelected || isInnerBlockSelected ) && (
					<UpsellNudge requiredPlan={ fieldFileAvailability?.details?.required_plan } />
				) }
				<JetpackFieldLabel
					attributes={ attributes }
					label={ label }
					required={ required }
					requiredText={ requiredText }
					setAttributes={ setAttributes }
				/>
				<div className="jetpack-form-file-field__dropzone">
					<div className="jetpack-form-file-field__dropzone-inner">
						<input type="file" style={ { display: 'none' } } aria-hidden="true" />
						<InnerBlocks
							allowedBlocks={ ALLOWED_BLOCKS }
							template={ BLOCKS_TEMPLATE }
							templateLock={ false }
						/>
					</div>
				</div>
			</div>

			<JetpackFieldControls
				id={ id }
				required={ required }
				width={ width }
				setAttributes={ setAttributes }
				attributes={ attributes }
				hidePlaceholder={ true }
			/>
		</>
	);
};

export default compose(
	withSharedFieldAttributes( [ 'labelFontSize', 'labelLineHeight', 'labelColor' ] )
)( JetpackFieldFile );

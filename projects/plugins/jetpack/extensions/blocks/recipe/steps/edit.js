import {
	ContrastChecker,
	InnerBlocks,
	useBlockProps,
	InspectorControls,
	PanelColorSettings,
} from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

import './editor.scss';

function RecipeStepsEdit( { className, attributes, setAttributes } ) {
	const { stepHighlightColor, stepTextColor } = attributes;
	const blockProps = useBlockProps( { className } );

	return (
		<>
			<InspectorControls>
				<PanelColorSettings
					title={ __( 'Color Settings', 'jetpack' ) }
					colorSettings={ [
						{
							value: stepHighlightColor,
							onChange: newStepHighlightColor =>
								setAttributes( { stepHighlightColor: newStepHighlightColor } ),
							label: __( 'Step Highlight Color', 'jetpack' ),
						},
						{
							value: stepTextColor,
							onChange: newStepTextColor => setAttributes( { stepTextColor: newStepTextColor } ),
							label: __( 'Step Text Color', 'jetpack' ),
						},
					] }
				>
					{
						<ContrastChecker
							{ ...{
								isLargeText: false,
								textColor: stepTextColor,
								backgroundColor: stepHighlightColor,
							} }
						/>
					}
				</PanelColorSettings>
			</InspectorControls>
			<ol { ...blockProps }>
				<InnerBlocks
					allowedBlocks={ [ 'jetpack/recipe-step' ] }
					renderAppender={ InnerBlocks.ButtonBlockAppender }
				/>
			</ol>
		</>
	);
}

export default RecipeStepsEdit;

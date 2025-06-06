import { SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import NoSupport from './no-support';

export default ( {
	fontBase,
	fontBaseDefault,
	fontHeadings,
	fontHeadingsDefault,
	fontBaseOptions,
	fontHeadingsOptions,
	updateBaseFont,
	updateHeadingsFont,
} ) => {
	if ( ! fontBaseOptions || ! fontHeadingsOptions ) {
		return <NoSupport unsupportedFeature={ __( 'custom font selection', 'jetpack-mu-wpcom' ) } />;
	}

	return (
		<>
			<SelectControl
				label={ __( 'Heading Font', 'jetpack-mu-wpcom' ) }
				value={ fontHeadings }
				options={ fontHeadingsOptions }
				onChange={ newValue => updateHeadingsFont( newValue ) }
				style={ { fontFamily: fontHeadings !== 'unset' ? fontHeadings : fontHeadingsDefault } }
				__next40pxDefaultSize={ true }
				__nextHasNoMarginBottom={ true }
			/>
			<SelectControl
				label={ __( 'Base Font', 'jetpack-mu-wpcom' ) }
				value={ fontBase }
				options={ fontBaseOptions }
				onChange={ newValue => updateBaseFont( newValue ) }
				style={ { fontFamily: fontBase !== 'unset' ? fontBase : fontBaseDefault } }
				__next40pxDefaultSize={ true }
				__nextHasNoMarginBottom={ true }
			/>
			<hr />
		</>
	);
};

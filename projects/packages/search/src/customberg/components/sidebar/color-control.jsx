// NOTE: Expect this import to break when the exported value is renamed!
import { __experimentalColorGradientControl as ColorGradientControl } from '@wordpress/block-editor'; // eslint-disable-line @wordpress/no-unsafe-wp-apis
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

import './color-control.scss';

const DEFAULT_COLORS = [
	{ name: 'Dull blue', color: '#463ECE' },
	{ name: 'Jazzberry jam', color: '#C6446F' },
	{ name: 'June bud', color: '#C4D455' },
];

/**
 * Color control for use in SidebarOptions tab.
 *
 * @param {object}   props          - component properties.
 * @param {boolean}  props.disabled - disables the control.
 * @param {Function} props.onChange - invoked with a new color when the selected color has changed.
 * @param {string}   props.value    - color value prefixed with #.
 * @return {Element} component instance
 */
export default function ColorControl( { disabled, value, onChange } ) {
	const colors = useSelect( select => {
		const settings = select( 'core/block-editor' ).getSettings() ?? {};
		return Array.isArray( settings?.colors ) && settings.colors.length > 0
			? settings.colors
			: DEFAULT_COLORS;
	} );

	return (
		<div className="jp-search-configure-color-input components-base-control">
			<ColorGradientControl
				label={ __( 'Highlight for search terms', 'jetpack-search-pkg' ) }
				disabled={ disabled }
				colorValue={ value }
				colors={ colors }
				disableCustomColors={ false }
				disableCustomGradients={ true }
				onColorChange={ onChange }
			/>
		</div>
	);
}

import { CheckboxControl, Notice } from '@wordpress/components';
import { useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import './excluded-post-types-control.scss';

const VALID_POST_TYPES = window.JetpackInstantSearchOptions.postTypes;

/**
 * Control for modifying excluded post types.
 *
 * @param {object}   props                - component properties.
 * @param {boolean}  props.disabled       - disables the control.
 * @param {Function} props.onChange       - invoked with new array of excluded post types when the selection has been updated.
 * @param {object}   props.validPostTypes - { [ postTypeId ]: { name: string, singular_name: string } }.
 * @param {string}   props.value          - excluded post types as a CSV.
 * @return {Element} component instance
 */
export default function ExcludedPostTypesControl( {
	disabled,
	onChange,
	validPostTypes = VALID_POST_TYPES,
	value,
} ) {
	const validPostTypeNames = useMemo( () => Object.keys( validPostTypes ), [ validPostTypes ] );
	const selectedValues = useMemo( () => {
		if ( ! value || ! Array.isArray( value ) ) {
			return new Set();
		}
		return new Set( value );
	}, [ value ] );
	const isLastUnchecked = selectedValues.size === validPostTypeNames.length - 1;

	const changeHandler = key => isSelected => {
		const newValue = new Set( selectedValues );
		isSelected ? newValue.add( key ) : newValue.delete( key );
		onChange( [ ...newValue ] );
	};

	return (
		<div className="jp-search-configure-excluded-post-types-control components-base-control">
			<div className="jp-search-configure-excluded-post-types-control__label">
				{ __( 'Excluded post types', 'jetpack-search-pkg' ) }
			</div>
			{ isLastUnchecked && (
				<Notice isDismissible={ false } status="info">
					{ /* translators: for excluded post types control; one post type must remain included. */ }
					{ __( 'You must leave at least one post type unchecked.', 'jetpack-search-pkg' ) }
				</Notice>
			) }
			{ validPostTypeNames.map( type => (
				<CheckboxControl
					checked={ selectedValues.has( type ) }
					disabled={ disabled || ( ! selectedValues.has( type ) && isLastUnchecked ) }
					key={ type }
					label={ VALID_POST_TYPES[ type ].name }
					onChange={ changeHandler( type ) }
					value={ type }
					__nextHasNoMarginBottom={ true }
				/>
			) ) }
		</div>
	);
}

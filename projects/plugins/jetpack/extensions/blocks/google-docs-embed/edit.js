import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl } from '@wordpress/components';
import { Fragment, useEffect } from '@wordpress/element';
import { __, _x } from '@wordpress/i18n';
import Embed from './embed';
import variations from './variations';

const docsVariation = variations?.find( v => v.name === 'jetpack/google-docs' );
const sheetsVariation = variations?.find( v => v.name === 'jetpack/google-sheets' );
const slidesVariation = variations?.find( v => v.name === 'jetpack/google-slides' );

const GOOGLE_DOCUMENT = {
	type: 'document',
	title: docsVariation?.title,
	icon: docsVariation.icon,
	patterns: [ /^(http|https):\/\/(docs\.google.com)\/document\/d\/([A-Za-z0-9_-]+).*?$/i ],
};

const GOOGLE_SPREADSHEET = {
	type: 'spreadsheets',
	title: sheetsVariation?.title,
	icon: sheetsVariation.icon,
	patterns: [ /^(http|https):\/\/(docs\.google.com)\/spreadsheets\/d\/([A-Za-z0-9_-]+).*?$/i ],
};

const GOOGLE_SLIDE = {
	type: 'presentation',
	title: slidesVariation?.title,
	icon: slidesVariation.icon,
	patterns: [ /^(http|https):\/\/(docs\.google.com)\/presentation\/d\/([A-Za-z0-9_-]+).*?$/i ],
};

/**
 * Edit component.
 * See https://wordpress.org/gutenberg/handbook/designers-developers/developers/block-api/block-edit-save/#edit
 *
 * @param {object}   props                  - The block props.
 * @param {object}   props.attributes       - Block attributes.
 * @param {string}   props.attributes.title - Custom title to be displayed.
 * @param {string}   props.className        - Class name for the block.
 * @param {Function} props.setAttributes    - Sets the value for block attributes.
 * @return {Function} Render the edit screen
 */
const GsuiteBlockEdit = props => {
	const {
		attributes: { aspectRatio },
		attributes: { variation },
		attributes: { url },
		setAttributes,
	} = props;

	let icon = '';
	let title = '';
	let patterns = [];
	let type = '';

	useEffect( () => {
		/**
		 * Parse the URL to detect the variation type.
		 *
		 * @return {string} The variation.
		 */
		const detectVariation = () => {
			const regex = /^(http|https):\/\/(docs\.google\.com)\/(.*)\/d\//;
			const matches = url.match( regex );

			switch ( matches[ 3 ] ) {
				case 'document':
					return 'google-docs';

				case 'spreadsheets':
					return 'google-sheets';

				case 'presentation':
					return 'google-slides';
			}

			return '';
		};

		if ( ! variation ) {
			setAttributes( { variation: detectVariation() } );
		}
	}, [ variation, url, setAttributes ] );

	switch ( variation.replace( 'jetpack/', '' ) ) {
		case 'google-docs':
			icon = GOOGLE_DOCUMENT.icon;
			title = GOOGLE_DOCUMENT.title;
			patterns = GOOGLE_DOCUMENT.patterns;
			type = GOOGLE_DOCUMENT.type;
			break;

		case 'google-sheets':
			icon = GOOGLE_SPREADSHEET.icon;
			title = GOOGLE_SPREADSHEET.title;
			patterns = GOOGLE_SPREADSHEET.patterns;
			type = GOOGLE_SPREADSHEET.type;
			break;

		case 'google-slides':
			icon = GOOGLE_SLIDE.icon;
			title = GOOGLE_SLIDE.title;
			patterns = GOOGLE_SLIDE.patterns;
			type = GOOGLE_SLIDE.type;
			break;
	}

	/**
	 * Convert GSuite URL to a preview URL.
	 *
	 * @return {string} The URL pattern.
	 */
	const mapGSuiteURL = () => {
		/**
		 * If the block is not the expected one, return the
		 * original URL as is.
		 */
		if ( patterns.length === 0 || '' === type ) {
			return url;
		}

		/**
		 * Check if the URL is valid.
		 *
		 * If not, return the original URL as is.
		 */
		const matches = url.match( patterns[ 0 ] );
		if (
			null === matches ||
			'undefined' === typeof matches[ 1 ] ||
			'undefined' === typeof matches[ 2 ] ||
			'undefined' === typeof matches[ 3 ]
		) {
			return url;
		}

		return `${ matches[ 1 ] }://${ matches[ 2 ] }/${ type }/d/${ matches[ 3 ] }/preview`;
	};

	const aspectRatios = [
		// translators: default aspect ratio for the embedded Google document.
		{ label: __( 'Default', 'jetpack' ), value: '' },
		// translators: aspect ratio for the embedded Google document.
		{ label: __( '100% - Show the whole document', 'jetpack' ), value: 'ar-100' },
		// translators: aspect ratio for the embedded Google document.
		{ label: __( '50% - Show half of the document', 'jetpack' ), value: 'ar-50' },
	];

	return (
		<>
			<Fragment>
				<Embed
					icon={ icon.src }
					instructions={ [
						__( 'Copy and paste your document link below.', 'jetpack' ),
						__(
							'If your document is private, only readers logged into a Google account with shared access to the document may view it.',
							'jetpack'
						),
					].join( ' ' ) }
					label={ title }
					patterns={ patterns }
					placeholder={ _x( 'Enter the link here…', 'Embed block placeholder', 'jetpack' ) }
					mapUrl={ mapGSuiteURL }
					mismatchErrorMessage={ __(
						'The document couldn’t be embedded. To embed a document, use the link in your browser address bar when editing the document.',
						'jetpack'
					) }
					checkGoogleDocVisibility={ true }
					{ ...props }
				/>
			</Fragment>
			<Fragment>
				<InspectorControls>
					<PanelBody>
						<p>
							{ __(
								'Select a different aspect-ratio to show more (or less) of your embedded document.',
								'jetpack'
							) }
						</p>
						<SelectControl
							label={ __( 'Aspect Ratio', 'jetpack' ) }
							value={ aspectRatio }
							options={ aspectRatios }
							onChange={ value => setAttributes( { aspectRatio: value } ) }
							__nextHasNoMarginBottom={ true }
							__next40pxDefaultSize={ true }
						/>
					</PanelBody>
				</InspectorControls>
			</Fragment>
		</>
	);
};
export default GsuiteBlockEdit;

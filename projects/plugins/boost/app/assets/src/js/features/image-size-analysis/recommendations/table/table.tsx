import { __ } from '@wordpress/i18n';
import BrokenDataRow from '../row-types/broken-data-row/broken-data-row';
import ImageMissingRow from '../row-types/image-missing-row/image-missing-row';
import ImageSizeRow from '../row-types/image-size-row/image-size-row';
import LoadingRow from '../row-types/loading-row/loading-row';
import Spinner from '$features/ui/spinner/spinner';
import {
	useImageFixer,
	type IsaImage,
	type IsaReport,
	ISAStatus,
} from '$features/image-size-analysis';
import clsx from 'clsx';
import styles from './table.module.scss';
import rowStyles from '../row.module.scss';

const toggleImageFix = ( imageDetails: IsaImage ) => {
	const imageFixer = useImageFixer();

	if ( ! imageDetails ) {
		return;
	}

	const edit_url = imageDetails?.page.edit_url;
	let postId = '0';
	if ( edit_url ) {
		const url = new URL( edit_url );
		postId = new URLSearchParams( url.search ).get( 'post' ) || '0';
	}

	imageFixer.mutate( {
		image_id: imageDetails.id,
		image_url: imageDetails.image.url,
		image_width: imageDetails.image.dimensions.expected.width.toString(),
		image_height: imageDetails.image.dimensions.expected.height.toString(),
		post_id: postId,
		fix: ! imageDetails.image.fixed,
	} );
};

interface TableProps {
	isaDataLoading: boolean;
	images: IsaImage[];
	isaReport?: IsaReport;
}

const Table = ( { isaDataLoading, images, isaReport }: TableProps ) => {
	return (
		<>
			<div className={ clsx( styles[ 'loading-spinner' ], { [ styles.active ]: isaDataLoading } ) }>
				<Spinner size="3rem" lineWidth="4px" />
			</div>

			{ ! isaDataLoading && images.length === 0 ? (
				<h4 className={ styles[ 'header-no-issues' ] }>
					{ isaReport?.status === ISAStatus.Completed
						? __( '🥳 No image size issues found!', 'jetpack-boost' )
						: __( 'No image size issues found yet…', 'jetpack-boost' ) }
				</h4>
			) : (
				<div className={ clsx( styles.table, { [ styles.loading ]: isaDataLoading } ) }>
					<div className={ clsx( styles[ 'table-header' ], rowStyles[ 'row-grid' ] ) }>
						<div className={ styles.image }>Image</div>
						<div className={ styles[ 'potential-size' ] }>Potential Size</div>
						<div className={ styles.device }>Device</div>
						<div className={ styles.page }>Page/Post</div>
					</div>

					{ isaDataLoading
						? [ ...Array( 10 ) ].map( ( _, i ) => <LoadingRow key={ i } /> )
						: images.map( image =>
								image.type === 'image_size' ? (
									<ImageSizeRow
										key={ image.id }
										enableTransition={ images.length > 0 }
										details={ image }
										toggleImageFix={ () => toggleImageFix( image ) }
									/>
								) : image.type === 'image_missing' ? (
									<ImageMissingRow
										key={ image.id }
										enableTransition={ images.length > 0 }
										details={ image }
									/>
								) : (
									<BrokenDataRow key={ image.id } />
								)
						  ) }
				</div>
			) }
		</>
	);
};

export default Table;

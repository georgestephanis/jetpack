/**
 * External dependencies
 */
import { Text, useBreakpointMatch } from '@automattic/jetpack-components';
import { Tooltip } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { Icon, info, warning } from '@wordpress/icons';
import clsx from 'clsx';
import { useState } from 'react';
/**
 * Internal dependencies
 */
import {
	LOCAL_VIDEO_ERROR_MIME_TYPE_NOT_SUPPORTED,
	VIDEO_PRIVACY_LEVELS,
	VIDEO_PRIVACY_LEVEL_PRIVATE,
} from '../../../state/constants';
import { usePlan } from '../../hooks/use-plan';
import useVideos from '../../hooks/use-videos';
import Checkbox from '../checkbox';
import ConnectVideoRow, { LocalVideoRow, Stats } from '../video-row';
import VideoRowError from '../video-row/error';
import styles from './style.module.scss';
/**
 * Types
 */
import { LocalVideoListProps, VideoListProps } from './types';

const VideoList = ( {
	videos,
	hidePrivacy = false,
	hideDuration = false,
	hidePlays = false,
	showActionButton = true,
	showQuickActions = true,
	loading = false,
	onVideoDetailsClick,
}: VideoListProps ) => {
	const [ selected, setSelected ] = useState( [] );
	const [ isSmall ] = useBreakpointMatch( 'sm' );
	const allSelected = selected?.length === videos?.length;
	const showCheckbox = false; // TODO: implement bulk actions

	const handleAll = checked => {
		if ( checked ) {
			setSelected( videos.map( ( _, i ) => i ) );
		} else {
			setSelected( [] );
		}
	};

	const handleClickWithIndex = ( index, callback ) => () => {
		callback?.( videos[ index ] );
	};

	return (
		<div className={ styles.list }>
			<div className={ styles.header }>
				<div className={ styles[ 'title-wrapper' ] }>
					{ showCheckbox && <Checkbox checked={ allSelected } onChange={ handleAll } /> }
					<Text>{ __( 'Title', 'jetpack-videopress-pkg' ) }</Text>
				</div>
				{ ! isSmall && (
					<div className={ styles[ 'data-wrapper' ] }>
						<Stats
							privacy={ hidePrivacy ? null : __( 'Privacy', 'jetpack-videopress-pkg' ) }
							duration={ hideDuration ? null : __( 'Duration', 'jetpack-videopress-pkg' ) }
							plays={ hidePlays ? null : __( 'Plays', 'jetpack-videopress-pkg' ) }
							upload={ __( 'Upload date', 'jetpack-videopress-pkg' ) }
						/>
					</div>
				) }
			</div>
			{ videos.map( ( video, index ) => {
				const isPrivate =
					VIDEO_PRIVACY_LEVELS[ video.privacySetting ] === VIDEO_PRIVACY_LEVEL_PRIVATE;

				return video.error ? (
					<VideoRowError key={ video?.guid ?? video?.id } id={ video?.id } title={ video?.title } />
				) : (
					<ConnectVideoRow
						key={ video?.guid ?? video?.id }
						id={ video?.id }
						checked={ selected.includes( index ) }
						title={ video.title }
						thumbnail={ video?.posterImage } // TODO: we should use thumbnail when the API is ready https://github.com/Automattic/jetpack/issues/26319
						duration={ hideDuration ? null : video.duration }
						plays={ hidePlays ? null : video.plays }
						isPrivate={ hidePrivacy ? null : isPrivate }
						uploadDate={ video.uploadDate }
						showQuickActions={ ! video?.uploading && showQuickActions }
						showActionButton={ ! video?.uploading && showActionButton }
						showCheckbox={ showCheckbox }
						className={ styles.row }
						onActionClick={ handleClickWithIndex( index, onVideoDetailsClick ) }
						loading={ loading }
						onSelect={ check =>
							setSelected( current => {
								if ( check ) {
									return [ ...current, index ];
								}
								return current.filter( idx => idx !== index );
							} )
						}
					/>
				);
			} ) }
		</div>
	);
};

export const LocalVideoList = ( {
	videos,
	showActionButton = true,
	showQuickActions = false,
	uploading = false,
	onActionClick,
}: LocalVideoListProps ) => {
	const [ selected, setSelected ] = useState( [] );
	const [ isSmall ] = useBreakpointMatch( 'sm' );
	const allSelected = selected?.length === videos?.length;
	const showCheckbox = false; // TODO: implement bulk actions
	const { hasVideoPressPurchase } = usePlan();
	const { uploadedVideoCount, isFetching } = useVideos();
	const hasVideos = uploadedVideoCount > 0 || isFetching || uploading?.length > 0;

	const handleAll = checked => {
		if ( checked ) {
			setSelected( videos.map( ( _, i ) => i ) );
		} else {
			setSelected( [] );
		}
	};

	const handleClickWithIndex = index => () => {
		onActionClick?.( videos[ index ] );
	};

	const getTitleAdornment = video => {
		if ( video?.isUploadedToVideoPress ) {
			return (
				<Tooltip
					position="top center"
					text={ __( 'Video already uploaded to VideoPress', 'jetpack-videopress-pkg' ) }
				>
					<div className={ styles[ 'title-adornment' ] }>
						<Icon icon={ info } />
					</div>
				</Tooltip>
			);
		}
		if ( video?.readError != null ) {
			const errorMessageReadError = __( 'Video cannot be read', 'jetpack-videopress-pkg' );
			const errorMessageMimeType = __(
				'Video has an unsupported file type',
				'jetpack-videopress-pkg'
			);

			return (
				<Tooltip
					position="top center"
					text={
						video?.readError === LOCAL_VIDEO_ERROR_MIME_TYPE_NOT_SUPPORTED
							? errorMessageMimeType
							: errorMessageReadError
					}
				>
					<div className={ clsx( styles[ 'title-adornment' ], styles.error ) }>
						<Icon icon={ warning } />
					</div>
				</Tooltip>
			);
		}
		return null;
	};

	return (
		<div className={ styles.list }>
			<div className={ styles.header }>
				<div className={ styles[ 'title-wrapper' ] }>
					{ showCheckbox && <Checkbox checked={ allSelected } onChange={ handleAll } /> }
					<Text>{ __( 'Title', 'jetpack-videopress-pkg' ) }</Text>
				</div>
				{ ! isSmall && (
					<div className={ styles[ 'data-wrapper' ] }>
						<Stats
							privacy=""
							duration=""
							plays=""
							upload={ __( 'Upload date', 'jetpack-videopress-pkg' ) }
						/>
					</div>
				) }
			</div>
			{ videos.map( ( video, index ) => {
				if ( ! video?.id ) {
					return null;
				}
				return (
					<LocalVideoRow
						key={ `local-video-${ video.id }` }
						id={ video.id }
						title={ video.title }
						showActionButton={ showActionButton }
						showQuickActions={ showQuickActions }
						showCheckbox={ showCheckbox }
						uploadDate={ video.uploadDate }
						onActionClick={ handleClickWithIndex( index ) }
						actionButtonLabel={ __( 'Upload to VideoPress', 'jetpack-videopress-pkg' ) }
						disabled={ video?.isUploadedToVideoPress || video?.readError != null }
						disableActionButton={ ( hasVideos && ! hasVideoPressPurchase ) || uploading }
						titleAdornment={ getTitleAdornment( video ) }
					/>
				);
			} ) }
		</div>
	);
};

export default VideoList;

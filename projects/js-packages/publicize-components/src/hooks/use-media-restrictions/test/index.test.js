import { renderHook } from '@testing-library/react';
import useAttachedMedia from '../../use-attached-media';
import {
	FILE_SIZE_ERROR,
	FILE_TYPE_ERROR,
	VIDEO_LENGTH_TOO_LONG_ERROR,
	VIDEO_LENGTH_TOO_SHORT_ERROR,
} from '../constants';
import useMediaRestrictions from '../index';

jest.mock( '../../use-attached-media', () => jest.fn() );

jest.mock( '../../../utils/use-share-message-max-length.ts', () => ( {
	useShareMessageMaxLength: jest.fn().mockReturnValue( 255 ),
} ) );

const DUMMY_CONNECTIONS = [
	{
		service_name: 'facebook',
		connection_id: 'facebook',
	},
	{
		service_name: 'tumblr',
		connection_id: 'tumblr',
	},
	{
		service_name: 'linkedin',
		connection_id: 'linkedin',
	},
	{
		service_name: 'mastodon',
		connection_id: 'mastodon',
	},
	{
		service_name: 'instagram-business',
		connection_id: 'instagram-business',
	},
];

const INVALID_TYPES = [ 'imagejpg', 'image/tgif', 'video/mp5', '', null ];

const VALID_MEDIA_ALL = [
	{ metaData: { mime: 'image/jpg', fileSize: 40 }, mediaData: { width: 400, height: 500 } },
	{ metaData: { mime: 'image/jpeg', fileSize: 20 }, mediaData: { width: 400, height: 500 } },
];
const ALLOWED_MEDIA_TYPES_ALL = [
	'image/jpeg',
	'image/jpg',
	'image/png',
	'video/mp4',
	'video/videopress',
];

const getHookProps = (
	{ connections = DUMMY_CONNECTIONS, media = [], isSocialImageGeneratorEnabledForPost = false } = {
		connections: DUMMY_CONNECTIONS,
		media: [],
		isSocialImageGeneratorEnabledForPost: false,
	}
) => [
	connections,
	media,
	{
		isSocialImageGeneratorEnabledForPost,
	},
];

describe( 'useMediaRestrictions hook', () => {
	beforeEach( () => {
		// Mocking attached media to allow validation
		useAttachedMedia.mockReturnValue( { attachedMedia: [ 1 ] } );
	} );

	test( 'Should not run validation if a connection does not require media or if attachedMedia is empty', () => {
		// Mocking useAttachedMedia to return an empty array to simulate no attached media
		useAttachedMedia.mockReturnValue( { attachedMedia: [] } );

		// Define media that would normally fail validation
		const INVALID_MEDIA = {
			metaData: { mime: 'image/jpg', fileSize: 100000000 }, // Large file size
			mediaData: { width: 400, height: 500 },
		};

		const { result } = renderHook( () =>
			useMediaRestrictions(
				...getHookProps( { media: INVALID_MEDIA, connections: DUMMY_CONNECTIONS } )
			)
		);

		// Expect only the Instagram connection to have an error since it requires media
		// and we validate it even if there is no attached media
		expect( result.current.validationErrors ).toEqual( {
			'instagram-business': 'FILE_SIZE_ERROR',
		} );
	} );
	test( 'should not get any errors for image that accepted by all platforms', () => {
		VALID_MEDIA_ALL.forEach( media => {
			const { result } = renderHook( () => useMediaRestrictions( ...getHookProps( { media } ) ) );
			expect( result.current.validationErrors ).toEqual( {} );
		} );
	} );

	test( 'No error with empty connections', () => {
		VALID_MEDIA_ALL.forEach( media => {
			const { result } = renderHook( () =>
				useMediaRestrictions( ...getHookProps( { media, connections: [] } ) )
			);
			expect( result.current.validationErrors ).toEqual( {} );
		} );
	} );

	test( 'Should be valid if image is not uploaded', () => {
		[ { metaData: { mime: 'image/jpg', fileSize: 100000000 } } ].forEach( media => {
			const { result } = renderHook( () =>
				useMediaRestrictions(
					...getHookProps( {
						connections: DUMMY_CONNECTIONS.splice( 0, -1 ), // Instagram checks even if not uploaded
						media,
					} )
				)
			);
			expect( result.current.validationErrors ).toEqual( {} );
		} );
	} );

	test( 'Should not get File Type Error for the all accepted types', () => {
		ALLOWED_MEDIA_TYPES_ALL.map( type => ( {
			metaData: { mime: type, fileSize: 5 },
		} ) ).forEach( media => {
			const { result } = renderHook( () =>
				useMediaRestrictions(
					...getHookProps( { media, connections: DUMMY_CONNECTIONS.splice( 0, -1 ) } ) // Instagram checks even if not uploaded
				)
			);
			expect( result.current.validationErrors ).toEqual( {} );
		} );
	} );

	test( 'Should get errors for invalid media types', () => {
		INVALID_TYPES.map( type => ( {
			metaData: { mime: type, fileSize: 5 },
		} ) ).forEach( media => {
			const { result } = renderHook( () =>
				useMediaRestrictions(
					...getHookProps( { media } ) // Instagram checks even if not uploaded
				)
			);
			expect( Object.values( result.current.validationErrors ) ).toHaveLength(
				Object.keys( DUMMY_CONNECTIONS ).length
			);
			expect(
				Object.values( result.current.validationErrors ).every( error => error === FILE_TYPE_ERROR )
			).toBe( true );
		} );
	} );

	test( 'Instagram should only accept good sized image', () => {
		[
			{
				media: {
					metaData: { mime: 'image/jpg', fileSize: 10000000 },
					mediaData: { width: 400, height: 500 },
				},
				error: FILE_SIZE_ERROR,
			}, // Too big image
			{
				media: {
					metaData: { mime: 'image/png', fileSize: 10000000 },
					mediaData: { width: 400, height: 500 },
				},
				error: FILE_TYPE_ERROR,
			}, // Png
			{
				media: {
					metaData: { mime: 'video/mp5', fileSize: 10 },
					mediaData: { width: 320, height: 500 },
				},
				error: FILE_TYPE_ERROR,
			}, // Bad Video
		].forEach( testData => {
			const { result } = renderHook( () =>
				useMediaRestrictions(
					...getHookProps( { media: testData.media, connections: [ DUMMY_CONNECTIONS[ 4 ] ] } )
				)
			);
			expect( result.current.validationErrors ).toHaveProperty( 'instagram-business' );
			expect( result.current.validationErrors[ 'instagram-business' ] ).toEqual( testData.error );
		} );
	} );

	test( 'Can get video length error', () => {
		[
			{
				media: {
					metaData: { mime: 'video/mp4', fileSize: 1000000, length: 2 },
					mediaData: { width: 10, height: 10 },
				},
				error: VIDEO_LENGTH_TOO_SHORT_ERROR,
			}, // Too short video
			{
				media: {
					metaData: { mime: 'video/mp4', fileSize: 1000000, length: 20000 },
					mediaData: { width: 10, height: 10 },
				},
				error: VIDEO_LENGTH_TOO_LONG_ERROR,
			}, // Too long video
		].forEach( testData => {
			const { result } = renderHook( () =>
				useMediaRestrictions(
					...getHookProps( {
						media: testData.media,
						connections: [ DUMMY_CONNECTIONS[ 2 ] ],
					} ) // Instagram not support videos
				)
			);
			expect( result.current.validationErrors.linkedin ).toEqual( testData.error );
		} );
	} );
} );

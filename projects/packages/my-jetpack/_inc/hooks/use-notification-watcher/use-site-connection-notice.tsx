import { Col, TermsOfService, Text } from '@automattic/jetpack-components';
import { __, sprintf } from '@wordpress/i18n';
import { useContext, useEffect, useMemo } from 'react';
import { MyJetpackRoutes } from '../../constants';
import { NOTICE_PRIORITY_HIGH } from '../../context/constants';
import { NoticeContext } from '../../context/notices/noticeContext';
import { useAllProducts } from '../../data/products/use-all-products';
import useProductsByOwnership from '../../data/products/use-products-by-ownership';
import getProductSlugsThatRequireUserConnection from '../../data/utils/get-product-slugs-that-require-user-connection';
import useAnalytics from '../use-analytics';
import useConnectSite from '../use-connect-site';
import useMyJetpackConnection from '../use-my-jetpack-connection';
import useMyJetpackNavigate from '../use-my-jetpack-navigate';
import type { NoticeHookType } from './types';
import type { NoticeOptions } from '../../context/notices/types';
import type { MouseEvent } from 'react';

const useSiteConnectionNotice: NoticeHookType = ( redBubbleAlerts, isLoading ) => {
	const { recordEvent } = useAnalytics();
	const { setNotice, resetNotice } = useContext( NoticeContext );
	const { siteIsRegistering, isSiteConnected } = useMyJetpackConnection( {
		skipUserConnection: true,
	} );
	const { data: products, isLoading: isAllProductsLoading, isError } = useAllProducts();
	const navToConnection = useMyJetpackNavigate( MyJetpackRoutes.ConnectionSkipPricing );
	const redBubbleSlug = 'missing-connection';
	const connectionError = redBubbleAlerts?.[ redBubbleSlug ];
	const { connectSite } = useConnectSite( {
		tracksInfo: {
			event: 'jetpack_my_jetpack_site_connection_notice_cta',
			properties: {},
		},
	} );

	const { refetch: refetchOwnershipData } = useProductsByOwnership();

	const productSlugsThatRequireUserConnection = useMemo( () => {
		if ( isLoading || isAllProductsLoading || isError ) {
			return [];
		}
		return getProductSlugsThatRequireUserConnection( products );
	}, [ isLoading, isError, isAllProductsLoading, products ] );

	useEffect( () => {
		if ( ! connectionError ) {
			return;
		}

		const requiresUserConnection = connectionError.type === 'user';

		const onActionButtonClick = ( { e }: { e: MouseEvent< HTMLButtonElement > } ) => {
			if ( requiresUserConnection ) {
				recordEvent( 'jetpack_my_jetpack_user_connection_notice_cta_click' );
				navToConnection();
			} else {
				connectSite( e );
			}
		};

		const oneProductMessage = sprintf(
			/* translators: placeholder is product name. */
			__(
				'Jetpack %s needs a user connection to WordPress.com to be able to work.',
				'jetpack-my-jetpack'
			),
			productSlugsThatRequireUserConnection[ 0 ]
		);

		const userConnectionContent = {
			message:
				productSlugsThatRequireUserConnection.length === 1
					? oneProductMessage
					: __(
							'Some products need a user connection to WordPress.com to be able to work.',
							'jetpack-my-jetpack'
					  ),
			buttonLabel: __( 'Connect your user account', 'jetpack-my-jetpack' ),
			title: __( 'Missing user connection', 'jetpack-my-jetpack' ),
		};

		const siteConnectionContent = {
			message: __(
				'Some products need a connection to WordPress.com to be able to work.',
				'jetpack-my-jetpack'
			),
			buttonLabel: __( 'Connect your site', 'jetpack-my-jetpack' ),
			title: __( 'Missing site connection', 'jetpack-my-jetpack' ),
		};

		const noticeOptions: NoticeOptions = {
			id: redBubbleSlug,
			level: connectionError.is_error ? 'error' : 'info',
			actions: [
				{
					label: requiresUserConnection
						? userConnectionContent.buttonLabel
						: siteConnectionContent.buttonLabel,
					isLoading: siteIsRegistering,
					loadingText: __( 'Connecting…', 'jetpack-my-jetpack' ),
					onClick: onActionButtonClick,
					noDefaultClasses: true,
				},
			],
			// If this notice gets into a loading state, we want to show it above the rest
			priority: NOTICE_PRIORITY_HIGH + ( siteIsRegistering ? 1 : 0 ),
			isRedBubble: true,
			tracksArgs: {
				type: connectionError.type,
				is_error: connectionError.is_error,
			},
		};

		const messageContent = requiresUserConnection ? (
			userConnectionContent.message
		) : (
			<Col>
				<Text mb={ 2 }>{ siteConnectionContent.message }</Text>
				<TermsOfService agreeButtonLabel={ siteConnectionContent.buttonLabel } />
			</Col>
		);

		if ( ! isLoading ) {
			setNotice( {
				message: messageContent,
				title: requiresUserConnection ? userConnectionContent.title : siteConnectionContent.title,
				options: noticeOptions,
			} );
		}
	}, [
		isSiteConnected,
		connectSite,
		navToConnection,
		products,
		recordEvent,
		redBubbleAlerts,
		resetNotice,
		setNotice,
		siteIsRegistering,
		connectionError,
		refetchOwnershipData,
		productSlugsThatRequireUserConnection,
		isLoading,
	] );
};

export default useSiteConnectionNotice;

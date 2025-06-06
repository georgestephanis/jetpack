/* global jetpack_redirects */

import { GetRedirectUrlArgs, QueryVars } from './types.ts';

/**
 * Builds an URL using the jetpack.com/redirect/ service
 *
 * If source is a simple slug, it will be sent using the source query parameter. e.g. jetpack.com/redirect/?source=slug
 *
 * If source is a full URL, starting with https://, it will be sent using the url query parameter. e.g. jetpack.com/redirect/?url=https://wordpress.com
 *
 * Note: if using full URL, query parameters and anchor must be passed in args. Any querystring of url fragment in the URL will be discarded.
 *
 * @since 0.2.0
 * @param {string}             source - The URL handler registered in the server or the full destination URL (starting with https://).
 * @param {GetRedirectUrlArgs} args   - Additional arguments to build the url.
 *                                    This is not a complete list as any argument passed here
 *                                    will be sent to as a query parameter to the Redirect server.
 *                                    These parameters will not necessarily be passed over to the final destination URL.
 *                                    If you want to add a parameter to the final destination URL, use the `query` argument.
 * @return {string} The redirect URL
 */
export default function getRedirectUrl( source: string, args: GetRedirectUrlArgs = {} ) {
	const queryVars: QueryVars = {};

	let calypsoEnv;
	if ( typeof window !== 'undefined' ) {
		// eslint-disable-next-line @typescript-eslint/ban-ts-comment -- Not using @ts-expect-error because JP_CONNECTION_INITIAL_STATE is typed in the "connection" package and that doesn't expect this error
		// @ts-ignore The usage of JP_CONNECTION_INITIAL_STATE is not typed inside this generic package. We should get rid of it in the future.
		calypsoEnv = window?.JP_CONNECTION_INITIAL_STATE?.calypsoEnv;
	}

	if ( source.search( 'https://' ) === 0 ) {
		const parsedUrl = new URL( source );

		// discard any query and fragments.
		source = `https://${ parsedUrl.host }${ parsedUrl.pathname }`;
		queryVars.url = encodeURIComponent( source );
	} else {
		queryVars.source = encodeURIComponent( source );
	}

	for ( const argName in args ) {
		queryVars[ argName ] = encodeURIComponent( args[ argName ] );
	}

	if (
		! Object.keys( queryVars ).includes( 'site' ) &&
		typeof jetpack_redirects !== 'undefined' &&
		Object.hasOwn( jetpack_redirects, 'currentSiteRawUrl' )
	) {
		queryVars.site = jetpack_redirects.currentBlogID ?? jetpack_redirects.currentSiteRawUrl;
	}

	if ( calypsoEnv ) {
		queryVars.calypso_env = calypsoEnv;
	}

	const queryString = Object.keys( queryVars )
		.map( key => key + '=' + queryVars[ key ] )
		.join( '&' );

	return `https://jetpack.com/redirect/?` + queryString;
}

import { castToString } from '$lib/utils/cast-to-string';
import { JSONObject } from '$lib/utils/json-types';
import { sortByFrequency } from '$lib/utils/sort-by-frequency';
import {
	CriticalCssErrorDetails,
	CriticalCssState,
	Critical_CSS_Error_Type,
	Provider,
} from './stores/critical-css-state-types';
import { ProviderRecommendation } from './stores/recommendation-types';

/**
 * Specification for a set of errors that can appear as a part of a recommendation.
 * Every error in the set is of the same type.
 */
export type ErrorSet = {
	type: Critical_CSS_Error_Type; // Type of errors in this set.
	firstMeta: JSONObject; // Meta from the first error, for convenience.
	byUrl: {
		[ url: string ]: CriticalCssErrorDetails; // Each error keyed by URL.
	};
};

/**
 * Given a Critical CSS State, returns whether or not this represents a fatal error.
 *
 * @param {CriticalCssState} cssState - The CSS State object.
 */
export function isFatalError( cssState: CriticalCssState ): boolean {
	if ( cssState.status === 'error' ) {
		return true;
	}

	if ( cssState.status === 'not_generated' ) {
		return false;
	}

	// If there are no providers, the state is being re-initialized. So dismiss any show-stopper errors.
	if ( cssState.providers.length === 0 ) {
		return false;
	}

	const hasActiveProvider = cssState.providers.some(
		provider => provider.status === 'success' || provider.status === 'pending'
	);

	return ! hasActiveProvider;
}

/**
 * Given a CSS State object, returns all the providers that have errors.
 *
 * @param cssState - The CSS State object.
 */
export function getProvidersWithErrors( cssState: CriticalCssState ): Provider[] {
	return cssState.providers.filter( provider => ( provider.errors?.length || 0 ) > 0 );
}

/**
 * Given a CSS State object, return the most important Set of errors among the recommendations.
 *
 * @param cssState - The CSS State object.
 */
export function getPrimaryErrorSet( cssState: CriticalCssState ): ErrorSet | undefined {
	const providersWithErrors = getProvidersWithErrors( cssState );

	if ( ! providersWithErrors.length ) {
		return undefined;
	}

	const primaryProviders = [ 'core_front_page', 'core_posts_page' ];

	for ( const key of primaryProviders ) {
		const provider = providersWithErrors.find(
			p => p.key === key || p.key.startsWith( 'cornerstone_' )
		);
		if ( provider && provider.errors ) {
			return getPrimaryGroupedError( provider.errors );
		}
	}

	return undefined;
}

export function getPrimaryGroupedError( errors: CriticalCssErrorDetails[] ): ErrorSet | undefined {
	const groupedErrors = groupErrorsByFrequency( errors );
	return groupedErrors.length > 0 ? groupedErrors[ 0 ] : undefined;
}

/**
 * Takes a set of errors (in an object keyed by URL), and returns
 * a SortedErrorSet; an array which contains each type of error grouped. Also
 * groups things like HTTP errors by code.
 *
 * @param errors A sorted set of errors.
 */
export function groupErrorsByFrequency( errors: CriticalCssErrorDetails[] ): ErrorSet[] {
	if ( ! errors ) {
		return [];
	}

	const groupKeys = errors.map( error => groupKey( error ) );
	const groupOrder = sortByFrequency( groupKeys );

	return groupOrder.map( group => {
		const byUrl = errors.reduce< { [ url: string ]: CriticalCssErrorDetails } >( ( acc, error ) => {
			if ( groupKey( error ) === group ) {
				acc[ error.url ] = error;
			}
			return acc;
		}, {} );
		const first = byUrl[ Object.keys( byUrl )[ 0 ] ];

		return {
			type: first.type,
			firstMeta: first.meta,
			byUrl,
		};
	} );
}

/**
 * Figures out a grouping key for the given Critical CSS error. Used to group
 * "like" errors - such as HTTP errors with the same code, or by type.
 *
 * @param {CriticalCssErrorDetails} error
 */
export function groupKey( error: CriticalCssErrorDetails ) {
	if (
		error.type === 'HttpError' &&
		typeof error.meta === 'object' &&
		error.meta !== null &&
		'code' in error.meta
	) {
		return error.type + '-' + castToString( error.meta.code, '' );
	}

	if ( error.type === 'UnknownError' ) {
		return error.type + '-' + error.message;
	}

	return error.type;
}

type RecommendationsResult = {
	activeRecommendations: ProviderRecommendation[];
	dismissedRecommendations: ProviderRecommendation[];
};

type ErrorsByType = Record< Critical_CSS_Error_Type, CriticalCssErrorDetails[] >;

export function getErrorTypeKey( error: CriticalCssErrorDetails ): Critical_CSS_Error_Type {
	if (
		error.type === 'HttpError' &&
		typeof error.meta === 'object' &&
		error.meta !== null &&
		'code' in error.meta
	) {
		return `HttpError-${ error.meta.code }` as Critical_CSS_Error_Type;
	}
	return error.type;
}

export function groupRecommendationsByStatus(
	providersWithIssues: Provider[]
): RecommendationsResult {
	const activeRecommendations: ProviderRecommendation[] = [];
	const dismissedRecommendations: ProviderRecommendation[] = [];

	providersWithIssues.forEach( provider => {
		const providerErrors = provider.errors || [];
		// Group errors by type first
		const errorsByType = providerErrors.reduce( ( acc, error ) => {
			const errorTypeKey = getErrorTypeKey( error );
			if ( ! acc[ errorTypeKey ] ) {
				acc[ errorTypeKey ] = [];
			}
			acc[ errorTypeKey ].push( error );
			return acc;
		}, {} as ErrorsByType );

		// For each error type group, check if it's dismissed
		Object.entries( errorsByType ).forEach( ( [ errorType, errors ] ) => {
			if ( provider.dismissed_errors?.includes( errorType as Critical_CSS_Error_Type ) ) {
				dismissedRecommendations.push( {
					key: provider.key,
					label: provider.label,
					errorType: errorType as Critical_CSS_Error_Type,
					errors: errors,
				} );
			} else {
				activeRecommendations.push( {
					key: provider.key,
					label: provider.label,
					errorType: errorType as Critical_CSS_Error_Type,
					errors: errors,
				} );
			}
		} );
	} );

	return { activeRecommendations, dismissedRecommendations };
}

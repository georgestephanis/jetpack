/**
 * External dependencies
 */
import { createReduxStore, register } from '@wordpress/data';
/**
 * Internal dependencies
 */
import actions from './actions.ts';
import reducer from './reducer.ts';
/**
 * Types
 */
import type { AiFeatureProps, PlanStateProps } from './types.ts';

const store = 'wordpress-com/plans';

export const selectors = {
	/*
	 * Return the plan with the given slug.
	 *
	 * @param {Object} state    - The Plans state tree.
	 * @param {string} planSlug - The plan slug to find.
	 * @return {Object}           The plan.
	 */
	getPlan( state: PlanStateProps, planSlug: string ) {
		return state.plans.find( plan => plan.product_slug === planSlug );
	},

	/**
	 * Return the AI Assistant feature.
	 *
	 * @param {PlanStateProps} state - The Plans state tree.
	 * @return {AiFeatureProps}       The AI Assistant feature data.
	 */
	getAiAssistantFeature( state: PlanStateProps ): AiFeatureProps {
		// Clean up the _meta property.
		const data = { ...state.features.aiAssistant };
		delete data._meta;

		return data;
	},

	/**
	 * Get the isRequesting flag for the AI Assistant feature.
	 *
	 * @param {PlanStateProps} state - The Plans state tree.
	 * @return {boolean}              The isRequesting flag.
	 */
	getIsRequestingAiAssistantFeature( state: PlanStateProps ): boolean {
		return state.features.aiAssistant?._meta?.isRequesting;
	},

	getAsyncRequestCountdownValue( state: PlanStateProps ): number {
		return state.features.aiAssistant?._meta?.asyncRequestCountdown;
	},

	getAsyncRequestCountdownTimerId( state: PlanStateProps ): number {
		return state.features.aiAssistant?._meta?.asyncRequestTimerId;
	},
};

export const wordpressPlansStore = createReduxStore( store, {
	actions,

	reducer,

	selectors,

	controls: {
		FETCH_FROM_API( { url } ) {
			// We cannot use `@wordpress/api-fetch` here since it unconditionally sends
			// the `X-WP-Nonce` header, which is disallowed by WordPress.com.
			// (To reproduce, note that you need to call `apiFetch` with `
			// `{ credentials: 'same-origin', mode: 'cors' }`, since its defaults are
			// different from `fetch`'s.)
			return fetch( url ).then( response => response.json() );
		},
	},

	resolvers: {
		*getPlan() {
			const url = 'https://public-api.wordpress.com/rest/v1.5/plans';
			const plans = yield actions.fetchFromAPI( url );
			return actions.setPlans( plans );
		},

		getAiAssistantFeature: ( state: PlanStateProps ) => {
			if ( state?.features?.aiAssistant ) {
				return;
			}

			return actions.fetchAiAssistantFeature();
		},
	},
} );

register( wordpressPlansStore );

// Types

// eslint-disable-next-line @typescript-eslint/no-explicit-any
type OmitFirstArg< F > = F extends ( _: any, ...args: infer P ) => infer R
	? ( ...args: P ) => R
	: never;

export type WordPressPlansSelectors = {
	[ key in keyof typeof selectors ]: OmitFirstArg< ( typeof selectors )[ key ] >;
};

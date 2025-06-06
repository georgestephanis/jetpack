import { assign } from 'lodash';
import { combineReducers } from 'redux';
import {
	JETPACK_PRODUCTS_FETCH,
	JETPACK_PRODUCTS_FETCH_FAIL,
	JETPACK_PRODUCTS_FETCH_RECEIVE,
} from 'state/action-types';

export const items = ( state = {}, action ) => {
	switch ( action.type ) {
		case JETPACK_PRODUCTS_FETCH_RECEIVE:
			return action.products;
		default:
			return state;
	}
};

export const requests = ( state = {}, action ) => {
	switch ( action.type ) {
		case JETPACK_PRODUCTS_FETCH:
			return assign( {}, state, {
				isFetchingProducts: true,
			} );
		case JETPACK_PRODUCTS_FETCH_RECEIVE:
		case JETPACK_PRODUCTS_FETCH_FAIL:
			return assign( {}, state, {
				isFetchingProducts: false,
			} );

		default:
			return state;
	}
};

export const reducer = combineReducers( {
	items,
	requests,
} );

/**
 * Returns true if currently requesting products. Otherwise false.
 *
 * @param {object} state - Global state tree
 * @return {boolean}       Whether products are being requested
 */
export function isFetchingProducts( state ) {
	return !! state.jetpack.products.requests.isFetchingProducts;
}

/**
 * Returns WP.com products that are relevant to Jetpack.
 * @param {object} state - Global state tree
 * @return {object}  Products
 */
export function getProducts( state ) {
	return state.jetpack.products.items;
}

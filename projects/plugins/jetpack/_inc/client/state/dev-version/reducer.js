import { combineReducers } from 'redux';
import { DEV_CARD_DISPLAY, DEV_CARD_HIDE } from 'state/action-types';

export const display = ( state = false, action ) => {
	switch ( action.type ) {
		case DEV_CARD_DISPLAY:
			return true;
		case DEV_CARD_HIDE:
			return false;

		default:
			return state;
	}
};

export const reducer = combineReducers( {
	display,
} );

/**
 * Determines if the DevCard should be displayed.
 * @param {object} state - Global state tree
 * @return {boolean}       whether the devCard can be displayed
 */
export function canDisplayDevCard( state ) {
	return !! state.jetpack.devCard.display;
}

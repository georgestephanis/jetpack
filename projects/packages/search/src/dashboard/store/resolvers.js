import { __ } from '@wordpress/i18n';
import { errorNotice } from '../components/global-notices/store/actions';
import { setJetpackSettings } from './actions/jetpack-settings';
import { setSearchPricing } from './actions/search-pricing';
import { setSearchPlanInfo } from './actions/site-plan';
import { setSearchStats } from './actions/site-stats';
import {
	fetchJetpackSettings,
	fetchSearchPlanInfo,
	fetchSearchPricing,
	fetchSearchStats,
} from './controls';

/**
 * Yield actions to get Search Module Status
 *
 * @yield {object} - an action object.
 * @return {object} - an action object.
 */
export function* getSearchModuleStatus() {
	try {
		const settings = yield fetchJetpackSettings();
		if ( settings ) {
			return setJetpackSettings( settings );
		}
	} catch {
		return errorNotice( __( 'Error fetching settings…', 'jetpack-search-pkg' ) );
	}
}

/**
 * Yield actions to get search plan info
 *
 * @yield {object} - an action object.
 * @return {object} - an action object.
 */
export function* getSearchPlanInfo() {
	try {
		const planInfo = yield fetchSearchPlanInfo();
		if ( planInfo ) {
			return setSearchPlanInfo( planInfo );
		}
	} catch {
		return errorNotice( __( 'Error fetching search plan…', 'jetpack-search-pkg' ) );
	}
}

/**
 * Yield actions to get search stats
 *
 * @yield {object} - an action object.
 * @return {object} - an action object.
 */
export function* getSearchStats() {
	try {
		const stats = yield fetchSearchStats();
		if ( stats ) {
			return setSearchStats( stats );
		}
	} catch {
		return errorNotice( __( 'Error fetching search stats', 'jetpack-search-pkg' ) );
	}
}

/**
 * Yield actions to get search pricing
 *
 * @yield {object} - an action object.
 * @return {object} - an action object.
 */
export function* getSearchPricing() {
	try {
		const pricing = yield fetchSearchPricing();
		if ( pricing ) {
			return setSearchPricing( pricing );
		}
	} catch {
		// we just ignore the notice.
	}
}

export default { getSearchModuleStatus, getSearchPlanInfo, getSearchStats, getSearchPricing };

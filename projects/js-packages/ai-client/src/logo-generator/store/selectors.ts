/**
 * Types
 */
import { DEFAULT_LOGO_COST } from '../constants.ts';
import type { AiFeatureProps, LogoGeneratorStateProp, Logo, RequestError } from './types.ts';
import type { SiteDetails } from '../types.ts';

const selectors = {
	/**
	 * Return the AI Assistant feature.
	 * @param {LogoGeneratorStateProp} state - The app state tree.
	 * @return {Partial<AiFeatureProps>}      The AI Assistant feature data.
	 */
	getAiAssistantFeature( state: LogoGeneratorStateProp ): Partial< AiFeatureProps > {
		// Clean up the _meta property.
		const data = { ...state.features.aiAssistantFeature };
		delete data._meta;

		return data;
	},

	/**
	 * Return the site details.
	 * @param {LogoGeneratorStateProp} state - The app state tree.
	 * @return {Partial<SiteDetails> | undefined}   The site details.
	 */
	getSiteDetails( state: LogoGeneratorStateProp ): Partial< SiteDetails > | undefined {
		return state.siteDetails;
	},

	/**
	 * Get the isRequesting flag for the AI Assistant feature.
	 * @param {LogoGeneratorStateProp} state - The app state tree.
	 * @return {boolean}                      The isRequesting flag.
	 */
	getIsRequestingAiAssistantFeature( state: LogoGeneratorStateProp ): boolean {
		return state.features.aiAssistantFeature?._meta?.isRequesting ?? false;
	},

	/**
	 * Get the logos history.
	 * @param {LogoGeneratorStateProp} state - The app state tree.
	 * @return {Array<Logo>}                  The logos history array.
	 */
	getLogos( state: LogoGeneratorStateProp ): Array< Logo > {
		return state.history ?? [];
	},

	/**
	 * Get the selected logo index.
	 * @param {LogoGeneratorStateProp} state - The app state tree.
	 * @return {number | null}                The selected logo index.
	 */
	getSelectedLogoIndex( state: LogoGeneratorStateProp ): number | null {
		return state.selectedLogoIndex ?? null;
	},

	/**
	 * Get the selected logo.
	 * @param {LogoGeneratorStateProp} state - The app state tree.
	 * @return {Logo}                         The selected logo.
	 */
	getSelectedLogo( state: LogoGeneratorStateProp ): Logo {
		return state.history?.[ state.selectedLogoIndex ] ?? null;
	},

	/**
	 * Get the isSavingToLibrary flag.
	 * @param {LogoGeneratorStateProp} state - The app state tree.
	 * @return {boolean}                      The isSavingToLibrary flag.
	 */
	getIsSavingLogoToLibrary( state: LogoGeneratorStateProp ): boolean {
		return state._meta?.isSavingLogoToLibrary ?? false;
	},

	/**
	 * Get the isApplyingLogo flag.
	 * @param {LogoGeneratorStateProp} state - The app state tree.
	 * @return {boolean}                      The isApplyingLogo flag.
	 */
	getIsApplyingLogo( state: LogoGeneratorStateProp ): boolean {
		return state._meta?.isApplyingLogo ?? false;
	},

	/**
	 * Get the isEnhancingPrompt flag.
	 * @param {LogoGeneratorStateProp} state - The app state tree.
	 * @return {boolean}                      The isEnhancingPrompt flag.
	 */
	getIsEnhancingPrompt( state: LogoGeneratorStateProp ): boolean {
		return state._meta?.isEnhancingPrompt ?? false;
	},

	/**
	 * Get the isRequestingImage flag.
	 * @param {LogoGeneratorStateProp} state - The app state tree.
	 * @return {boolean}                      The isRequestingImage flag.
	 */
	getIsRequestingImage( state: LogoGeneratorStateProp ): boolean {
		return state._meta?.isRequestingImage ?? false;
	},

	/**
	 * Get an aggregated isBusy flag, based on the loading states of the app.
	 * @param {LogoGeneratorStateProp} state - The app state tree.
	 * @return {boolean}                      The isBusy flag.
	 */
	getIsBusy( state: LogoGeneratorStateProp ): boolean {
		return (
			selectors.getIsApplyingLogo( state ) ||
			selectors.getIsSavingLogoToLibrary( state ) ||
			selectors.getIsRequestingImage( state ) ||
			selectors.getIsEnhancingPrompt( state )
		);
	},

	/**
	 * Get the requireUpgrade value from aiAssistantFeature
	 * @param {LogoGeneratorStateProp} state - The app state tree.
	 * @return {boolean}                      The requireUpgrade flag.
	 */
	getRequireUpgrade( state: LogoGeneratorStateProp ): boolean {
		const feature = state.features.aiAssistantFeature;

		if ( ! feature?.tierPlansEnabled ) {
			return feature?.requireUpgrade;
		}
		const logoCost = feature?.costs?.[ 'jetpack-ai-logo-generator' ]?.logo ?? DEFAULT_LOGO_COST;
		const currentLimit = feature?.currentTier?.value || 0;
		const currentUsage = feature?.usagePeriod?.requestsCount || 0;
		const isUnlimited = currentLimit === 1;
		const hasNoNextTier = ! feature?.nextTier; // If there is no next tier, the user cannot upgrade.

		// Add a local check on top of the feature flag, based on the current usage and logo cost.
		return (
			state.features.aiAssistantFeature?.requireUpgrade ||
			( ! isUnlimited && ! hasNoNextTier && currentLimit - currentUsage < logoCost )
		);
	},

	/**
	 * Get the featureFetchError value.
	 * @param {LogoGeneratorStateProp} state - The app state tree.
	 * @return {RequestError}                 The featureFetchError value.
	 */
	getFeatureFetchError( state: LogoGeneratorStateProp ): RequestError {
		return state._meta?.featureFetchError ?? null;
	},

	/**
	 * Get the firstLogoPromptFetchError value.
	 * @param {LogoGeneratorStateProp} state - The app state tree.
	 * @return {RequestError}                 The firstLogoPromptFetchError value.
	 */
	getFirstLogoPromptFetchError( state: LogoGeneratorStateProp ): RequestError {
		return state._meta?.firstLogoPromptFetchError ?? null;
	},

	/**
	 * Get the enhancePromptFetchError value.
	 * @param {LogoGeneratorStateProp} state - The app state tree.
	 * @return {RequestError}                 The enhancePromptFetchError value.
	 */
	getEnhancePromptFetchError( state: LogoGeneratorStateProp ): RequestError {
		return state._meta?.enhancePromptFetchError ?? null;
	},

	/**
	 * Get the logoFetchError value.
	 * @param {LogoGeneratorStateProp} state - The app state tree.
	 * @return {RequestError}                 The logoFetchError value.
	 */
	getLogoFetchError( state: LogoGeneratorStateProp ): RequestError {
		return state._meta?.logoFetchError ?? null;
	},

	/**
	 * Get the saveToLibraryError value.
	 * @param {LogoGeneratorStateProp} state - The app state tree.
	 * @return {RequestError}                 The saveToLibraryError value.
	 */
	getSaveToLibraryError( state: LogoGeneratorStateProp ): RequestError {
		return state._meta?.saveToLibraryError ?? null;
	},

	/**
	 * Get the logoUpdateError value.
	 * @param {LogoGeneratorStateProp} state - The app state tree.
	 * @return {RequestError}                 The logoUpdateError value.
	 */
	getLogoUpdateError( state: LogoGeneratorStateProp ): RequestError {
		return state._meta?.logoUpdateError ?? null;
	},

	/**
	 * Get the context value.
	 * @param {LogoGeneratorStateProp} state - The app state tree.
	 * @return {string}                       The context value.
	 */
	getContext( state: LogoGeneratorStateProp ): string {
		return state._meta?.context ?? '';
	},

	/**
	 * Get tier plans enabled status.
	 *
	 * @param {LogoGeneratorStateProp} state - The app state tree.
	 * @return {boolean}                      The tier plans enabled status.
	 */
	getTierPlansEnabled( state: LogoGeneratorStateProp ): boolean {
		return state.features.aiAssistantFeature?.tierPlansEnabled ?? false;
	},

	/**
	 * Get tier plans enabled status.
	 *
	 * @param {LogoGeneratorStateProp} state - The app state tree.
	 * @return {boolean}                      The loading logo history status.
	 */
	getIsLoadingHistory( state: LogoGeneratorStateProp ): boolean {
		return state._meta?.isLoadingHistory ?? false;
	},
};

export default selectors;

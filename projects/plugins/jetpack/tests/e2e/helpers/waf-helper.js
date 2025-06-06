import { execWpCommand } from '_jetpack-e2e-commons/helpers/utils-helper.js';
import logger from '_jetpack-e2e-commons/logger.js';

/**
 * Enable automatic rules
 * @return {string} wp-cli 'jetpack-waf generate_rules' command output
 */
export async function enableAutomaticRules() {
	logger.sync( 'Enabling automatic firewall rules' );
	const optionUpdated = execWpCommand( 'option update jetpack_waf_automatic_rules 1' );
	const rulesGenerated = execWpCommand( 'jetpack-waf generate_rules' );
	return optionUpdated && rulesGenerated;
}

/**
 * Generate firewall rules
 * @return {string} wp-cli command output
 */
export async function generateRules() {
	logger.sync( 'Generating firewall rules' );
	return execWpCommand( 'jetpack-waf generate_rules' );
}

#!/usr/bin/env bash

# Exit if any command fails.
set -e

BASE_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
export PATH="$BASE_DIR/../node_modules/.bin:$PATH"

usage() {
	echo "usage: $0 command"
	echo "  start [--activate-plugins plugin1 plugin2 ...]    Setup the docker containers for E2E tests and optionally activate additional plugins"
	echo "  stop                                              Stop the docker containers for E2E tests"
	echo "  reset [--activate-plugins plugin1 plugin2 ...]    Reset the containers state (reset db, re-installs WordPress) and optionally activate additional plugins"
	echo "  clean                                             Completely resets the environment (remove docker volumes, MySql and WordPress data and logs)"
	echo "  new [--activate-plugins plugin1 plugin2 ...]      Completely resets the running environment and starts a new fresh one"
	echo "  gb-setup                                          Setup Gutenberg plugin"
	echo "  -h | usage                                        Output this message"
	exit 1
}

BASE_CMD='pnpm jetpack docker --type e2e --name t1'

start_env() {
	export_e2e_config
	$BASE_CMD up -d
	$BASE_CMD install || true
	configure_wp_env "$@"
}

stop_env() {
	$BASE_CMD down
}

reset_env() {
	$BASE_CMD wp -- db reset --yes
	$BASE_CMD install || true
	configure_wp_env "$@"
}

clean_env() {
	$BASE_CMD uninstall || true
	$BASE_CMD clean
}

new_env() {
	clean_env
	start_env "$@"
}

gb_setup() {
	GB_ZIP="wp-content/gutenberg.zip"
	$BASE_CMD exec-silent -- /usr/local/src/jetpack-monorepo/tools/e2e-commons/bin/container-setup.sh gb-setup $GB_ZIP
	$BASE_CMD wp plugin install $GB_ZIP
	$BASE_CMD wp plugin activate gutenberg
}

# Exports E2E WP password from config file.
export_e2e_config() {
	if [[ ! -f 'config/local.cjs' ]]; then
		echo "Decrypted config file not found! Have you run 'pnpm config:decrypt' yet?";
		exit 1
	else
		local e2e_config=$(node -e 'const config = require( "./config/local.cjs" ); console.log( JSON.stringify(config.testSites.default) );' 2>/dev/null) || { echo "Failed to read config file!" && exit 1; }

		WP_FORCE_ADMIN_USER=$( jq -r '.username' <<< "$e2e_config" )
		WP_FORCE_ADMIN_PASS=$( jq -r '.password' <<< "$e2e_config" )
		export WP_FORCE_ADMIN_USER WP_FORCE_ADMIN_PASS
	fi
}

configure_wp_env() {
	$BASE_CMD exec-silent -- chown -R www-data:www-data /var/www/html/wp-content/uploads
	$BASE_CMD exec-silent -- mkdir -p /var/www/html/wp-content/jetpack-waf
	$BASE_CMD exec-silent -- chown -R www-data:www-data /var/www/html/wp-content/jetpack-waf
	$BASE_CMD wp plugin status

	$BASE_CMD wp plugin activate jetpack
	$BASE_CMD wp plugin activate e2e-direct-filesystem
	$BASE_CMD wp plugin activate e2e-plan-data-interceptor
	$BASE_CMD wp plugin activate e2e-waf-data-interceptor
	$BASE_CMD wp plugin activate e2e-search-test-helper
	$BASE_CMD wp plugin activate e2e-wpcom-request-interceptor
	$BASE_CMD wp plugin activate e2e-social-config
	if [ "${1}" == "--activate-plugins" ]; then
		shift
		for var in "$@"; do
			# shellcheck disable=SC2086
			pnpm $BASE_CMD wp plugin activate "$var"
		done
	fi
	$BASE_CMD wp option set permalink_structure ""
	$BASE_CMD wp jetpack module deactivate sso

	# Disable modules that may interfere with login flow.
	$BASE_CMD wp jetpack module deactivate account-protection
	$BASE_CMD wp jetpack module deactivate protect

	echo
	$BASE_CMD wp plugin status
	echo
	echo "WordPress is ready!"
	echo
}

if [ "${1}" == "start" ]; then
	start_env "${@:2}"
elif [ "${1}" == "stop" ]; then
	stop_env
elif [ "${1}" == "reset" ]; then
	reset_env "${@:2}"
elif [ "${1}" == "clean" ]; then
	clean_env
elif [ "${1}" == "new" ]; then
	new_env "${@:2}"
elif [ "${1}" == "gb-setup" ]; then
	gb_setup
elif [ "${1}" == "usage" ]; then
	usage
else
	usage
fi

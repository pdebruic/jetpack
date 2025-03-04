#!/usr/bin/env bash

# Exit if any command fails.
set -e

usage() {
	echo "usage: $0 command"
	echo "  start                        Setup the docker containers for E2E tests"
	echo "  stop                         Stop the docker containers for E2E tests"
	echo "  reset                        Reset the containers state"
	echo "  gb-setup                     Setup Gutenberg plugin"
	echo "  -h | usage                   Output this message"
	exit 1
}

BASE_CMD='pnpx jetpack docker --type e2e --name t1'

start_env() {
	$BASE_CMD up -d
	$BASE_CMD install
	configure_wp_env
}

reset_env() {
	$BASE_CMD wp -- db reset --yes
	$BASE_CMD install
	configure_wp_env
}

stop_env() {
	$BASE_CMD down
}

gb_setup() {
	GB_ZIP="wp-content/gutenberg.zip"
	$BASE_CMD exec-silent -- /usr/local/src/jetpack-monorepo/tools/e2e-commons/bin/container-setup.sh gb-setup $GB_ZIP
	$BASE_CMD wp plugin install $GB_ZIP
	$BASE_CMD wp plugin activate gutenberg
}

configure_wp_env() {
	$BASE_CMD wp plugin activate jetpack
	$BASE_CMD wp plugin activate e2e-plan-data-interceptor
	$BASE_CMD wp option set permalink_structure ""

	echo
	echo "WordPress is ready!"
	echo
}

if [ "${1}" == "start" ]; then
	start_env
elif [ "${1}" == "stop" ]; then
	stop_env
elif [ "${1}" == "reset" ]; then
	reset_env
elif [ "${1}" == "gb-setup" ]; then
	gb_setup
elif [ "${1}" == "usage" ]; then
	usage
else
	usage
fi

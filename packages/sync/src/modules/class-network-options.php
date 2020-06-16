<?php
/**
 * Network Options sync module.
 *
 * @package automattic/jetpack-sync
 */

namespace Automattic\Jetpack\Sync\Modules;

use Automattic\Jetpack\Sync\Defaults;

/**
 * Class to handle sync for network options.
 */
class Network_Options extends Module {
	/**
	 * Allowlist for network options we want to sync.
	 *
	 * @access private
	 *
	 * @var array
	 */
	private $network_options_allowlist;

	/**
	 * Sync module name.
	 *
	 * @access public
	 *
	 * @return string
	 */
	public function name() {
		return 'network_options';
	}

	/**
	 * Initialize network options action listeners when on multisite.
	 *
	 * @access public
	 *
	 * @param callable $callable Action handler callable.
	 */
	public function init_listeners( $callable ) {
		if ( ! is_multisite() ) {
			return;
		}

		// Multi site network options.
		add_action( 'add_site_option', $callable, 10, 2 );
		add_action( 'update_site_option', $callable, 10, 3 );
		add_action( 'delete_site_option', $callable, 10, 1 );

		$allowlist_network_option_handler = array( $this, 'allowlist_network_options' );
		add_filter( 'jetpack_sync_before_enqueue_delete_site_option', $allowlist_network_option_handler );
		add_filter( 'jetpack_sync_before_enqueue_add_site_option', $allowlist_network_option_handler );
		add_filter( 'jetpack_sync_before_enqueue_update_site_option', $allowlist_network_option_handler );
	}

	/**
	 * Initialize network options action listeners for full sync.
	 *
	 * @access public
	 *
	 * @param callable $callable Action handler callable.
	 */
	public function init_full_sync_listeners( $callable ) {
		add_action( 'jetpack_full_sync_network_options', $callable );
	}

	/**
	 * Initialize the module in the sender.
	 *
	 * @access public
	 */
	public function init_before_send() {
		if ( ! is_multisite() ) {
			return;
		}

		// Full sync.
		add_filter(
			'jetpack_sync_before_send_jetpack_full_sync_network_options',
			array(
				$this,
				'expand_network_options',
			)
		);
	}

	/**
	 * Set module defaults.
	 * Define the network options allowlist based on the default one.
	 *
	 * @access public
	 */
	public function set_defaults() {
		$this->network_options_allowlist = Defaults::$default_network_options_allowlist;
	}

	/**
	 * Enqueue the network options actions for full sync.
	 *
	 * @access public
	 *
	 * @param array   $config               Full sync configuration for this sync module.
	 * @param int     $max_items_to_enqueue Maximum number of items to enqueue.
	 * @param boolean $state                True if full sync has finished enqueueing this module, false otherwise.
	 * @return array Number of actions enqueued, and next module state.
	 */
	public function enqueue_full_sync_actions( $config, $max_items_to_enqueue, $state ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		if ( ! is_multisite() ) {
			return array( null, true );
		}

		/**
		 * Tells the client to sync all options to the server
		 *
		 * @since 4.2.0
		 *
		 * @param boolean Whether to expand options (should always be true)
		 */
		do_action( 'jetpack_full_sync_network_options', true );

		// The number of actions enqueued, and next module state (true == done).
		return array( 1, true );
	}

	/**
	 * Send the network options actions for full sync.
	 *
	 * @access public
	 *
	 * @param array $config Full sync configuration for this sync module.
	 * @param int   $send_until The timestamp until the current request can send.
	 * @param array $state This module Full Sync status.
	 *
	 * @return array This module Full Sync status.
	 */
	public function send_full_sync_actions( $config, $send_until, $state ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		if ( ! is_multisite() ) {
			return array( null, true );
		}

		// we call this instead of do_action when sending immediately.
		$this->send_action( 'jetpack_full_sync_network_options', array( true ) );

		// The number of actions enqueued, and next module state (true == done).
		return array( 'finished' => true );
	}

	/**
	 * Retrieve an estimated number of actions that will be enqueued.
	 *
	 * @access public
	 *
	 * @param array $config Full sync configuration for this sync module.
	 * @return array Number of items yet to be enqueued.
	 */
	public function estimate_full_sync_actions( $config ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		if ( ! is_multisite() ) {
			return null;
		}

		return 1;
	}

	/**
	 * Retrieve the actions that will be sent for this module during a full sync.
	 *
	 * @access public
	 *
	 * @return array Full sync actions of this module.
	 */
	public function get_full_sync_actions() {
		return array( 'jetpack_full_sync_network_options' );
	}

	/**
	 * Retrieve all network options as per the current network options allowlist.
	 *
	 * @access public
	 *
	 * @return array All network options.
	 */
	public function get_all_network_options() {
		$options = array();
		foreach ( $this->network_options_allowlist as $option ) {
			$options[ $option ] = get_site_option( $option );
		}

		return $options;
	}

	/**
	 * Set the network options allowlist.
	 *
	 * @access public
	 *
	 * @param array $options The new network options allowlist.
	 */
	public function set_network_options_allowlist( $options ) {
		$this->network_options_allowlist = $options;
	}

	/**
	 * Get the network options allowlist.
	 *
	 * @access public
	 *
	 * @return array The network options allowlist.
	 */
	public function get_network_options_allowlist() {
		return $this->network_options_allowlist;
	}

	/**
	 * Reject non-allowed network options.
	 *
	 * @access public
	 *
	 * @param array $args The hook parameters.
	 * @return array|false $args The hook parameters, false if not an allowed network option.
	 */
	public function allowlist_network_options( $args ) {
		if ( ! $this->is_allowed_network_option( $args[0] ) ) {
			return false;
		}

		return $args;
	}

	/**
	 * Whether the option is a allowed network option in a multisite system.
	 *
	 * @access public
	 *
	 * @param string $option Option name.
	 * @return boolean True if this is a allowed network option.
	 */
	public function is_allowed_network_option( $option ) {
		return is_multisite() && in_array( $option, $this->network_options_allowlist, true );
	}

	/**
	 * Expand the network options within a hook before they are serialized and sent to the server.
	 *
	 * @access public
	 *
	 * @param array $args The hook parameters.
	 * @return array $args The hook parameters.
	 */
	public function expand_network_options( $args ) {
		if ( $args[0] ) {
			return $this->get_all_network_options();
		}

		return $args;
	}

	/**
	 * Return Total number of objects.
	 *
	 * @param array $config Full sync configuration for this sync module.
	 *
	 * @return int total
	 */
	public function total( $config ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		return count( $this->network_options_allowlist );
	}
}

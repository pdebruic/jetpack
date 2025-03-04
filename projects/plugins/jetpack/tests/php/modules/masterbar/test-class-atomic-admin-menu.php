<?php
/**
 * Tests for Atomic_Admin_Menu class.
 *
 * @package automattic/jetpack
 */

use Automattic\Jetpack\Dashboard_Customizations\Atomic_Admin_Menu;
use Automattic\Jetpack\Status;

require_jetpack_file( 'modules/masterbar/admin-menu/class-atomic-admin-menu.php' );
require_jetpack_file( 'tests/php/modules/masterbar/data/admin-menu.php' );

/**
 * Class Test_Atomic_Admin_Menu.
 *
 * @coversDefaultClass Automattic\Jetpack\Dashboard_Customizations\Atomic_Admin_Menu
 */
class Test_Atomic_Admin_Menu extends WP_UnitTestCase {

	/**
	 * Menu data fixture.
	 *
	 * @var array
	 */
	public static $menu_data;

	/**
	 * Submenu data fixture.
	 *
	 * @var array
	 */
	public static $submenu_data;

	/**
	 * Test domain.
	 *
	 * @var string
	 */
	public static $domain;

	/**
	 * Whether this testsuite is run on WP.com.
	 *
	 * @var bool
	 */
	public static $is_wpcom;

	/**
	 * Admin menu instance.
	 *
	 * @var Atomic_Admin_Menu
	 */
	public static $admin_menu;

	/**
	 * Mock user ID.
	 *
	 * @var int
	 */
	private static $user_id = 0;

	/**
	 * Create shared fixtures.
	 *
	 * @param WP_UnitTest_Factory $factory Fixture factory.
	 */
	public static function wpSetUpBeforeClass( $factory ) {
		static::$domain       = ( new Status() )->get_site_suffix();
		static::$user_id      = $factory->user->create( array( 'role' => 'administrator' ) );
		static::$menu_data    = get_wpcom_menu_fixture();
		static::$submenu_data = get_submenu_fixture();
	}

	/**
	 * Set up data.
	 */
	public function set_up() {
		parent::set_up();
		global $menu, $submenu;

		// Initialize in setUp so it registers hooks for every test.
		static::$admin_menu = Atomic_Admin_Menu::get_instance();

		$menu    = static::$menu_data;
		$submenu = static::$submenu_data;

		wp_set_current_user( static::$user_id );
	}

	/**
	 * Tests add_browse_sites_link.
	 *
	 * @covers ::add_browse_sites_link
	 */
	public function test_add_browse_sites_link() {
		global $menu;

		// No output when executed in single site mode.
		static::$admin_menu->add_browse_sites_link();
		$this->assertArrayNotHasKey( 0, $menu );
	}

	/**
	 * Tests add_browse_sites_link.
	 *
	 * @covers ::add_browse_sites_link
	 */
	public function test_add_browse_sites_link_multisite() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Only used on multisite' );
		}

		global $menu;

		// No output when user has just one site.
		static::$admin_menu->add_browse_sites_link();
		$this->assertArrayNotHasKey( 0, $menu );

		// Give user a second site.
		update_user_option( static::$user_id, 'wpcom_site_count', 2 );

		static::$admin_menu->add_browse_sites_link();

		$browse_sites_menu_item = array(
			'Browse sites',
			'read',
			'https://wordpress.com/home',
			'site-switcher',
			'menu-top toplevel_page_https://wordpress.com/home',
			'toplevel_page_https://wordpress.com/home',
			'dashicons-arrow-left-alt2',
		);
		$this->assertSame( $menu[0], $browse_sites_menu_item );

		delete_user_option( static::$user_id, 'wpcom_site_count' );
	}

	/**
	 * Tests add_new_site_link.
	 *
	 * @covers ::add_new_site_link
	 */
	public function test_add_new_site_link() {
		global $menu;

		// Set jetpack user data.
		update_user_option( static::$user_id, 'wpcom_site_count', 1 );

		static::$admin_menu->add_new_site_link();

		$new_site_menu_item = array(
			'Add New Site',
			'read',
			'https://wordpress.com/start?ref=calypso-sidebar',
			'Add New Site',
			'menu-top toplevel_page_https://wordpress.com/start?ref=calypso-sidebar',
			'toplevel_page_https://wordpress.com/start?ref=calypso-sidebar',
			'dashicons-plus-alt',
		);
		$this->assertSame( array_pop( $menu ), $new_site_menu_item );

		delete_user_option( static::$user_id, 'wpcom_site_count' );
	}

	/**
	 * Tests add_site_card_menu
	 *
	 * @covers ::add_site_card_menu
	 */
	public function test_add_site_card_menu() {
		global $menu;

		if ( ! function_exists( 'site_is_private' ) ) {
			function site_is_private() { // phpcs:ignore
				return false;
			}
		}
		static::$admin_menu->add_site_card_menu();

		$home_url            = home_url();
		$site_card_menu_item = array(
			// phpcs:ignore Squiz.Strings.DoubleQuoteUsage.NotRequired
			'
<div class="site__info">
	<div class="site__title">' . get_option( 'blogname' ) . '</div>
	<div class="site__domain">' . static::$domain . "</div>\n\t\n</div>",
			'read',
			$home_url,
			'site-card',
			'menu-top toplevel_page_' . $home_url,
			'toplevel_page_' . $home_url,
			'data:image/svg+xml,%3Csvg%20class%3D%22gridicon%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%2024%2024%22%3E%3Ctitle%3EGlobe%3C%2Ftitle%3E%3Crect%20fill-opacity%3D%220%22%20x%3D%220%22%20width%3D%2224%22%20height%3D%2224%22%2F%3E%3Cg%3E%3Cpath%20fill%3D%22%23fff%22%20d%3D%22M12%202C6.477%202%202%206.477%202%2012s4.477%2010%2010%2010%2010-4.477%2010-10S17.523%202%2012%202zm0%2018l2-2%201-1v-2h-2v-1l-1-1H9v3l2%202v1.93c-3.94-.494-7-3.858-7-7.93l1%201h2v-2h2l3-3V6h-2L9%205v-.41C9.927%204.21%2010.94%204%2012%204s2.073.212%203%20.59V6l-1%201v2l1%201%203.13-3.13c.752.897%201.304%201.964%201.606%203.13H18l-2%202v2l1%201h2l.286.286C18.03%2018.06%2015.24%2020%2012%2020z%22%2F%3E%3C%2Fg%3E%3C%2Fsvg%3E',
		);

		$this->assertEquals( $menu[1], $site_card_menu_item );
	}

	/**
	 * Tests set_site_card_menu_class
	 *
	 * @covers ::set_site_card_menu_class
	 */
	public function test_set_site_card_menu_class() {
		global $menu;

		if ( ! function_exists( 'site_is_private' ) ) {
			function site_is_private() { // phpcs:ignore
				return false;
			}
		}

		static::$admin_menu->add_site_card_menu();

		$menu = static::$admin_menu->set_site_card_menu_class( $menu );
		$this->assertStringNotContainsString( 'has-site-icon', $menu[1][4] );

		// Atomic fallback site icon counts as no site icon.
		add_filter( 'get_site_icon_url', array( $this, 'wpcomsh_site_icon_url' ) );
		$menu = static::$admin_menu->set_site_card_menu_class( $menu );
		remove_filter( 'get_site_icon_url', array( $this, 'wpcomsh_site_icon_url' ) );
		$this->assertStringNotContainsString( 'has-site-icon', $menu[1][4] );

		// Custom site icon triggers CSS class.
		add_filter( 'get_site_icon_url', array( $this, 'custom_site_icon_url' ) );
		$menu = static::$admin_menu->set_site_card_menu_class( $menu );
		remove_filter( 'get_site_icon_url', array( $this, 'custom_site_icon_url' ) );
		$this->assertStringContainsString( 'has-site-icon', $menu[1][4] );
	}

	/**
	 * Shim wpcomsh fallback site icon.
	 *
	 * @return string
	 */
	public function wpcomsh_site_icon_url() {
		return 'https://s0.wp.com/i/webclip.png';
	}

	/**
	 * Custom site icon.
	 *
	 * @return string
	 */
	public function custom_site_icon_url() {
		return 'https://s0.wp.com/i/jetpack.png';
	}

	/**
	 * Tests get_preferred_view
	 *
	 * @covers ::get_preferred_view
	 */
	public function test_get_preferred_view() {
		$this->assertSame( 'classic', static::$admin_menu->get_preferred_view( 'plugins.php' ) );
		$this->assertSame( 'classic', static::$admin_menu->get_preferred_view( 'export.php' ) );
	}

	/**
	 * Tests add_upgrades_menu
	 *
	 * @covers ::add_upgrades_menu
	 */
	public function test_add_upgrades_menu() {
		global $submenu;

		static::$admin_menu->add_upgrades_menu();

		$this->assertSame( 'https://wordpress.com/plans/my-plan/' . static::$domain, $submenu['paid-upgrades.php'][1][2] );
		$this->assertSame( 'https://wordpress.com/domains/manage/' . static::$domain, $submenu['paid-upgrades.php'][2][2] );

		/** This filter is already documented in modules/masterbar/admin-menu/class-atomic-admin-menu.php */
		if ( apply_filters( 'jetpack_show_wpcom_upgrades_email_menu', false ) ) {
			$this->assertSame( 'https://wordpress.com/email/' . static::$domain, $submenu['paid-upgrades.php'][3][2] );
			$this->assertSame( 'https://wordpress.com/purchases/subscriptions/' . static::$domain, $submenu['paid-upgrades.php'][4][2] );
		} else {
			$this->assertSame( 'https://wordpress.com/purchases/subscriptions/' . static::$domain, $submenu['paid-upgrades.php'][3][2] );
		}
	}

	/**
	 * Tests add_inbox_menu
	 *
	 * @covers ::add_inbox_menu
	 */
	public function test_add_inbox_menu() {
		global $menu;

		add_filter( 'jetpack_show_wpcom_inbox_menu', '__return_true' );
		static::$admin_menu->add_inbox_menu();

		$this->assertSame( 'https://wordpress.com/inbox/' . static::$domain, $menu['4.64424'][2] );
	}

	/**
	 * Tests add_options_menu
	 *
	 * @covers ::add_options_menu
	 */
	public function test_add_options_menu() {
		global $submenu;

		static::$admin_menu->add_options_menu();
		$this->assertSame( 'https://wordpress.com/hosting-config/' . static::$domain, $submenu['options-general.php'][11][2] );
	}

	/**
	 * Tests add_gutenberg_menus
	 *
	 * @covers ::add_gutenberg_menus
	 */
	public function test_add_gutenberg_menus() {
		global $menu;
		static::$admin_menu->add_gutenberg_menus();

		// Gutenberg plugin menu should not be visible.
		$this->assertArrayNotHasKey( 101, $menu );
	}
}

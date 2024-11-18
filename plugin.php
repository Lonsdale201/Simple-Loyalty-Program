<?php
/**
 * Plugin Name: Simple Loyalty Program
 * Description: Loyalty settings for WooCommerce
 * Version: 1.0
 * Author: Soczó Kristóf
 * Author URI: https://hellowp.io/en/
 * Plugin URI: https://github.com/Lonsdale201/Simple-Loyalty-Program
 * Text Domain: hw-woo-loyalty
 * Domain Path: /languages
 * Requires Plugins: woocommerce
 * Requires at least: 6.0
 * Tested up to: 6.7s
 * Requires PHP: 8.0
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace HelloWP\HWLoyalty;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'HWLoyalty_URL', plugin_dir_url( __FILE__ ) );
define( 'HWLoyalty_PATH', plugin_dir_path( __FILE__ ) );

require_once __DIR__ . '/vendor/autoload.php';
require dirname(__FILE__) . '/plugin-update-checker/plugin-update-checker.php';

use YahnisElsts\PluginUpdateChecker\v5p0\PucFactory;
use HelloWP\HWLoyalty\App\Helper\SettingsConfig;

/**
 * The main class for the Loyalty Program settings plugin
 */
final class HWLoyalty {

    const MINIMUM_WOOCOMMERCE_VERSION = '9.2.0';
    const MINIMUM_WORDPRESS_VERSION = '6.0';
    const MINIMUM_PHP_VERSION = '8.0';

    private static $_instance = null;

    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        $this->add_hooks();
    }

    private function add_hooks() {
        add_action('init', [$this, 'load_plugin_textdomain'], -999);
        add_action('plugins_loaded', [$this, 'on_plugins_loaded']);
        add_action('update_option_hw_loyalty_enable_inactivity', [$this, 'handle_inactivity_option_update'], 10, 2);
        add_action('before_woocommerce_init', [$this, 'declare_hpos_compatibility']);
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), [$this, 'add_settings_link']);
    }
    
    public function load_plugin_textdomain() {
        if ( version_compare( $GLOBALS['wp_version'], '6.7', '<' ) ) {
            load_plugin_textdomain( 'hw-woo-loyalty', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
        } else {
            load_textdomain( 'hw-woo-loyalty', HWLoyalty_PATH . 'languages/hw-woo-loyalty-' . determine_locale() . '.mo' );
        }
    }
    
    public function add_settings_link($links) {
        $settings_link = '<a href="' . esc_url(admin_url('admin.php?page=wc-settings&tab=hw_loyalty')) . '">' . __('Settings', 'hw-woo-loyalty') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    public function on_plugins_loaded() {
        if ( ! $this->is_compatible() ) {
            return;
        }
        
        \HelloWP\HWLoyalty\App\Admin\AdminSettings::get_instance();
        \HelloWP\HWLoyalty\App\Modules\Manager::init();
        \HelloWP\HWLoyalty\App\Helper\Hooks::init();

        $myUpdateChecker = PucFactory::buildUpdateChecker(
            'https://plugin-uodater.alex.hellodevs.dev/plugins/hw-woo-loyalty.json',
            __FILE__,
            'hw-woo-loyalty'
        );
    }

    public function handle_inactivity_option_update($old_value, $value) {
        $permanent_program = \HelloWP\HWLoyalty\App\Helper\SettingsConfig::get('hw_loyalty_permanent_program', 'no');
        $enable_inactivity = \HelloWP\HWLoyalty\App\Helper\SettingsConfig::get('hw_loyalty_enable_inactivity', 'no');
    
        if ($permanent_program === 'yes' && $enable_inactivity === 'yes') {
            \HelloWP\HWLoyalty\App\Helper\CronHandler::schedule_cron_event();
        } else {
            \HelloWP\HWLoyalty\App\Helper\CronHandler::clear_cron_event();
        }
    }
    
    public function declare_hpos_compatibility() {
        if (!class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
            return;
        }
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
    

    public static function deactivate_plugin() {
        if (class_exists('HelloWP\HWLoyalty\App\Helper\CronHandler')) {
            \HelloWP\HWLoyalty\App\Helper\CronHandler::clear_cron_event();
        }
    }

    public function is_compatible() {
        if ( ! class_exists( 'WooCommerce' ) || version_compare( WC_VERSION, self::MINIMUM_WOOCOMMERCE_VERSION, '<' ) ) {
            add_action( 'admin_notices', [ $this, 'admin_notice_minimum_woocommerce_version' ] );
            return false;
        }

        if ( version_compare( get_bloginfo( 'version' ), self::MINIMUM_WORDPRESS_VERSION, '<' ) ) {
            add_action( 'admin_notices', [ $this, 'admin_notice_minimum_wordpress_version' ] );
            return false;
        }

        if ( version_compare( PHP_VERSION, self::MINIMUM_PHP_VERSION, '<' ) ) {
            add_action( 'admin_notices', [ $this, 'admin_notice_minimum_php_version' ] );
            return false;
        }

        return true;
    }

    public function admin_notice_minimum_woocommerce_version() {
        if ( ! current_user_can('manage_options') ) {
            return;
        }
        echo '<div class="notice notice-warning is-dismissible"><p>';
        echo sprintf(__('This plugin requires WooCommerce %s or later. Please update WooCommerce.', 'hw-woo-loyalty'), self::MINIMUM_WOOCOMMERCE_VERSION);
        echo '</p></div>';
    }
    
    public function admin_notice_minimum_wordpress_version() {
        if ( ! current_user_can('manage_options') ) {
            return;
        }
        echo '<div class="notice notice-warning is-dismissible"><p>';
        echo sprintf(__('This plugin requires WordPress %s or later. Please update WordPress.', 'hw-woo-loyalty'), self::MINIMUM_WORDPRESS_VERSION);
        echo '</p></div>';
    }
    
    public function admin_notice_minimum_php_version() {
        if ( ! current_user_can('manage_options') ) {
            return;
        }
        echo '<div class="notice notice-warning is-dismissible"><p>';
        echo sprintf(__('This plugin requires PHP %s or later. Please update your PHP version.', 'hw-woo-loyalty'), self::MINIMUM_PHP_VERSION);
        echo '</p></div>';
    }
}

HWLoyalty::instance();



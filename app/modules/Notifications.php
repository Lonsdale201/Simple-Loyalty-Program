<?php

namespace HelloWP\HWLoyalty\App\Modules;

use HelloWP\HWLoyalty\App\Helper\SettingsConfig;
use HelloWP\HWLoyalty\App\Modules\LoyaltyDiscount;
use HelloWP\HWLoyalty\App\Modules\CycleDiscount;
use HelloWP\HWLoyalty\App\Modules\SmartCodes;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Notifications {
    private static $instance = null;
    private $myaccount_endpoint_slug;

    private function __construct() {
        if (SettingsConfig::get('hw_loyalty_enable_notifications', 'no') === 'yes') {
            add_action('woocommerce_before_cart', [$this, 'display_notification']);
            add_action('woocommerce_before_checkout_form', [$this, 'display_notification']);
            add_action('wp_enqueue_scripts', [$this, 'enqueue_styles']);
        }

        // Initialize My Account menu item if enabled
        if (SettingsConfig::get('hw_loyalty_enable_myaccount_menu', 'no') === 'yes') {
            $this->myaccount_endpoint_slug = $this->generate_slug(SettingsConfig::get('hw_loyalty_myaccount_menu_label', 'loyalty-program'));

            // Register endpoint
            add_action('init', [$this, 'add_myaccount_endpoint']);
            add_filter('query_vars', [$this, 'add_query_vars'], 0);

            // Add menu item and display content
            add_filter('woocommerce_account_menu_items', [$this, 'add_myaccount_menu_item']);
            add_action("woocommerce_account_{$this->myaccount_endpoint_slug}_endpoint", [$this, 'display_myaccount_content']);
        }
    }

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function enqueue_styles() {
        if ((is_cart() || is_checkout()) && $this->is_discount_notification_applicable()) {
            wp_enqueue_style('frontend-loyality', HWLoyalty_URL . 'assets/frontend-loyality.css');
        }
    }

    public function display_notification() {
        if (is_user_logged_in()) {
            $this->display_logged_in_user_notification();
        } else {
            $this->display_logged_out_user_notification();
        }
    }

    private function display_logged_in_user_notification() {
        if ($this->is_discount_notification_applicable()) {
            $message = SettingsConfig::get('hw_loyalty_notification_message', '');
            if ($message) {
                $message = do_shortcode($message);
                include HWLoyalty_PATH . 'templates/notifications-loggedin.php';
            }
        }
    }

    private function display_logged_out_user_notification() {
        $message = SettingsConfig::get('hw_loyalty_notification_logged_out', '');
        if ($message && SettingsConfig::get('hw_loyalty_enable_notifications', 'no') === 'yes') {
            $message = do_shortcode($message);
            include HWLoyalty_PATH . 'templates/notifications-loggedout.php';
        }
    }

    private function is_discount_notification_applicable() {
        $user_id = get_current_user_id();
        return LoyaltyDiscount::is_loyalty_member($user_id) || CycleDiscount::is_eligible($user_id);
    }

    /**
     * Adds a new endpoint for the loyalty program in My Account.
     */
    public function add_myaccount_endpoint() {
        add_rewrite_endpoint($this->myaccount_endpoint_slug, EP_PAGES);
    }

    /**
     * Adds query vars for the custom endpoint.
     *
     * @param array $vars Existing query vars.
     * @return array Modified query vars.
     */
    public function add_query_vars($vars) {
        $vars[] = $this->myaccount_endpoint_slug;
        return $vars;
    }

    /**
     * Adds a new menu item to the My Account menu.
     *
     * @param array $items Existing My Account menu items.
     * @return array Modified menu items with the loyalty program item added.
     */
    public function add_myaccount_menu_item($items) {
        $menu_label = SettingsConfig::get('hw_loyalty_myaccount_menu_label', __('Loyalty Program', 'hw-woo-torzsvasarloi'));

        // Insert the new menu item before the logout link
        $logout = $items['customer-logout'];
        unset($items['customer-logout']);

        $items[$this->myaccount_endpoint_slug] = $menu_label;
        $items['customer-logout'] = $logout;

        return $items;
    }

    /**
     * Displays the content for the loyalty program page in My Account.
     */
    public function display_myaccount_content() {
        $content = SettingsConfig::get('hw_loyalty_myaccount_menu_content', '');


        $content = SmartCodes::process_smart_codes($content);
        // Parse shortcodes in the content
        echo do_shortcode($content);
    }

    /**
     * Generates a URL-friendly slug from the provided label.
     *
     * @param string $label The label to convert.
     * @return string URL-friendly slug.
     */
    private function generate_slug($label) {
        $label = sanitize_title($label); 
        return apply_filters('hw_loyalty_slug', $label); 
    }
}

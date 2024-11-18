<?php
namespace HelloWP\HWLoyalty\App\Modules;

use HelloWP\HWLoyalty\App\Helper\SettingsConfig;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class LoyaltyDiscount {

    /**
     * Register WooCommerce hooks
     */
    public static function init() {
        add_action('woocommerce_order_status_completed', [self::class, 'check_and_update_loyalty_status'], 10, 1);
    }

    /**
     * Checks if a user qualifies for the loyalty program when an order is completed.
     *
     * @param int $order_id The ID of the completed order.
     */
    public static function check_and_update_loyalty_status($order_id) {
        $order = wc_get_order($order_id);
        $user_id = $order->get_user_id();
    
        // Run eligibility check and update loyalty status
        if ($user_id) {
            $is_eligible = self::is_user_eligible($user_id);
    
            // If user qualifies for the program and does not have a join date, set it
            if ($is_eligible && empty(get_user_meta($user_id, 'hw_loyalty_joined_date', true))) {
                update_user_meta($user_id, 'hw_loyalty_joined_date', current_time('mysql'));
    
                do_action('hw_loyalty_user_became_member', $user_id);
            }
        }
    }



    /**
     * Check if a user is eligible for the loyalty program based on the permanent settings.
     *
     * @param int $user_id The ID of the user to check.
     * @return bool True if the user is eligible, false otherwise.
     */
    public static function is_user_eligible($user_id) {
        $settings = SettingsConfig::get_all_settings();
        
        // Check if the permanent program is enabled
        if ($settings['permanent_program'] !== 'yes') {
            return false;
        }
    
        // Get user metadata
        $total_spent = (float) get_user_meta($user_id, 'hw_total_spent_amount', true);
        $total_items = (int) get_user_meta($user_id, 'hw_total_order_items', true);
        $user_role = self::get_user_role($user_id);
    
        // Check conditions based on 'AND' or 'OR' relation setting
        $conditions_met = [];
        
        // Check total spent condition if set
        if (!empty($settings['permanent_min_spent']) && $settings['permanent_min_spent'] > 0) {
            $conditions_met[] = $total_spent >= $settings['permanent_min_spent'];
        }
    
        // Check total items condition if set
        if (!empty($settings['permanent_min_order_items']) && $settings['permanent_min_order_items'] > 0) {
            $conditions_met[] = $total_items >= $settings['permanent_min_order_items'];
        }
    
        // Check user role condition if roles are specified
        if (!empty($settings['permanent_user_roles'])) {
            $conditions_met[] = in_array($user_role, $settings['permanent_user_roles']);
        }
    
        // Evaluate conditions based on relation
        if ($settings['permanent_condition_relation'] === 'AND') {
            $is_eligible = !in_array(false, $conditions_met, true);
        } else {
            $is_eligible = in_array(true, $conditions_met, true);
        }
    
        // Update user's loyalty status meta only if it is currently false
        if ($is_eligible && get_user_meta($user_id, 'hw_loyalty_status', true) !== 'true') {
            update_user_meta($user_id, 'hw_loyalty_status', 'true');
        } elseif (!$is_eligible) {
            update_user_meta($user_id, 'hw_loyalty_status', 'false');
        }
    
        return $is_eligible;
    }
    

    /**
     * Simple method to check if a user is already a loyalty member based on meta data.
     *
     * @param int $user_id The user ID.
     * @return bool True if the user is a loyalty member, false otherwise.
     */
    public static function is_loyalty_member($user_id) {
        $settings = SettingsConfig::get_all_settings();
        
        if ($settings['permanent_program'] !== 'yes') {
            return false;
        }

        // Ellenőrzi a törzsvásárlói státuszt a meta adat alapján
        $is_member = get_user_meta($user_id, 'hw_loyalty_status', true) === 'true';

        // Szűrő alkalmazása a törzsvásárlói státuszra
        return apply_filters('hw_is_loyalty_member', $is_member, $user_id);
    }
    
    /**
     * Get the loyalty status of the current logged-in user or a specified user ID.
     *
     * @param int|null $user_id The user ID to check, or null to use the current logged-in user.
     * @return array An array containing the user's loyalty status and related information.
     */
    public static function get_loyalty_status($user_id = null) {
        if (is_null($user_id)) {
            if (!is_user_logged_in()) {
                return ['status' => 'not_logged_in'];
            }
            $user_id = get_current_user_id();
        }
        

        $is_member = self::is_loyalty_member($user_id);
        $join_date = get_user_meta($user_id, 'hw_loyalty_joined_date', true);
        
        $status_data = [
            'is_member' => $is_member,
            'join_date' => $join_date,
            'total_spent' => get_user_meta($user_id, 'hw_total_spent_amount', true),
            'total_items' => get_user_meta($user_id, 'hw_total_order_items', true),
        ];

        return apply_filters('hw_get_loyalty_status', $status_data, $user_id);
    }


    /**
     * Retrieve the primary user role.
     *
     * @param int $user_id The user ID.
     * @return string The user's primary role.
     */
    private static function get_user_role($user_id) {
        $user = get_userdata($user_id);
        return $user->roles[0] ?? '';
    }
}

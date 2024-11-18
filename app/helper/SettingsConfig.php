<?php

namespace HelloWP\HWLoyalty\App\Helper;

if (!defined('ABSPATH')) {
    exit;
}

class SettingsConfig {

    /**
     * Retrieves the value of a specific setting option from the admin panel.
     * If no value is saved, it returns the default value.
     *
     * @param string $option_name The name of the option.
     * @param mixed $default The default value if the option is not set.
     * @return mixed The option value or the default value.
     */
    public static function get($option_name, $default = null) {
        $value = get_option($option_name, $default);

        if ($value === false) {
            return $default;
        }

        return $value;
    }

    /**
     * Retrieves all settings relevant to the loyalty program.
     * 
     * @return array All configuration settings with default values.
     */
    public static function get_all_settings() {
        return [
            'discount_name' => self::get('hw_loyalty_discount_name', __('Loyalty Discount', 'hw-woo-loyalty')),
            'target_amount' => self::get('hw_loyalty_target_amount', 20000),
            'enable_cycle_discount' => self::get('hw_loyalty_enable_cycle_discount', 'no'), 
            'disable_sale_product' => self::get('hw_loyalty_sale_exlcude', 'no'), 
            'disable_coupons' => self::get('hw_loyalty_disable_coupons', 'no'),
            'discount_cycle' => self::get('hw_loyalty_discount_cycle', '30'),
            'discount_mode' => self::get('hw_loyalty_discount_mode', 'cart_based'),
            'discount_type' => self::get('hw_loyalty_discount_type', 'cart_based_percentage'),
            'percentage_discount' => self::get('hw_loyalty_percentage_discount', 0),
            'fixed_discount' => self::get('hw_loyalty_fixed_discount', 0),
            'permanent_program' => self::get('hw_loyalty_permanent_program', 'no'),
            
            // permanent(loyalty) conditions settings
            'permanent_min_spent' => self::get('hw_loyalty_permanent_min_spent', 5000),
            'permanent_min_order_items' => self::get('hw_loyalty_permanent_min_order_items', 1),
            'permanent_condition_relation' => self::get('hw_loyalty_condition_relation', 'AND'),
            'permanent_user_roles' => self::get('hw_loyalty_user_roles', []), 

            // Gift settings
            'gift_products' => self::get('hw_loyalty_gift_products', []), 
            'free_gift_label' => self::get('hw_loyalty_free_gift_label', __('Free Gift', 'hw-woo-loyalty')),

            // Messages settings
            'message_coupon_disabled' => self::get('hw_loyalty_message_coupon_disabled', __('Coupon usage is disabled for loyalty customers.', 'hw-woo-loyalty')),
            'message_gift_removed' => self::get('hw_loyalty_message_gift_removed', __('A free gift has been removed from your cart as it is no longer available.', 'hw-woo-loyalty')),
    
            // Notification settings
            'enable_notifications' => self::get('hw_loyalty_enable_notifications', 'no'),
            'notification_message' => self::get('hw_loyalty_notification_message', ''),
            'notification_logged_out' => self::get('hw_loyalty_notification_logged_out', ''),

            // Myaccount settings
            'enable_myaccount_menu' => self::get('hw_loyalty_enable_myaccount_menu', 'no'),
            'myaccount_menu_label' => self::get('hw_loyalty_myaccount_menu_label', __('Loyalty Program', 'hw-woo-loyalty')),
            'myaccount_menu_content' => self::get('hw_loyalty_myaccount_menu_content', ''),
            
            // Fluenntcrm
            'fluentcrm_list' => self::get('hw_loyalty_fluentcrm_list', ''),
            'fluentcrm_list_removal' => self::get('hw_loyalty_fluentcrm_list_removal', 'no'),

            // Inactivity settings
            'enable_inactivity' => self::get('hw_loyalty_enable_inactivity', 'no'),
            'inactivity_time' => self::get('hw_loyalty_inactivity_time', '12'),
            'reset_user_data' => self::get('hw_loyalty_reset_user_data', 'no'),
        ];
    }
    
}
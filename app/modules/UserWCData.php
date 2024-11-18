<?php
namespace HelloWP\HWLoyalty\App\Modules;
use HelloWP\HWLoyalty\App\Helper\SettingsConfig;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Class UserWCData
 * Manages user activity data, total spent amount, and order item count.
 */
class UserWCData {

    /**
     * Initializes hooks for tracking user activity and purchase data.
     */
    public static function init() {
        add_action('wp', [self::class, 'update_last_activity_date']);
        add_action('woocommerce_order_status_completed', [self::class, 'update_order_data'], 10, 1);
        add_action('woocommerce_order_status_refunded', [self::class, 'handle_refund'], 10, 1);
    }

    /**
     * Updates the last activity date for a user.
     */
    public static function update_last_activity_date() {
        if (!self::is_loyalty_program_enabled()) {
            return;
        }
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
            $current_date = current_time('Y-m-d'); 
    
            $last_activity = get_user_meta($user_id, 'hw_last_activity_date', true);
            if ($last_activity !== $current_date) {
                update_user_meta($user_id, 'hw_last_activity_date', $current_date);
            }
        }
    }
    

    /**
     * Updates the total order items and spent amount for a user when an order is completed.
     *
     * @param int $order_id The ID of the completed order.
     */
    public static function update_order_data($order_id) {
        if (!self::is_loyalty_program_enabled()) {
            return;
        }

        $order = wc_get_order($order_id);
        $user_id = $order->get_user_id();

        if (!$user_id) {
            $order_email = $order->get_billing_email();
            $user = get_user_by('email', $order_email);

            if ($user) {
                $user_id = $user->ID;
            }
        }

        if ($user_id && $order->get_meta('_hw_order_processed') !== 'yes') {
            $current_total_items = (int) get_user_meta($user_id, 'hw_total_order_items', true);
            $current_total_spent = (float) get_user_meta($user_id, 'hw_total_spent_amount', true);
            $order_item_count = $order->get_item_count(); 
            $order_total = (float) $order->get_total();   
            update_user_meta($user_id, 'hw_total_order_items', $current_total_items + $order_item_count);
            update_user_meta($user_id, 'hw_total_spent_amount', $current_total_spent + $order_total);
            $order->update_meta_data('_hw_order_processed', 'yes');
            $order->save();
        }
    }

    /**
     * Handles refunds and adjusts the user's order data accordingly.
     *
     * @param int $order_id The ID of the refunded order.
     */
    public static function handle_refund($order_id) {
        if (!self::is_loyalty_program_enabled()) {
            return; 
        }

        $order = wc_get_order($order_id);
        $user_id = $order->get_user_id();

        if (!$user_id) {
            return;
        }

        if ($order->get_meta('_hw_order_processed') === 'yes') {
            $order_total = (float) $order->get_total();
            $order_items_count = (int) $order->get_item_count();

            $current_total_spent = (float) get_user_meta($user_id, 'hw_total_spent_amount', true);
            $current_total_items = (int) get_user_meta($user_id, 'hw_total_order_items', true);

            $updated_total_spent = max(0, $current_total_spent - $order_total);
            $updated_total_items = max(0, $current_total_items - $order_items_count);

            update_user_meta($user_id, 'hw_total_spent_amount', $updated_total_spent);
            update_user_meta($user_id, 'hw_total_order_items', $updated_total_items);

            $order->update_meta_data('_hw_order_refund_processed', 'yes');
            $order->save();
        }
    }

    /**
     * Checks if the loyalty program is enabled.
     *
     * @return bool True if enabled, false otherwise.
     */
    private static function is_loyalty_program_enabled() {
        return SettingsConfig::get('hw_loyalty_permanent_program', 'no') === 'yes';
    }
}

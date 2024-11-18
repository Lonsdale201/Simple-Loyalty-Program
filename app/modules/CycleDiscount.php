<?php

namespace HelloWP\HWLoyalty\App\Modules;

use HelloWP\HWLoyalty\App\Helper\SettingsConfig;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Class CycleDiscount
 * Handles user eligibility based on spending cycle requirements.
 */
class CycleDiscount {
    private static $instance = null;
    private $settings;

    /**
     * Singleton pattern: private constructor to prevent direct instantiation.
     */
    private function __construct() {
        $this->load_settings();
    }

    /**
     * Singleton instance method. Ensures only one instance of the class is created.
     * 
     * @return CycleDiscount The instance of this class.
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Loads all settings from the centralized SettingsConfig class.
     */
    private function load_settings() {
        $this->settings = SettingsConfig::get_all_settings();
    }

    /**
     * Checks if the user is eligible for a discount based on spending requirements within a defined cycle period.
     * 
     * @param int $user_id The ID of the user to check.
     * @return bool True if the user is eligible for the discount, false otherwise.
     */
    public function is_user_eligible_for_discount($user_id) {
        if ($this->settings['enable_cycle_discount'] !== 'yes') {
            return false;
        }
    
        return $this->has_purchased_amount($user_id, $this->settings['target_amount']);
    }
    

    /**
     * Checks if the user has spent the required target amount in the defined cycle period.
     * 
     * @param int $user_id The ID of the user to check.
     * @param float $amount The target spending amount.
     * @return bool True if the user has met the spending requirement, false otherwise.
     */
    private function has_purchased_amount($user_id, $amount) {
        $days = $this->settings['discount_cycle'];
        $date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        $orders = wc_get_orders([
            'customer_id' => $user_id,
            'status' => ['wc-completed'],
            'limit' => -1,
            'date_created' => '>' . $date,
        ]);

        $total_spent = 0;
        foreach ($orders as $order) {
            $total_spent += $order->get_total();
            if ($total_spent >= $amount) {
                return true;
            }
        }
        return false;
    }

    /**
     * Static method to check if a user is eligible for a discount.
     * Allows external files to access eligibility status without creating an instance.
     * 
     * @param int $user_id The ID of the user to check.
     * @return bool True if the user is eligible for the discount, false otherwise.
     */
    public static function is_eligible($user_id) {
        return self::get_instance()->is_user_eligible_for_discount($user_id);
    }
}

<?php
namespace HelloWP\HWLoyalty\App\Helper;

use HelloWP\HWLoyalty\App\Modules\CycleDiscount;
use HelloWP\HWLoyalty\App\Modules\LoyaltyDiscount;
use WC_Cart;
use WC_Tax;
use WC_Order_Item_Fee;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Class DiscountHandler
 * Applies the actual discount based on eligibility and global discount settings.
 */
class DiscountHandler {
    private static $instance = null;
    private $settings;

    /**
     * Singleton pattern: private constructor to prevent direct instantiation.
     */
    private function __construct() {
        $this->load_settings();
        // Hook the apply_discount method to cart calculate fees
        add_action('woocommerce_cart_calculate_fees', [$this, 'apply_discount']);
    }

    /**
     * Singleton instance method. Ensures only one instance of the class is created.
     * 
     * @return DiscountHandler The instance of this class.
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
     * Applies the discount based on the selected discount mode.
     * 
     * @param WC_Cart $cart The WooCommerce cart object.
     */
    public function apply_discount($cart) {
        $user_id = get_current_user_id();
    
        if (LoyaltyDiscount::is_loyalty_member($user_id) || CycleDiscount::is_eligible($user_id)) {

            if ($this->settings['disable_coupons'] === 'yes') {
                if (!empty($cart->applied_coupons)) {
                    $cart->applied_coupons = [];
                    $message_template = SettingsConfig::get(
                        'hw_loyalty_message_coupon_disabled',
                        __('Coupons are not allowed when loyalty discounts are applied.', 'hw-woo-loyalty')
                    );
                    
                    wc_clear_notices();
                    wc_add_notice($message_template, 'notice');
                }
            }
    
            $this->apply_discount_as_fee($cart);
        }
    }
    

    /**
     * Adds the discount as a fee to the cart subtotal if applicable.
     * 
     * @param WC_Cart $cart The WooCommerce cart object.
     */
    private function apply_discount_as_fee($cart) {
        $cart_total = floatval($cart->get_cart_contents_total());
    
        if ($cart_total <= 0) {
            return;
        }
    
        $discount = $this->calculate_discount($cart);
        if ($discount > 0) {
            $taxable = true; 
            $tax_class = ''; 

            $cart->add_fee(
                $this->settings['discount_name'], 
                -$discount,                     
                $taxable,                      
                $tax_class                    
            );
        }
    }
    


    /**
     * Calculates the discount based on global settings for cart-based percentage or fixed discount.
     * 
     * @param WC_Cart $cart The WooCommerce cart object.
     * @return float The calculated discount amount.
     */
    private function calculate_discount($cart) {
        $cart_total = floatval($cart->get_cart_contents_total());
    
        if ($cart_total <= 0) {
            return 0;
        }
    
        if ($this->settings['discount_type'] === 'cart_based_percentage') {
            $percentage_discount = isset($this->settings['percentage_discount']) ? floatval($this->settings['percentage_discount']) : 0;
            if ($percentage_discount <= 0) {
                return 0;
            }
            return $cart_total * ($percentage_discount / 100);
        } elseif ($this->settings['discount_type'] === 'cart_based_fix') {
            $fixed_discount = isset($this->settings['fixed_discount']) ? floatval($this->settings['fixed_discount']) : 0;
            if ($fixed_discount <= 0) {
                return 0;
            }
            return min($fixed_discount, $cart_total);
        }
    
        return 0;
    }

}

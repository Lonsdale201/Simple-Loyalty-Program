<?php

namespace HelloWP\HWLoyalty\App\Modules;

use HelloWP\HWLoyalty\App\Modules\LoyaltyDiscount;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class FreeShipping {
    public function __construct() {
        add_filter('woocommerce_shipping_free_shipping_is_available', [$this, 'handle_free_shipping_availability'], 10, 3);
    }

    public function handle_free_shipping_availability($is_available, $package, $method) {
        if ($method->id !== 'free_shipping') {
            return $is_available;
        }
    
        if ($method->get_option('requires') === 'loyalty_member') {
            $user_id = get_current_user_id();
            $min_amount = $method->get_option('min_amount');
            if (!empty($min_amount) && $min_amount > 0) {
                $cart_total = 0;
                if (function_exists('WC')) {
                    $cart = WC()->cart;
                    if ($cart && !empty($cart)) {
                        $cart_total = $cart->get_cart_contents_total() 
                                    + $cart->get_shipping_total() 
                                    + $cart->get_taxes_total();
                    }
                }
    
                if ($cart_total < $min_amount) {
                    return false; 
                }
            }
    
            if (!$user_id || !LoyaltyDiscount::is_loyalty_member($user_id)) {
                return false;
            }
    
            return true;
        }
        return $is_available;
    }
    
}

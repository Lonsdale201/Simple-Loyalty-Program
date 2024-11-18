<?php
namespace HelloWP\HWLoyalty\App\Modules;

use HelloWP\HWLoyalty\App\Helper\SettingsConfig;
use WC_Cart;

if (!defined('ABSPATH')) {
    exit;
}

class FreeGift {

    /**
     * Initialize WooCommerce hooks for the FreeGift class.
     */
    public static function init() {
        // Hook to validate and apply gift products in the cart
        add_action('woocommerce_cart_loaded_from_session', [self::class, 'validate_and_apply_gift_products'], 20);
        add_action('woocommerce_before_calculate_totals', [self::class, 'validate_and_apply_gift_products'], 15);

        // Hook to remove out-of-stock or invalid gift products
        add_action('woocommerce_cart_loaded_from_session', [self::class, 'remove_out_of_stock_gift_products'], 20);
        add_action('woocommerce_before_calculate_totals', [self::class, 'remove_out_of_stock_gift_products'], 10);

        // Hook to set the price of gift products to zero
        add_action('woocommerce_before_calculate_totals', [self::class, 'set_gift_price_to_zero'], 20);

        // Hook to disable quantity modification for gift items
        add_action('woocommerce_cart_item_quantity', [self::class, 'disable_quantity_for_gift'], 10, 2);

        // Hook to remove all gift products if there are no regular items in the cart
        add_action('woocommerce_cart_updated', [self::class, 'remove_gift_if_no_regular_items']);

        // Hook to apply gift changes during the checkout process
        add_action('woocommerce_checkout_create_order_line_item', [self::class, 'apply_gift_during_checkout'], 10, 4);

        // Filter to disable the remove link for gift products
        add_filter('woocommerce_cart_item_remove_link', [self::class, 'disable_remove_link_for_gift'], 10, 2);
    }



    /**
     * Apply or validate gift products in the cart.
     *
     * @param WC_Cart $cart The WooCommerce cart object.
     */
    public static function validate_and_apply_gift_products($cart) {
        if (is_admin() || !is_user_logged_in()) {
            return;
        }
    
        static $is_processing = false;
    
        if ($is_processing) {
            return;
        }
        $is_processing = true;
    
        $user_id = get_current_user_id();
        $gift_product_ids = SettingsConfig::get('hw_loyalty_gift_products', []);
        self::remove_out_of_stock_gift_products($cart);
    
        if (!LoyaltyDiscount::is_loyalty_member($user_id) || empty($gift_product_ids)) {
            self::remove_all_gift_products($cart);
            $is_processing = false;
            return;
        }
    
        if (!self::has_regular_items($cart)) {
            self::remove_all_gift_products($cart);
            $is_processing = false;
            return;
        }
    
        foreach ($gift_product_ids as $product_id) {
            if (!self::is_product_in_stock($product_id)) {
                continue; 
            }
        
            $default_quantity = apply_filters('hw_loyalty_global_gift_quantity', 1, $product_id);
            $quantity = apply_filters('hw_loyalty_gift_quantity', $default_quantity, $product_id);
        
            $product = wc_get_product($product_id);
            $stock_quantity = $product->get_stock_quantity();
        
            if ($stock_quantity !== null && $quantity > $stock_quantity) {
                $quantity = $stock_quantity; 
            }
        
            if (!self::is_product_in_cart($product_id)) {
                if ($quantity > 0) {
                    $cart->add_to_cart($product_id, $quantity, '', '', ['is_free_gift' => true]);
                }
            } else {
                foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
                    if ($cart_item['product_id'] == $product_id && isset($cart_item['is_free_gift']) && $cart_item['is_free_gift'] === true) {
                        if ($cart_item['quantity'] != $quantity) {
                            $cart->set_quantity($cart_item_key, $quantity);
                        }
                        break;
                    }
                }
            }
        }
        
    
        $is_processing = false; 
    }


     /**
     * Checks if a product or variation is in stock.
     *
     * @param int $product_id The product ID.
     * @return bool True if the product is in stock, false otherwise.
     */
    public static function is_product_in_stock($product_id) {
        $product = wc_get_product($product_id);

        if (!$product) {
            return false;
        }

        if ($product->is_type('variation')) {
            return $product->is_in_stock();
        }

        return $product->is_in_stock();
    }

    public static function remove_out_of_stock_gift_products($cart) {
        $removed_products = []; 
    
        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            if (isset($cart_item['is_free_gift']) && $cart_item['is_free_gift'] === true) {
                $product_id = $cart_item['product_id'];
                $variation_id = $cart_item['variation_id'] ?? 0;
    
                if (!self::is_product_or_variation_valid($product_id, $variation_id)) {
                    $removed_products[] = $cart_item['data']->get_name();
                    $cart->remove_cart_item($cart_item_key);
                }
            }
        }
    
        if (!empty($removed_products)) {
            $message_template = SettingsConfig::get(
                'hw_loyalty_message_gift_removed',
                __('A free gift has been removed from your cart as it is no longer available.', 'hw-woo-loyalty')
            );
    
            $product_names = implode(', ', $removed_products);
            $message = str_replace('%product', $product_names, $message_template);
            wc_add_notice($message, 'notice');
        }
    }
    
    /**
     * Check if a product or its variation is valid (exists and is published).
     *
     * @param int $product_id The product ID.
     * @param int $variation_id The variation ID (optional).
     * @return bool True if the product or variation exists and is published, false otherwise.
     */
    private static function is_product_or_variation_valid($product_id, $variation_id = 0) {
        if ($variation_id) {
            $product = wc_get_product($variation_id);
        } else {
            $product = wc_get_product($product_id);
        }

        if (!$product) {
            return false;
        }

        if ($product->get_status() !== 'publish') {
            return false;
        }

        return $product->is_in_stock();
    }

    /**
     * Check if a product or its variation is in stock.
     *
     * @param int $product_id The product ID.
     * @param int $variation_id The variation ID (optional).
     * @return bool True if the product or variation is in stock, false otherwise.
     */
    private static function is_product_or_variation_in_stock($product_id, $variation_id = 0) {
        if ($variation_id) {
            $product = wc_get_product($variation_id);
        } else {
            $product = wc_get_product($product_id);
        }

        if (!$product) {
            return false; 
        }

        return $product->is_in_stock();
    }

     /**
     * Disable remove link for gift items.
     *
     * @param string $remove_link The remove link HTML.
     * @param string $cart_item_key The cart item key.
     * @return string Modified remove link HTML.
     */
    public static function disable_remove_link_for_gift($remove_link, $cart_item_key) {
        $cart_item = WC()->cart->get_cart()[$cart_item_key];
        if (isset($cart_item['is_free_gift']) && $cart_item['is_free_gift'] === true) {
            return ''; 
        }
        return $remove_link;
    }

     /**
     * Remove all gift products from the cart.
     *
     * @param WC_Cart $cart The WooCommerce cart object.
     */
    private static function remove_all_gift_products($cart) {
        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            if (isset($cart_item['is_free_gift']) && $cart_item['is_free_gift'] === true) {
                $cart->remove_cart_item($cart_item_key);
            }
        }
    }

    /**
     * Set the price of gift items to zero.
     *
     * @param WC_Cart $cart The WooCommerce cart object.
     */
    public static function set_gift_price_to_zero($cart) {
        $free_gift_label = SettingsConfig::get('hw_loyalty_free_gift_label', __('Free Gift', 'hw-woo-loyalty'));
        
        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            if (isset($cart_item['is_free_gift']) && $cart_item['is_free_gift'] === true) {
                $cart_item['data']->set_price(0);
                
                // Ellenőrzés: Ha már tartalmazza a címkét, ne add hozzá újra
                $product_name = $cart_item['data']->get_name();
                if (strpos($product_name, $free_gift_label) === false) {
                    $cart_item['data']->set_name($product_name . '<br><small>' . esc_html($free_gift_label) . '</small>');
                }
            }
        }
    }


    /**
     * Disable quantity modification for gift items.
     *
     * @param string $product_quantity The product quantity HTML output.
     * @param string $cart_item_key The cart item key.
     * @return string Modified product quantity HTML output.
     */
    public static function disable_quantity_for_gift($product_quantity, $cart_item_key) {
        $cart_item = WC()->cart->get_cart()[$cart_item_key];
        if (isset($cart_item['is_free_gift']) && $cart_item['is_free_gift'] === true) {
            $quantity = $cart_item['quantity'];
            return sprintf('<input type="hidden" name="cart[%s][qty]" value="%d" />%d', $cart_item_key, $quantity, $quantity);
        }
        return $product_quantity;
    }

    
    /**
     * Remove all gift products from the cart if there are no regular items left.
     */
    public static function remove_gift_if_no_regular_items() {
        $cart = WC()->cart;
        if (!self::has_regular_items($cart)) {
            self::remove_all_gift_products($cart);
        }
    }

    /**
     * Check if there are regular (non-gift) items in the cart.
     *
     * @param WC_Cart $cart The WooCommerce cart object.
     * @return bool True if there are regular items, false otherwise.
     */
    private static function has_regular_items($cart) {
        foreach ($cart->get_cart() as $cart_item) {
            if (!isset($cart_item['is_free_gift']) || $cart_item['is_free_gift'] !== true) {
                return true;
            }
        }
        return false;
    }

    /**
     * Apply free gift during checkout.
     *
     * @param WC_Order_Item_Product $item The order item.
     * @param string $cart_item_key The cart item key.
     * @param array $values Cart item values.
     * @param WC_Order $order The order object.
     */
    public static function apply_gift_during_checkout($item, $cart_item_key, $values, $order) {
        if (isset($values['is_free_gift']) && $values['is_free_gift'] === true) {
            $free_gift_label = SettingsConfig::get('hw_loyalty_free_gift_label', __('Free Gift', 'hw-woo-loyalty'));
            $item_name = $item->get_name();
            $item->set_name($item_name . ' - ' . $free_gift_label);
            
            $item->set_subtotal(0);
            $item->set_total(0);
        }
    }
    

    /**
     * Check if a specific product is already in the cart.
     *
     * @param int $product_id The product ID to check.
     * @return bool True if the product is in the cart, false otherwise.
     */
    private static function is_product_in_cart($product_id) {
        foreach (WC()->cart->get_cart() as $cart_item) {
            if (($cart_item['product_id'] == $product_id || (isset($cart_item['variation_id']) && $cart_item['variation_id'] == $product_id)) 
                && isset($cart_item['is_free_gift']) 
                && $cart_item['is_free_gift'] === true) {
                return true;
            }
        }
        return false;
    }

}

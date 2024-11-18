<?php

namespace HelloWP\HWLoyalty\App\Modules;
use HelloWP\HWLoyalty\App\Helper\SettingsConfig;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}


class SmartCodes {
    
    /**
     * Process smart codes in the provided content.
     *
     * @param string $content The content with smart codes.
     * @return string Processed content with smart codes replaced by values.
     */
    public static function process_smart_codes($content) {
        $patterns = [
            '/\{\{\s*customer\.loyality\.status\s*\|\s*status-true:\s*\'([^\']*)\'\s*\|\s*status-false:\s*\'([^\']*)\'\s*\}\}/' => [self::class, 'get_loyalty_status'],
            '/\{\{\s*discount-amount\s*\}\}/' => [self::class, 'parse_discount_amount'], 
        ];
        
        foreach ($patterns as $pattern => $callback) {
            $content = preg_replace_callback($pattern, $callback, $content);
        }
        
        return $content;
    }

    /**
     * Returns the loyalty status based on user meta with specific classes.
     * 
     * @param array $matches Regex matches, where $matches[1] is status-true text, $matches[2] is status-false text.
     * @return string
     */
    private static function get_loyalty_status($matches) {
        $status_true_text = $matches[1];
        $status_false_text = $matches[2];

        // Check the loyalty status for the current user
        $user_id = get_current_user_id();
        if (LoyaltyDiscount::is_loyalty_member($user_id)) {
            return "<span class='loyalty-status-active'>{$status_true_text}</span>";
        } else {
            return "<span class='loyalty-status-inactive'>{$status_false_text}</span>";
        }
    }

    /**
     * Parses and returns the discount amount as per the current global settings.
     *
     * @param array $matches Regex matches (unused in this case).
     * @return string Formatted discount amount with specific class.
     */
    public static function parse_discount_amount($matches) {
        // Get discount type and value from settings
        $settings = SettingsConfig::get_all_settings();
        $discount_type = $settings['discount_type'];
        $discount_value = ($discount_type === 'cart_based_percentage') ? $settings['percentage_discount'] . '%' : wc_price($settings['fixed_discount']);
        
        // Return formatted discount amount
        return "<span class='discount-amount'>$discount_value</span>";
    }
}

<?php

namespace HelloWP\HWLoyalty\App\Helper;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

    class FreeShippingManager extends \WC_Shipping_Free_Shipping
    {
        /**
         * Initialize the FreeShipping method.
         *
         * @param int $instance_id Instance ID.
         */
        public function __construct($instance_id = 0) {
            parent::__construct($instance_id);
            add_filter('woocommerce_shipping_instance_form_fields_free_shipping', [$this, 'add_loyalty_option']);
        }

        /**
         * Add the 'Loyalty Member' condition to the 'requires' field.
         *
         * @param array $fields Existing fields.
         * @return array Modified fields.
         */
        public function add_loyalty_option($fields) {
            if (isset($fields['requires'])) {
                $fields['requires']['options']['loyalty_member'] = __('Loyalty Program Member', 'hw-woo-loyalty');
            }
            return $fields;
        }
    }


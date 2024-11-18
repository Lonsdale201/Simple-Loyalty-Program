<?php

namespace HelloWP\HWLoyalty\App\Admin;
use HelloWP\HWLoyalty\App\Helper\Utility;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class AdminSettings {

    private static $instance = null;

    private function __construct() {
        if (!current_user_can('manage_options')) {
            return; 
        }
        $this->init();
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init() {
        add_filter('woocommerce_settings_tabs_array', [$this, 'add_settings_tab'], 50);
        add_action('woocommerce_settings_tabs_hw_loyalty', [$this, 'settings_tab']);
        add_action('woocommerce_update_options_hw_loyalty', [$this, 'update_settings']);
    }

    public function enqueue_scripts($hook) {
        if ($hook === 'woocommerce_page_wc-settings' && isset($_GET['tab']) && $_GET['tab'] === 'hw_loyalty') {
            wp_enqueue_script(
                'hw_loyalty_js',
                HWLoyalty_URL . 'assets/loyality.js', 
                ['jquery'], 
                null,
                true
            );
        }
    }
    

    public function add_settings_tab($settings_tabs) {
        $settings_tabs['hw_loyalty'] = __('Loyalty Program', 'hw-woo-loyalty');
        return $settings_tabs;
    }

    public function settings_tab() {
        $current_section = isset($_GET['section']) ? $_GET['section'] : 'global';
    
        echo '<h2 class="nav-tab-wrapper">';
        $this->output_section_link('global', __('Global Settings', 'hw-woo-loyalty'), $current_section);
        $this->output_section_link('cycle', __('Periodical Discount', 'hw-woo-loyalty'), $current_section);
        $this->output_section_link('permanent', __('Loyalty', 'hw-woo-loyalty'), $current_section);
        $this->output_section_link('messages', __('Messages', 'hw-woo-loyalty'), $current_section);
        $this->output_section_link('notifications', __('Notifications', 'hw-woo-loyalty'), $current_section);
        echo '</h2>';
    
        switch ($current_section) {
            case 'cycle':
                woocommerce_admin_fields($this->get_cycle_settings());
                break;
            case 'permanent':
                woocommerce_admin_fields($this->get_permanent_settings());
                break;
            case 'messages':
                woocommerce_admin_fields($this->get_messages_settings());
                break;
            case 'notifications':
                woocommerce_admin_fields($this->get_notifications_settings());
                break;
            default:
                woocommerce_admin_fields($this->get_global_settings());
                break;
        }
    }
    

    public function update_settings() {
        $current_section = isset($_GET['section']) ? $_GET['section'] : 'global';
        
        switch ($current_section) {
            case 'cycle':
                woocommerce_update_options($this->get_cycle_settings());
                break;
            case 'permanent':
                woocommerce_update_options($this->get_permanent_settings());
                break;
            case 'messages': 
                woocommerce_update_options($this->get_messages_settings());
                break;
            case 'notifications':
                woocommerce_update_options($this->get_notifications_settings());
                break;
            default:
                woocommerce_update_options($this->get_global_settings());
                break;
        }
    }
    
    
    private function get_global_settings() {
        return [
            [
                'title' => __('Global Settings', 'hw-woo-loyalty'),
                'type' => 'title',
                'desc' => __('Settings that apply to both periodic discounts and loyalty program discounts.', 'hw-woo-loyalty'),
                'id' => 'hw_loyalty_global_options'
            ],
            [
                'title' => __('Discount Name', 'hw-woo-loyalty'),
                'id' => 'hw_loyalty_discount_name',
                'type' => 'text',
                'default' => __('Loyalty Discount', 'hw-woo-loyalty'),
            ],
            [
                'title' => __('Discount Mode', 'hw-woo-loyalty'),
                'desc' => __('Choose the discount method.', 'hw-woo-loyalty'),
                'id' => 'hw_loyalty_discount_mode',
                'default' => 'cart_based', 
                'type' => 'select',
                'options' => [
                    'cart_based' => __('Cart Based', 'hw-woo-loyalty') 
                ],
                'desc_tip' => true,
                'class' => 'wc-enhanced-select',
            ],
            [
                'title' => __('Discount Amount Type', 'hw-woo-loyalty'),
                'id' => 'hw_loyalty_discount_type',
                'type' => 'radio',
                'default' => 'cart_based_percentage',
                'options' => [
                    'cart_based_percentage' => __('Cart-Based (%)', 'hw-woo-loyalty'),
                    'cart_based_fix' => __('Cart-Based (Fixed Amount)', 'hw-woo-loyalty')
                ],
            ],
            [
                'title' => __('Percentage Discount Amount', 'hw-woo-loyalty'),
                'id' => 'hw_loyalty_percentage_discount',
                'type' => 'number',
                'min' => '0',
                'max' => '100',
                'desc' => __('Discount as a percentage of the cart total.', 'hw-woo-loyalty'),
                'desc_tip' => true,
                'custom_attributes' => ['step' => '1']
            ],
            [
                'title' => __('Fixed Discount Amount', 'hw-woo-loyalty'),
                'id' => 'hw_loyalty_fixed_discount',
                'type' => 'number',
                'min' => '0',
                'desc' => __('Fixed amount to be deducted from the cart total (does not include delivery charges). Enter the amount excluding tax.', 'hw-woo-loyalty'),
                'desc_tip' => true,
                'custom_attributes' => ['step' => '1']
            ],
            [
                'type' => 'sectionend',
                'id' => 'hw_loyalty_global_options'
            ],
            [
                'title' => __('Exclude Settings', 'hw-woo-loyalty'),
                'type' => 'title',
                'desc' => __('Settings that define conditions under which the loyalty discount will not be applied.', 'hw-woo-loyalty'),
                'id' => 'hw_loyalty_global_options_exclude'
            ],
            [
                'name' => __('Exclude sale product', 'hw-woo-loyalty'),
                'desc' => __('if you enable this and there is a sale item in the cart, no discount will be given', 'hw-woo-loyalty'),
                'id' => 'hw_loyalty_sale_exlcude',
                'type' => 'checkbox',
            ],
            [
                'name' => __('Disable Coupon Usage', 'hw-woo-loyalty'),
                'desc' => __('If enabled, coupons will be disabled when loyalty discounts are active.', 'hw-woo-loyalty'),
                'id' => 'hw_loyalty_disable_coupons',
                'type' => 'checkbox',
                'default' => 'no',
            ],
            [
                'type' => 'sectionend',
                'id' => 'hw_loyalty_global_options_exclude'
            ],
        ];
    }

    private function get_cycle_settings() {
        return [
            [
                'title' => __('Periodic discount', 'hw-woo-loyalty'),
                'type' => 'title',
                'desc' => __('Periodic discount settings based on the users spending amount and spending cycle.','hw-woo-loyalty'),
                'id' => 'hw_loyalty_cycle_options'
            ],
            [
                'title'    => __('Enable Periodic discount', 'hw-woo-loyalty'),
                'desc'     => __('Enable periodic discounts. If enabled, the <b>loyalty program will not be available!</b>', 'hw-woo-loyalty'),
                'id'       => 'hw_loyalty_enable_cycle_discount',
                'type'     => 'checkbox',
                'default'  => 'no',
            ],
            
            [
                'title' => __('Target Amount', 'hw-woo-loyalty'),
                'desc' => __('Minimum spending required, combined with the spending cycle, to determine discount eligibility.', 'hw-woo-loyalty'),
                'id' => 'hw_loyalty_target_amount',
                'type' => 'number',
                'default' => 20000,
                'desc_tip' => true,
                'custom_attributes' => ['min' => '1']
            ],
            [
                'title' => __('Discount Cycle', 'hw-woo-loyalty'),
                'desc' => __('Specify the time period to be considered retrospectively for discount eligibility.', 'hw-woo-loyalty'),
                'id' => 'hw_loyalty_discount_cycle',
                'default' => '30',
                'type' => 'select',
                'options' => [
                    '30' => '30 days',
                    '60' => '60 days',
                    '90' => '90 days'
                ],
                'desc_tip' => true,
                'class' => 'wc-enhanced-select',
            ],
            [
                'type' => 'sectionend',
                'id' => 'hw_loyalty_cycle_options'
            ],
        ];
    }

    private function get_permanent_settings() {
        return [
            [
                'title' => __('Loyality program settings', 'hw-woo-loyalty'),
                'type' => 'title',
                'desc' => 'Settings for the loyalty program. The loyalty program and periodic discounts cannot function simultaneously; therefore, use only one of them.',
                'id' => 'hw_loyalty_permanent_options'
            ],
            [
                'name' => __('Enable Loyality Program', 'hw-woo-loyalty'),
                'desc' => __('if you enable this, users who meet the criteria will be enrolled in the loyalty program. The status is saved in the user data', 'hw-woo-loyalty'),
                'id' => 'hw_loyalty_permanent_program',
                'type' => 'checkbox',
            ],
            [
                'type' => 'sectionend',
                'id' => 'hw_loyalty_permanent_options'
            ],
            
            // Conditions Section
            [
                'title' => __('Conditions', 'hw-woo-loyalty'),
                'type' => 'title',
                'desc' => __('Set the conditions under which a user will qualify for the loyalty program. The discount amount is set in the global settings.', 'hw-woo-loyalty'),
                'id' => 'hw_loyalty_conditions_options'
            ],
            [
                'title' => __('Minimum Spent Amount', 'hw-woo-loyalty'),
                'desc' => __('The minimum amount a user must have spent to qualify for the permanent discount. These values are not calculated on the basis of the current count, but after the setting has been enabled. This data is also stored in the user data.', 'hw-woo-loyalty'),
                'id' => 'hw_loyalty_permanent_min_spent',
                'type' => 'number',
                'default' => 5000,
                'desc_tip' => true,
                'custom_attributes' => ['min' => '0']
            ],
            [
                'title' => __('Minimum Order Items', 'hw-woo-loyalty'),
                'desc' => __('The minimum number of items a user must have ordered to qualify. These values are not calculated on the basis of the current count, but after the setting has been enabled. This data is also stored in the user data.', 'hw-woo-loyalty'),
                'id' => 'hw_loyalty_permanent_min_order_items',
                'type' => 'number',
                'default' => 1,
                'desc_tip' => true,
                'custom_attributes' => ['min' => '0']
            ],
            [
                'title' => __('User Role Selector', 'hw-woo-loyalty'),
                'desc' => __('Select the user roles that qualify for the loyalty program. Leave empty for no specific role requirement.', 'hw-woo-loyalty'),
                'id' => 'hw_loyalty_user_roles',
                'type' => 'multiselect',
                'class' => 'wc-enhanced-select',
                'css' => 'min-width:300px;',
                'options' => ! empty( Utility::get_user_roles() ) ? Utility::get_user_roles() : ['none' => __('No roles available', 'hw-woo-loyalty')],
                'custom_attributes' => [
                    'data-placeholder' => __('Select roles...', 'hw-woo-loyalty'),
                ],
            ],
            [
                'title' => __('Condition Relation', 'hw-woo-loyalty'),
                'desc' => __('Select whether both conditions (amount and items) must be met, or just one.', 'hw-woo-loyalty'),
                'id' => 'hw_loyalty_condition_relation',
                'default' => 'AND',
                'type' => 'select',
                'options' => [
                    'AND' => 'AND',
                    'OR' => 'OR'
                ],
                'desc_tip' => true,
                'class' => 'wc-enhanced-select',
            ],
            [
                'type' => 'sectionend',
                'id' => 'hw_loyalty_conditions_options'
            ],
             // Gift Products Section
            [
                'title' => __('Gift Products', 'hw-woo-loyalty'),
                'type' => 'title',
                'desc' => __('Honor your loyal customers by automatically adding free products to their purchase. The added product(s) will appear as a 0 FT item.', 'hw-woo-loyalty'),
                'id' => 'hw_loyalty_gift_products_options'
            ],
            [
                'title' => __('Free Gift Label', 'hw-woo-loyalty'),
                'desc' => __('Text to display under free gift products in the cart and checkout pages.', 'hw-woo-loyalty'),
                'id' => 'hw_loyalty_free_gift_label',
                'type' => 'text',
                'css' => 'min-width:300px;',
                'desc_tip' => true,
                'placeholder' => __('e.g., Gift product', 'hw-woo-loyalty'),
            ],        
            [
                'title' => __('Choose Gift Products', 'hw-woo-loyalty'),
                'desc' => __('Select products to add as gifts for loyal customers.', 'hw-woo-loyalty'),
                'id' => 'hw_loyalty_gift_products',
                'type' => 'multiselect',
                'class' => 'wc-product-search',
                'css' => 'min-width:300px;',
                'options' => $this->get_gift_products(),
                'custom_attributes' => [
                    'data-placeholder' => __('Choose products', 'hw-woo-loyalty'),
                    'data-action' => 'woocommerce_json_search_products_and_variations',
                    'data-multiple' => 'true',
                    'data-allow_clear' => 'true',
                ],
            ],
            
            [
                'type' => 'sectionend',
                'id' => 'hw_loyalty_gift_products_options'
            ],

            [
                'title' => __('Inactivity', 'hw-woo-loyalty'),
                'type' => 'title',
                'desc' => __('Optionally set inactivity rules to remove users from the loyalty program.', 'hw-woo-loyalty'),
                'id' => 'hw_loyalty_inactivity_options'
            ],
            [
                'title' => __('Enable Inactivity', 'hw-woo-loyalty'),
                'desc' => __('Enable this if you want users to be removed from the loyalty program after a period of inactivity.', 'hw-woo-loyalty'),
                'id' => 'hw_loyalty_enable_inactivity',
                'type' => 'checkbox',
                'default' => 'no',
            ],
            [
                'title' => __('Inactivity Time', 'hw-woo-loyalty'),
                'desc' => __('Specify the inactivity period after which users will be removed from the loyalty program.', 'hw-woo-loyalty'),
                'id' => 'hw_loyalty_inactivity_time',
                'type' => 'select',
                'options' => array_combine(range(1, 12), array_map(function($i) {
                    return sprintf(_n('%d month', '%d months', $i, 'hw-woo-loyalty'), $i);
                }, range(1, 12))),
                'default' => '12',
                'class' => 'wc-enhanced-select',
            ],        
            [
                'title' => __('Reset User Data', 'hw-woo-loyalty'),
                'desc' => __('If enabled, user data for total spending and purchased items will also be reset when removed from the program.', 'hw-woo-loyalty'),
                'id' => 'hw_loyalty_reset_user_data',
                'type' => 'checkbox',
                'default' => 'no',
            ],
            [
                'type' => 'sectionend',
                'id' => 'hw_loyalty_inactivity_options'
            ],
        ];
    }

    private function get_messages_settings() {
        return [
            [
                'title' => __('Messages', 'hw-woo-loyalty'),
                'type' => 'title',
                'desc' => __('Custom messages for specific scenarios in the loyalty program.', 'hw-woo-loyalty'),
                'id' => 'hw_loyalty_messages_options',
            ],
            [
                'title' => __('If Coupon Usage Disabled', 'hw-woo-loyalty'),
                'desc' => __('Message shown when coupon usage is disabled for a loyalty customer.', 'hw-woo-loyalty'),
                'id' => 'hw_loyalty_message_coupon_disabled',
                'type' => 'textarea',
                'css' => 'min-width:300px;min-height:100px;',
                'desc_tip' => true,
                'placeholder' => __('Enter message here...', 'hw-woo-loyalty'),
            ],
            [
                'title' => __('If Free Gift Removed from Cart', 'hw-woo-loyalty'),
                'desc' => __('Message shown when a free gift product is removed from the cart. Use the %product to show the removed product name(s). Supports HTML', 'hw-woo-loyalty'),
                'id' => 'hw_loyalty_message_gift_removed',
                'type' => 'textarea',
                'css' => 'min-width:300px;min-height:100px;',
                'desc_tip' => true,
                'placeholder' => __('Enter message here...', 'hw-woo-loyalty'),
            ],
            [
                'type' => 'sectionend',
                'id' => 'hw_loyalty_messages_options',
            ],
        ];
    }
    

    private function get_notifications_settings() {
        $settings = [
            [
                'title' => __('Notifications', 'hw-woo-loyalty'),
                'type' => 'title',
                'desc' => 'Settings for displaying notification messages related to the loyalty program.',
                'id' => 'hw_loyalty_notifications_options'
            ],
            [
                'title' => __('Enable Notifications', 'hw-woo-loyalty'),
                'desc' => __('Enable this to display notification messages on the cart and checkout pages.', 'hw-woo-loyalty'),
                'id' => 'hw_loyalty_enable_notifications',
                'type' => 'checkbox',
                'default' => 'no'
            ],
            [
                'title' => __('Loyalty Program Message', 'hw-woo-loyalty'),
                'desc' => __('Show a message if the user is a loyalty customer or if the user has a periodic discount in the cart and checkout pages. Support HTML and shortcodes', 'hw-woo-loyalty'),
                'id' => 'hw_loyalty_notification_message',
                'type' => 'textarea',
                'css' => 'min-width:300px;min-height:100px;',
                'desc_tip' => true,
                'placeholder' => __('Enter message here...', 'hw-woo-loyalty'),
            ],
            [
                'title' => __('Logged Out User Message', 'hw-woo-loyalty'),
                'desc' => __('Message displayed on the cart page to encourage non-logged-in users. Supports HTML and shortcodes.', 'hw-woo-loyalty'),
                'id' => 'hw_loyalty_notification_logged_out',
                'type' => 'textarea',
                'css' => 'min-width:300px;min-height:100px;',
                'desc_tip' => true,
                'placeholder' => __('Enter incentive message here...', 'hw-woo-loyalty'),
            ],
            [
                'type' => 'sectionend',
                'id' => 'hw_loyalty_notifications_options'
            ],
            [
                'title' => __('Myaccount Page', 'hw-woo-loyalty'),
                'type' => 'title',
                'desc' => __('Create a custom menu item on the My Account page where you can display details about the loyalty program, terms, and conditions. After created the menu, please <b>refresh the permalink settings</b>', 'hw-woo-loyalty'),
                'id' => 'hw_loyalty_myaccount_options'
            ],
            [
                'title' => __('Enable Myaccount Menu', 'hw-woo-loyalty'),
                'desc' => __('Enable this to add a new menu item to the My Account page for loyalty program information.', 'hw-woo-loyalty'),
                'id' => 'hw_loyalty_enable_myaccount_menu',
                'type' => 'checkbox',
                'default' => 'no'
            ],
            [
                'title' => __('Menu Label', 'hw-woo-loyalty'),
                'desc' => __('Enter the name for the loyalty menu item in the My Account page.', 'hw-woo-loyalty'),
                'id' => 'hw_loyalty_myaccount_menu_label',
                'type' => 'text',
                'css' => 'min-width:300px;',
                'desc_tip' => true,
                'placeholder' => __('Loyalty Program', 'hw-woo-loyalty'),
            ],
            [
                'title' => __('Menu Content', 'hw-woo-loyalty'),
                'desc' => __('Content displayed under the loyalty menu item. Supports HTML, shortcodes. Use the smart codes, <code>{{ discount-amount }}</code>, or <code>{{ customer.loyality.status | status-true: \'Active\' | status-false: \'Not active\' }}</code>', 'hw-woo-loyalty'),
                'id' => 'hw_loyalty_myaccount_menu_content',
                'type' => 'textarea',
                'css' => 'min-width:300px;min-height:150px;',
                'desc_tip' => false,
                'placeholder' => __('Enter loyalty program details here...', 'hw-woo-loyalty'),
            ],            
            [
                'type' => 'sectionend',
                'id' => 'hw_loyalty_myaccount_options'
            ],
        ];

        if (Utility::is_fluentcrm_active()) {
            $fluentcrm_lists = Utility::get_fluentcrm_lists();

                $settings[] = [
                    'title' => __('FluentCRM Integration', 'hw-woo-loyalty'),
                    'type' => 'title',
                    'desc' => __('Configure FluentCRM settings to automatically add loyalty program members to specific lists.', 'hw-woo-loyalty'),
                    'id' => 'hw_loyalty_fluentcrm_options',
                ];
                $settings[] = [
                    'title' => __('List Removal', 'hw-woo-loyalty'),
                    'desc' => __('Enable this option to automatically remove users from the selected FluentCRM list if they no longer qualify for the loyalty program.', 'hw-woo-loyalty'),
                    'id' => 'hw_loyalty_fluentcrm_list_removal',
                    'type' => 'checkbox',
                    'default' => 'no',
                ];
                $settings[] = [
                    'title' => __('Subscriber List', 'hw-woo-loyalty'),
                    'desc' => __('Select the FluentCRM subscriber list where users will be added automatically. Leave blank if you don’t want to add them.', 'hw-woo-loyalty'),
                    'id' => 'hw_loyalty_fluentcrm_list',
                    'type' => 'select',
                    'options' => ['' => __('None', 'hw-woo-loyalty')] + $fluentcrm_lists,
                    'default' => '', 
                    'class' => 'wc-enhanced-select', 
                    'custom_attributes' => [
                        'data-placeholder' => __('Select a list...', 'hw-woo-loyalty'),
                        'data-allow_clear' => 'true',
                    ],
                ];
                       
                $settings[] = [
                    'type' => 'sectionend',
                    'id' => 'hw_loyalty_fluentcrm_options',
                ];
            }
            return $settings;
        }
    
    
    public function get_gift_products() {
        $product_ids = get_option('hw_loyalty_gift_products', []);
        $products = [];
        
        if (is_array($product_ids) && !empty($product_ids)) {
            foreach ($product_ids as $product_id) {
                $product = wc_get_product($product_id);
                if ($product) {
                    $products[$product_id] = $product->get_name();
                }
            }
        }
    
        return $products;
    }

    private function output_section_link($section, $label, $current_section) {
        $url = admin_url("admin.php?page=wc-settings&tab=hw_loyalty&section=$section");
        $active = ($current_section === $section) ? 'nav-tab-active' : '';
        echo "<a href='$url' class='nav-tab $active'>$label</a>";
    }
}
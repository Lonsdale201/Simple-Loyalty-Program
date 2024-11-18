<?php

namespace HelloWP\HWLoyalty\App\Helper;

use HelloWP\HWLoyalty\App\Helper\SettingsConfig;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class UserMeta {
    private static $instance = null;

    /**
     * Constructor
     * Registers actions for displaying and saving user meta fields.
     */
    private function __construct() {
        // Register actions to display the custom meta field on user profile pages
        add_action('show_user_profile', [$this, 'display_loyalty_status']);
        add_action('edit_user_profile', [$this, 'display_loyalty_status']);
        
        // Register actions to save the custom meta field data
        add_action('personal_options_update', [$this, 'save_loyalty_status']);
        add_action('edit_user_profile_update', [$this, 'save_loyalty_status']);
    }

    /**
     * Singleton instance getter
     * Ensures only one instance of the class exists.
     *
     * @return UserMeta
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Display Loyalty Program Status and additional fields on user profile
     *
     * Shows a select field for admins to set the user's loyalty status, and
     * displays additional loyalty-related information.
     *
     * @param WP_User $user The user object.
     */

    public function display_loyalty_status($user) {
        // Check if the permanent program setting is enabled
        $permanent_program_enabled = SettingsConfig::get('hw_loyalty_permanent_program', 'no') === 'yes';

        // Only display the fields if the permanent program is enabled
        if ($permanent_program_enabled) {
            // Loyalty status
            $loyalty_status = get_user_meta($user->ID, 'hw_loyalty_status', true) ?: 'false';

            // Additional loyalty program meta values
            $total_order_items = get_user_meta($user->ID, 'hw_total_order_items', true) ?: '0';
            $total_spent_amount = get_user_meta($user->ID, 'hw_total_spent_amount', true) ?: '0';
            $last_activity_date = get_user_meta($user->ID, 'hw_last_activity_date', true) ?: __('No activity', 'hw-woo-loyalty');
            
            // Loyalty joined date - display only if it exists
            $joined_date = get_user_meta($user->ID, 'hw_loyalty_joined_date', true) ?: __('Not set', 'hw-woo-loyalty');

            ?>
            <h3><?php _e('Loyalty Program Information', 'hw-woo-loyalty'); ?></h3>
            <table class="form-table">
                <tr>
                    <th><label for="hw_loyalty_status"><?php _e('Loyalty Program', 'hw-woo-loyalty'); ?></label></th>
                    <td>
                        <select name="hw_loyalty_status" id="hw_loyalty_status">
                            <option value="false" <?php selected($loyalty_status, 'false'); ?>><?php _e('No', 'hw-woo-loyalty'); ?></option>
                            <option value="true" <?php selected($loyalty_status, 'true'); ?>><?php _e('Yes', 'hw-woo-loyalty'); ?></option>
                        </select>
                        <p class="description"><?php _e('Is this user a loyalty program member?', 'hw-woo-loyalty'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label><?php _e('Total Order Items', 'hw-woo-loyalty'); ?></label></th>
                    <td>
                        <input type="number" value="<?php echo esc_attr($total_order_items); ?>" class="regular-text" readonly disabled />
                        <p class="description"><?php _e('Total number of items ordered by the user.', 'hw-woo-loyalty'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label><?php _e('Total Spent Amount', 'hw-woo-loyalty'); ?></label></th>
                    <td>
                        <input type="number" value="<?php echo esc_attr($total_spent_amount); ?>" class="regular-text" readonly disabled />
                        <p class="description"><?php _e('Total amount spent by the user.', 'hw-woo-loyalty'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label><?php _e('Last Activity Date', 'hw-woo-loyalty'); ?></label></th>
                    <td>
                        <input type="text" value="<?php echo esc_attr($last_activity_date); ?>" class="regular-text" readonly disabled />
                        <p class="description"><?php _e('Date of the user\'s last order or activity.', 'hw-woo-loyalty'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label><?php _e('Loyalty Program Joined Date', 'hw-woo-loyalty'); ?></label></th>
                    <td>
                        <input type="text" value="<?php echo esc_attr($joined_date); ?>" class="regular-text" readonly disabled />
                        <p class="description"><?php _e('The date the user joined the loyalty program.', 'hw-woo-loyalty'); ?></p>
                    </td>
                </tr>
            </table>
            <?php
        }
    }

    /**
     * Save the Loyalty Program Status for user
     *
     * Validates user permissions and then saves the loyalty status as a user meta.
     *
     * @param int $user_id The ID of the user being updated.
     */
    public function save_loyalty_status($user_id) {
        if (!current_user_can('edit_user', $user_id)) {
            return false;
        }
    
        $new_status = $_POST['hw_loyalty_status'];
        $current_status = get_user_meta($user_id, 'hw_loyalty_status', true);
    
        if ($new_status === 'true' && $current_status !== 'true') {
            update_user_meta($user_id, 'hw_loyalty_status', 'true');
            update_user_meta($user_id, 'hw_loyalty_joined_date', current_time('mysql'));
        }
        elseif ($new_status === 'false' && $current_status === 'true') {
            update_user_meta($user_id, 'hw_loyalty_status', 'false');
            delete_user_meta($user_id, 'hw_loyalty_joined_date');
        }
    }
    

}

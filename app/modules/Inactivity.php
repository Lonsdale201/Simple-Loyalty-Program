<?php
namespace HelloWP\HWLoyalty\App\Modules;

use HelloWP\HWLoyalty\App\Helper\Utility;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Inactivity {

    /**
     * Checks inactivity for all loyalty program members and removes them if necessary.
     */
    public static function check_inactivity_for_all() {
        $members = Utility::get_loyalty_members();

        error_log('[HW Loyalty] Checking inactivity for ' . count($members) . ' loyalty members.');

        $removed_count = 0;

        foreach ($members as $member) {
            $was_removed = self::process_last_activity($member->ID);
            if ($was_removed) {
                $removed_count++;
            }
        }

        error_log('[HW Loyalty] Inactivity check completed. Removed ' . $removed_count . ' users from the loyalty program.');
    }

    /**
     * Processes last activity for a specific user.
     *
     * @param int $user_id The ID of the user to check.
     * @return bool True if the user was removed, false otherwise.
     */
    private static function process_last_activity($user_id) {
        if (empty($user_id)) {
            // error_log('[HW Loyalty] Invalid or missing user ID in process_last_activity.');
            return false;
        }

        $last_activity_date = get_user_meta($user_id, 'hw_last_activity_date', true);

        if (empty($last_activity_date)) {
            // error_log('[HW Loyalty] User ID ' . $user_id . ' has no last activity date. Removing from loyalty program.');
            self::remove_user_from_loyalty($user_id);
            return true;
        }

        $time_limit_days = 30 * 6; // Példa: 6 hónap
        if (self::is_user_inactive($last_activity_date, $time_limit_days)) {
            // error_log('[HW Loyalty] User ID ' . $user_id . ' is inactive based on last activity date. Removing from loyalty program.');
            self::remove_user_from_loyalty($user_id);
            return true;
        }

        return false;
    }

    /**
     * Determines if a user is inactive based on their last activity date.
     *
     * @param string $last_activity_date The date of the user's last activity.
     * @param int $time_limit The inactivity limit in days.
     * @return bool True if the user is inactive, false otherwise.
     */
    private static function is_user_inactive($last_activity_date, $time_limit) {
        $last_action_timestamp = strtotime($last_activity_date);
        $time_threshold = strtotime("-$time_limit days");

        return $last_action_timestamp < $time_threshold;
    }

    /**
     * Removes a user from the loyalty program.
     *
     * @param int $user_id The user ID.
     */
    private static function remove_user_from_loyalty($user_id) {
        update_user_meta($user_id, 'hw_loyalty_status', 'false');
        delete_user_meta($user_id, 'hw_total_spent_amount');
        delete_user_meta($user_id, 'hw_total_order_items');
        do_action('hw_loyalty_user_removed', $user_id);
    }
}

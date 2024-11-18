<?php

namespace HelloWP\HWLoyalty\App\Helper;

use WP_User_Query;
use HelloWP\HWLoyalty\App\Modules\LoyaltyDiscount;

if (!defined('ABSPATH')) {
    exit; 
}

class Utility {

    /**
     * Get all users who are loyalty program members.
     *
     * @return array Array of user objects who are loyalty members.
     */
    public static function get_loyalty_members() {
        // Query all users with the meta key for loyalty status
        $query = new WP_User_Query([
            'meta_key'   => 'hw_loyalty_status',
            'meta_value' => 'true',
            'fields'     => 'all',
        ]);

        $users = $query->get_results();

        return array_filter($users, function($user) {
            return LoyaltyDiscount::is_loyalty_member($user->ID);
        });
    }

     /**
     * Retrieve all user roles in a human-readable format.
     *
     * @return array List of user roles with human-readable names.
     */
    public static function get_user_roles() {
        global $wp_roles;
        if ( ! isset( $wp_roles ) ) {
            $wp_roles = new \WP_Roles();
        }
        $roles = [];
        if (is_array($wp_roles->roles)) {
            foreach ($wp_roles->roles as $key => $role) {
                $roles[$key] = $role['name']; // Human-readable role names
            }
        }
        return $roles;
    }

    /**
     * Check if FluentCRM is active using a constant.
     *
     * @return bool True if FluentCRM is active, false otherwise.
     */
    public static function is_fluentcrm_active() {
        return defined('FLUENTCRM');
    }

   /**
     * Get all available FluentCRM subscriber lists using FluentCRM API.
     *
     * @return array Array of FluentCRM subscriber lists with ID as the key and name as the value.
     */
    public static function get_fluentcrm_lists() {
        // Ellenőrizzük, hogy a FluentCRM aktív-e
        if (!self::is_fluentcrm_active()) {
            error_log('[HW Loyalty Debug] FluentCRM is not active.');
            return [];
        }
    
        // Ellenőrizzük, hogy a FluentCRM 'Lists' osztály elérhető-e
        if (!class_exists('\FluentCrm\App\Models\Lists')) {
            error_log('[HW Loyalty Debug] FluentCRM Lists class does not exist.');
            return [];
        }
    
    
        try {
            // Próbáljuk lekérni a listákat
            $lists = \FluentCrm\App\Models\Lists::orderBy('title', 'ASC')->get();
    
            // Formázzuk az adatokat
            $options = [];
            foreach ($lists as $list) {
                $options[$list->id] = $list->title;
            }
            return $options;
    
        } catch (Exception $e) {
            return [];
        }
    }

}

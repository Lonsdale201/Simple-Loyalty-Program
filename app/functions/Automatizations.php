<?php

namespace HelloWP\HWLoyalty\App\Functions;

use HelloWP\HWLoyalty\App\Helper\SettingsConfig;
use HelloWP\HWLoyalty\App\Helper\Utility;

if (!defined('ABSPATH')) {
    exit; 
}

class Automatizations {

    /**
     * Initialize hooks for automatizations.
     */
    public static function init() {
        add_action('hw_loyalty_user_became_member', [self::class, 'add_user_to_fluentcrm_list']);
        add_action('hw_loyalty_user_removed_from_program', [self::class, 'remove_user_from_fluentcrm_list']);
    }

    /**
     * Add a user to the FluentCRM list when they join the loyalty program.
     *
     * @param int $user_id The ID of the user.
     */
    public static function add_user_to_fluentcrm_list($user_id) {
        // Check if FluentCRM is active
        if (!Utility::is_fluentcrm_active()) {
            return;
        }
    
        // Get the selected FluentCRM list ID from settings
        $list_id = SettingsConfig::get('hw_loyalty_fluentcrm_list', '');
    
        // Exit if no list is selected
        if (empty($list_id)) {
            return;
        }
    
        // Initialize FluentCRM Contacts API
        $contactApi = FluentCrmApi('contacts');
    
        // Get the user data
        $user = get_userdata($user_id);
        if (!$user || empty($user->user_email)) {
            return;
        }
    
        // Retrieve or create the contact
        $contactData = [
            'email'      => $user->user_email,
            'first_name' => $user->first_name,
            'last_name'  => $user->last_name,
            'status'     => 'subscribed', 
        ];
        $contact = $contactApi->createOrUpdate($contactData);
    
        // Check if the contact exists
        if (!$contact) {
            error_log('[HW Loyalty Error] Failed to create or update contact.');
            return;
        }
    
        // Check if contact is already in the list
        $contact_lists = $contact->lists->pluck('id')->toArray();
    
        if (in_array($list_id, $contact_lists)) {
            error_log("[HW Loyalty] User ID={$user_id} is already in FluentCRM list ID={$list_id}. Skipping attachment.");
            return;
        }
    
        // Attach the contact to the selected list
        try {
            $contact->attachLists([$list_id]);
            error_log("[HW Loyalty] Successfully added user ID={$user_id} to FluentCRM list ID={$list_id}");
        } catch (\Exception $e) {
            error_log('[HW Loyalty Error] Failed to attach contact to list: ' . $e->getMessage());
        }
    }

    /**
     * Remove a user from the FluentCRM list when they leave the loyalty program.
     *
     * @param int $user_id The ID of the user.
     */
    public static function remove_user_from_fluentcrm_list($user_id) {
        // Check if FluentCRM is active
        if (!Utility::is_fluentcrm_active()) {
            return;
        }

        // Check if list removal is enabled
        $list_removal_enabled = SettingsConfig::get('hw_loyalty_fluentcrm_list_removal', 'no');

        if ($list_removal_enabled !== 'yes') {
            return;
        }

        // Get the selected FluentCRM list ID from settings
        $list_id = SettingsConfig::get('hw_loyalty_fluentcrm_list', '');

        // Exit if no list is selected
        if (empty($list_id)) {
            return;
        }

        // Initialize FluentCRM Contacts API
        $contactApi = FluentCrmApi('contacts');

        // Get the user data
        $user = get_userdata($user_id);
        if (!$user || empty($user->user_email)) {
            return;
        }

        // Get the contact by email
        $contact = $contactApi->getContact($user->user_email);

        if (!$contact) {
            error_log('[HW Loyalty Error] Contact not found in FluentCRM.');
            return;
        }

        // Detach the contact from the selected list
        try {
            $contact->detachLists([$list_id]);
            error_log("[HW Loyalty] Successfully removed user ID={$user_id} from FluentCRM list ID={$list_id}");
        } catch (\Exception $e) {
            error_log('[HW Loyalty Error] Failed to detach contact from list: ' . $e->getMessage());
        }
    }
}

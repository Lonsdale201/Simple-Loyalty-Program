<?php
namespace HelloWP\HWLoyalty\App\Helper;

use HelloWP\HWLoyalty\App\Modules\Inactivity;
use HelloWP\HWLoyalty\App\Helper\SettingsConfig;

if (!defined('ABSPATH')) {
    exit;
}

class CronHandler {

    /**
     * Initializes the cron job and hooks.
     */
    public static function init() {
        add_action('hw_loyalty_check_inactivity', [self::class, 'handle_cron_jobs']);
        $permanent_program = \HelloWP\HWLoyalty\App\Helper\SettingsConfig::get('hw_loyalty_permanent_program', 'no');
        $enable_inactivity = \HelloWP\HWLoyalty\App\Helper\SettingsConfig::get('hw_loyalty_enable_inactivity', 'no');
    
        if ($permanent_program === 'yes' && $enable_inactivity === 'yes') {
            self::ensure_cron_scheduled();
        } else {
            self::clear_cron_event();
        }
    }

    /**
     * Schedules the daily cron job if not already scheduled.
     */
    public static function schedule_cron_event() {
        $timestamp = wp_next_scheduled('hw_loyalty_check_inactivity');
        if ($timestamp === false) {
            $start_time = strtotime('tomorrow midnight');
            if ($start_time <= time()) {
                $start_time = time() + 60; 
            }
            wp_schedule_event($start_time, 'daily', 'hw_loyalty_check_inactivity');
            error_log('[HW Loyalty] Cron event scheduled for: ' . gmdate('Y-m-d H:i:s', $start_time));
        } else {
            error_log('[HW Loyalty] Cron event already scheduled.');
        }
    }
    

    /**
     * Ensures the cron job is scheduled.
     */
    private static function ensure_cron_scheduled() {
        $permanent_program = \HelloWP\HWLoyalty\App\Helper\SettingsConfig::get('hw_loyalty_permanent_program', 'no');
        $enable_inactivity = \HelloWP\HWLoyalty\App\Helper\SettingsConfig::get('hw_loyalty_enable_inactivity', 'no');
    
        if ($permanent_program === 'yes' && $enable_inactivity === 'yes' && !wp_next_scheduled('hw_loyalty_check_inactivity')) {
            self::schedule_cron_event();
        } else {
            // error_log('[HW Loyalty] Cron event already scheduled or conditions not met.');
        }
    }

    /**
     * Clears the cron job during plugin deactivation.
     */
    public static function clear_cron_event() {
        $timestamp = wp_next_scheduled('hw_loyalty_check_inactivity');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'hw_loyalty_check_inactivity');
            error_log('[HW Loyalty] Cron event cleared.');
        }
    }

    /**
     * Executes all cron jobs tied to the 'hw_loyalty_check_inactivity' action.
     */
    public static function handle_cron_jobs() {
        error_log('[HW Loyalty] Starting cron job: hw_loyalty_check_inactivity');

        try {
            self::execute_inactivity_check();
        } catch (\Exception $e) {
            error_log('[HW Loyalty] Error during cron job: ' . $e->getMessage());
        }
    }

    /**
     * Executes the inactivity check.
     */
    private static function execute_inactivity_check() {
        Inactivity::check_inactivity_for_all();
        error_log('[HW Loyalty] Inactivity check completed successfully.');
    }
}

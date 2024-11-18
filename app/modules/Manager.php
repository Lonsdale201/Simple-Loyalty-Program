<?php

namespace HelloWP\HWLoyalty\App\Modules;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

use HelloWP\HWLoyalty\App\Helper\SettingsConfig;
use HelloWP\HWLoyalty\App\Helper\DiscountHandler;
use HelloWP\HWLoyalty\App\Helper\UserMeta;
use HelloWP\HWLoyalty\App\Modules\UserWCData;
use HelloWP\HWLoyalty\App\Modules\LoyaltyDiscount;
use HelloWP\HWLoyalty\App\Helper\CronHandler;
use HelloWP\HWLoyalty\App\Functions\Automatizations;

/**
 * Class Manager
 * Manages the initialization of discount modules based on settings.
 */
class Manager {
    /**
     * Initializes the appropriate discount modules based on the settings configuration.
     */
    public static function init() {
        // Initialize the CycleDiscount module if cycle-based discounts are enabled.
        if (SettingsConfig::get('enable_cycle_discount') === 'yes') {
            CycleDiscount::get_instance();
        }

        // Initialize the UserMeta module if the permanent program is enabled.
        if (SettingsConfig::get('hw_loyalty_permanent_program') === 'yes') {
            UserMeta::get_instance();
            LoyaltyDiscount::init(); 
            FreeGift::init();
            Automatizations::init();
        }

        // Initialize the inactivity system only if both loyalty and inactivity are enabled.
        self::initialize_inactivity_system();

        // Initialize Notifications if notifications are enabled.
        if (SettingsConfig::get('hw_loyalty_enable_notifications') === 'yes') {
            Notifications::get_instance();
        }

        // Initialize DiscountHandler to apply the actual discounts if any discounts are enabled.
        DiscountHandler::get_instance();

        // Initialize UserWCData to track user activity and purchase data.
        UserWCData::init();
    }

    /**
     * Initializes the inactivity system (cron jobs and related functionality).
     */
    private static function initialize_inactivity_system() {
        $permanent_program = SettingsConfig::get('hw_loyalty_permanent_program', 'no');
        $enable_inactivity = SettingsConfig::get('hw_loyalty_enable_inactivity', 'no');
    
        if ($permanent_program === 'yes' && $enable_inactivity === 'yes') {
            CronHandler::init();
        }
    }
    
    
}

<?php
namespace HelloWP\HWLoyalty\App\Helper;

class Hooks {
    /**
     * Initialize global hooks.
     */
    public static function init() {
        add_action('update_user_meta', [self::class, 'watch_loyalty_meta_update'], 10, 4);
    }

    /**
     * Watch for updates to 'hw_loyalty_status' meta and trigger actions if changed.
     */
    public static function watch_loyalty_meta_update($meta_id, $user_id, $meta_key, $meta_value) {
        if ($meta_key === 'hw_loyalty_status') {
            if ($meta_value === 'true') {
                // Trigger action when user becomes a loyalty member
                do_action('hw_loyalty_user_became_member', $user_id);
            } elseif ($meta_value === 'false') {
                // Trigger action when user is removed from the loyalty program
                do_action('hw_loyalty_user_removed_from_program', $user_id);
            }
        }
    }
}

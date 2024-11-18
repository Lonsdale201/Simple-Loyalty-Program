<?php

if (!defined('ABSPATH')) {
    exit; 
}

?>
<div class="hw-loyalty-notification">
    <span class="hw-loyalty-notification-text"><?php echo wp_kses_post($message); ?></span>
</div>

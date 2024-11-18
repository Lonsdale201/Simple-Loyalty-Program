<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

?>
<div class="hw-pilla-notification">
    <span class="hw-pilla-notification-text"><?php echo wp_kses_post($message); ?></span>
</div>

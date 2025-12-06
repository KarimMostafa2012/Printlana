<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * @description This file is used to render the Apple Pay form in the checkout page.
 */
?>
<div>
    <p><?php echo esc_html($this->get_description()); ?></p>
    <?php wp_nonce_field('moyasar-form', 'moyasar-ap-nonce-field'); ?>
</div>

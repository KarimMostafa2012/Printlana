<?php
 /** @var string $data_output */
 /** @var bool $show_inner */
 ?>
<div class="wapf--inner">
    <?php if( $show_inner !== 'grand' ) { ?>
    <div>
        <span><?php _e('Product total','sw-wapf'); ?></span>
        <span class="wapf-total wapf-product-total price amount"></span>
    </div>
    <div>
        <span><?php _e('Options total','sw-wapf'); ?></span>
        <span class="wapf-total wapf-options-total price amount"></span>
    </div>
    <?php } ?>
    <div>
        <span class="price-heading"><?php _e('Total','sw-wapf'); ?></span>
        <span class="wapf-total wapf-grand-total price amount"></span>
    </div>
</div>
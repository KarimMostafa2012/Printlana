<?php

require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/pisol.class.form.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-emails.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/review.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/Pro_Warning.php';

require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/menu.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/basic.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/order-status.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/order.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/plugins.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/reorder.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/wallet-refund.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-dependency.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-analytics.php';


require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/cancel-request.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/detect-order-status-change.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/order-detail-link-email.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/reorder.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-add-product-to-cart.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-email-control.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/auto-cancel-refund/class-walet-refund.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/auto-cancel-refund/class-auto-cancel-order.php';
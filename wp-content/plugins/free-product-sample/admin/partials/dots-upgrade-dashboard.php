<?php
/**
 * Handles free plugin user dashboard
 * 
 * @package DSFPS_Free_Product_Sample_Pro
 * @since   1.2.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get plugin header
require_once( plugin_dir_path( __FILE__ ) . 'header/plugin-header.php' );

// Get product details from Freemius via API
$annual_plugin_price = '';
$monthly_plugin_price = '';
$plugin_details = array(
    'product_id' => 45263,
);

$api_url = add_query_arg(wp_rand(), '', DSFPS_STORE_URL . 'wp-json/dotstore-product-fs-data/v2/dotstore-product-fs-data');
$final_api_url = add_query_arg($plugin_details, $api_url);

if ( function_exists( 'vip_safe_wp_remote_get' ) ) {
    $api_response = vip_safe_wp_remote_get( $final_api_url, 3, 1, 20 );
} else {
    $api_response = wp_remote_get( $final_api_url ); // phpcs:ignore
}

if ( ( !is_wp_error($api_response)) && (200 === wp_remote_retrieve_response_code( $api_response ) ) ) {
	$api_response_body = wp_remote_retrieve_body($api_response);
	$plugin_pricing = json_decode( $api_response_body, true );

	if ( isset( $plugin_pricing ) && ! empty( $plugin_pricing ) ) {
		$first_element = reset( $plugin_pricing );
        if ( ! empty( $first_element['price_data'] ) ) {
            $first_price = reset( $first_element['price_data'] )['annual_price'];
        } else {
            $first_price = "0";
        }

        if( "0" !== $first_price ){
        	$annual_plugin_price = $first_price;
        	$monthly_plugin_price = round( intval( $first_price  ) / 12 );
        }
	}
}

// Set plugin key features content
$plugin_key_features = array(
    array(
        'title' => esc_html__( 'Samples for User Groups', 'free-product-sample' ),
        'description' => esc_html__( 'Offer sample products to a specific user group for your store, such as shop managers, retailers, internal staff, etc.', 'free-product-sample' ),
        'popup_image' => esc_url( DSFPS_PLUGIN_URL . 'admin/images/pro-features-img/feature-box-one-img.png' ),
        'popup_content' => array(
        	esc_html__( 'Offer samples to a specific set of users according to which section gets you the most traction.', 'free-product-sample' ),
        	esc_html__( 'You can offer sample products to everyone who visits your website or select for access by user role types, such as shop manager, customer, etc.', 'free-product-sample' ),
        ),
        'popup_examples' => array(
            esc_html__( 'Enable sample purchases at a 2% cost exclusively for shop managers.', 'free-product-sample' ),
            esc_html__( 'Offer complimentary samples of newly launched products exclusively for company staff.', 'free-product-sample' ),
        )
    ),
    array(
        'title' => esc_html__( 'Manage Sample Stock', 'free-product-sample' ),
        'description' => esc_html__( 'Easily track and update stock levels to ensure that you always have samples for your customers, which leads to better customer satisfaction.', 'free-product-sample' ),
        'popup_image' => esc_url( DSFPS_PLUGIN_URL . 'admin/images/pro-features-img/feature-box-two-img.png' ),
        'popup_content' => array(
        	esc_html__( 'Stay in control of your sample product inventory with the sample inventory management feature.', 'free-product-sample' ),
        	esc_html__( 'Easily track and update stock levels, ensuring you always have samples for your customers.', 'free-product-sample' )
        ),
        'popup_examples' => array(
            esc_html__( 'You have 100 original products, but only 30 samples are available for sale. Once the samples are sold out, the sample products will be out of stock.', 'free-product-sample' ),
            esc_html__( ' There are 50 samples available for the Red, Green, and Blue hoodies, while the remaining sample stock is limited to just 10.', 'free-product-sample' ),
        )
    ),
    array(
        'title' => esc_html__( 'Dynamic Sample Pricing', 'free-product-sample' ),
        'description' => esc_html__( 'Let customers experience sample products for free or set a nominal price to choose a regular product rate at a discounted price while still covering costs.', 'free-product-sample' ),
        'popup_image' => esc_url( DSFPS_PLUGIN_URL . 'admin/images/pro-features-img/feature-box-three-img.png' ),
        'popup_content' => array(
        	esc_html__( 'Give your customers free to experience sample products or set a nominal price.', 'free-product-sample' ),
        	esc_html__( 'You can charge a percentage of the regular product rate, allowing you to offer samples at a discounted price while still covering costs.', 'free-product-sample' ),
        ),
        'popup_examples' => array(
            esc_html__( 'Enable shop managers to purchase samples at a nominal cost of 2%.', 'free-product-sample' ),
            esc_html__( 'Introduce a 10% sample cost applicable to all guest users.', 'free-product-sample' ),
        )
    ),
    array(
        'title' => esc_html__( 'Customized Shipping & Tax', 'free-product-sample' ),
        'description' => esc_html__( 'Allow the application of a sample\'s custom shipping class and tax to manage minimal costs for sample products.', 'free-product-sample' ),
        'popup_image' => esc_url( DSFPS_PLUGIN_URL . 'admin/images/pro-features-img/feature-box-four-img.png' ),
        'popup_content' => array(
        	esc_html__( 'Tailor your sample product experience by selecting a specific shipping class to manage shipping costs for sample orders.', 'free-product-sample' ),
        	esc_html__( 'Additionally, choose a tax class to apply custom taxes for samples, ensuring accurate tax calculations for these products.', 'free-product-sample' ),
        ),
        'popup_examples' => array(
            esc_html__( 'Assign the free shipping class to all sample products.', 'free-product-sample' ),
            esc_html__( 'Implement the DHL shipping tax for all sample products.', 'free-product-sample' ),
        )
    ),
    array(
        'title' => esc_html__( 'Manage Product-Specific Samples', 'free-product-sample' ),
        'description' => esc_html__( 'Allows you to set product-specific sample settings such as sample price, stock, weight, dimensions, and more for tailored samples.', 'free-product-sample' ),
        'popup_image' => esc_url( DSFPS_PLUGIN_URL . 'admin/images/pro-features-img/feature-box-seven-img.png' ),
        'popup_content' => array(
        	esc_html__( 'Allows you to specify sample settings for individual products, including price, stock, weight, dimensions, and more.', 'free-product-sample' ),
        	esc_html__( 'This option enables you to tailor sample settings for individual products, ensuring precise control over pricing, stock, and specifications, which enhances customer satisfaction.', 'free-product-sample' )
        ),
        'popup_examples' => array(
            esc_html__( 'Set a $5.50 sample price for special products.', 'free-product-sample' ),
            esc_html__( 'Adjust sample weights and dimensions to accurately reflect the physical characteristics of each product.', 'free-product-sample' ),
        )
    ),
    array(
        'title' => esc_html__( 'Promote Samples with Sticky Button', 'free-product-sample' ),
        'description' => esc_html__( 'Highlight your sample products with an eye-catching quick link promo button on your website to drive visibility and attract customer attention.', 'free-product-sample' ),
        'popup_image' => esc_url( DSFPS_PLUGIN_URL . 'admin/images/pro-features-img/feature-box-five-img.jpeg' ),
        'popup_content' => array(
        	esc_html__( 'Highlight your sample products with an eye-catching quick link promo button on your website. Drive visibility, attract customer attention, and boost sample product engagement.', 'free-product-sample' ),
        	esc_html__( 'Make it easy for customers to discover and explore your samples, increasing the chances of conversions and product sales.', 'free-product-sample' )
        ),
        'popup_examples' => array(
            esc_html__( 'Enable the display of a promotional button on the shop page and product detail page.', 'free-product-sample' ),
        )
    ),
    array(
        'title' => esc_html__( 'Winning Order Auto Follow-Up Emails', 'free-product-sample' ),
        'description' => esc_html__( 'Turn sample customers into paying customers by following up to purchase original products for sample orders at specific times.', 'free-product-sample' ),
        'popup_image' => esc_url( DSFPS_PLUGIN_URL . 'admin/images/pro-features-img/feature-box-six-img.png' ),
        'popup_content' => array(
        	esc_html__( 'Turn sample customers into paying customers with follow-up emails. Increase engagement, promote full product purchases, and drive conversions with personalized messages.', 'free-product-sample' ),
        	esc_html__( 'Unlock the potential of your sample program and maximize your sales with strategic email marketing.', 'free-product-sample' )
        ),
        'popup_examples' => array(
            esc_html__( 'Promptly follow up on free sample orders within 3 days to encourage conversion to full product purchases.', 'free-product-sample' ),
        )
    ),
);
?>
	<div class="fps-section-left">
		<div class="dotstore-upgrade-dashboard">
			<div class="premium-benefits-section">
				<h2><?php esc_html_e( 'Upgrade to Unlock Premium Features', 'free-product-sample' ); ?></h2>
				<p><?php esc_html_e( 'Upgrade to premium to access advanced features, boost consumer satisfaction, and effectively sell samples!', 'free-product-sample' ); ?></p>
			</div>
			<div class="premium-plugin-details">
				<div class="premium-key-fetures">
					<h3><?php esc_html_e( 'Discover Our Top Key Features', 'free-product-sample' ) ?></h3>
					<ul>
						<?php 
						if ( isset( $plugin_key_features ) && ! empty( $plugin_key_features ) ) {
							foreach( $plugin_key_features as $key_feature ) {
								?>
								<li>
									<h4><?php echo esc_html( $key_feature['title'] ); ?><span class="premium-feature-popup"></span></h4>
									<p><?php echo esc_html( $key_feature['description'] ); ?></p>
									<div class="feature-explanation-popup-main">
										<div class="feature-explanation-popup-outer">
											<div class="feature-explanation-popup-inner">
												<div class="feature-explanation-popup">
													<span class="dashicons dashicons-no-alt popup-close-btn" title="<?php esc_attr_e('Close', 'free-product-sample'); ?>"></span>
													<div class="popup-body-content">
														<div class="feature-content">
															<h4><?php echo esc_html( $key_feature['title'] ); ?></h4>
															<?php 
															if ( isset( $key_feature['popup_content'] ) && ! empty( $key_feature['popup_content'] ) ) {
																foreach( $key_feature['popup_content'] as $feature_content ) {
																	?>
																	<p><?php echo esc_html( $feature_content ); ?></p>
																	<?php
																}
															}
															?>
															<ul>
																<?php 
																if ( isset( $key_feature['popup_examples'] ) && ! empty( $key_feature['popup_examples'] ) ) {
																	foreach( $key_feature['popup_examples'] as $feature_example ) {
																		?>
																		<li><?php echo esc_html( $feature_example ); ?></li>
																		<?php
																	}
																}
																?>
															</ul>
														</div>
														<div class="feature-image">
															<img src="<?php echo esc_url( $key_feature['popup_image'] ); ?>" alt="<?php echo esc_attr( $key_feature['title'] ); ?>">
														</div>
													</div>
												</div>		
											</div>
										</div>
									</div>
								</li>
								<?php
							}
						}
						?>
					</ul>
				</div>
				<div class="premium-plugin-buy">
					<div class="premium-buy-price-box">
						<div class="price-box-top">
							<div class="pricing-icon">
								<img src="<?php echo esc_url( DSFPS_PLUGIN_URL . 'admin/images/premium-upgrade-img/pricing-1.svg' ); ?>" alt="<?php esc_attr_e( 'Personal Plan', 'free-product-sample' ); ?>">
							</div>
							<h4><?php esc_html_e( 'Personal', 'free-product-sample' ) ?></h4>
						</div>
						<div class="price-box-middle">
							<?php
							if ( ! empty( $annual_plugin_price ) ) {
								?>
								<div class="monthly-price-wrap"><?php echo esc_html( '$' . $monthly_plugin_price ) ?><span class="seprater">/</span><span><?php esc_html_e( 'month', 'free-product-sample' ) ?></span></div>
								<div class="yearly-price-wrap"><?php echo sprintf( esc_html__( 'Pay $%s today. Renews in 12 months.', 'free-product-sample' ), esc_html( $annual_plugin_price ) ); ?></div>
								<?php	
							}
							?>
							<span class="for-site"><?php esc_html_e( '1 site', 'free-product-sample' ) ?></span>
							<p class="price-desc"><?php esc_html_e( 'Great for website owners with a single WooCommerce Store', 'free-product-sample' ) ?></p>
						</div>
						<div class="price-box-bottom">
							<a href="javascript:void(0);" class="upgrade-now"><?php esc_html_e( 'Get The Premium Version', 'free-product-sample' ) ?></a>
							<p class="trusted-by"><?php esc_html_e( 'Trusted by 100,000+ store owners and WP experts!', 'free-product-sample' ) ?></p>
						</div>
					</div>
					<div class="premium-satisfaction-guarantee premium-satisfaction-guarantee-2">
						<div class="money-back-img">
							<img src="<?php echo esc_url(DSFPS_PLUGIN_URL . 'admin/images/premium-upgrade-img/14-Days-Money-Back-Guarantee.png'); ?>" alt="<?php esc_attr_e('14-Day money-back guarantee', 'free-product-sample'); ?>">
						</div>
						<div class="money-back-content">
							<h2><?php esc_html_e( '14-Day Satisfaction Guarantee', 'free-product-sample' ) ?></h2>
							<p><?php esc_html_e( 'You are fully protected by our 100% Satisfaction Guarantee. If over the next 14 days you are unhappy with our plugin or have an issue that we are unable to resolve, we\'ll happily consider offering a 100% refund of your money.', 'free-product-sample' ); ?></p>
						</div>
					</div>
					<div class="plugin-customer-review">
						<h3><?php esc_html_e( 'Easy to control product samples', 'free-product-sample' ) ?></h3>
						<p>
							<?php echo wp_kses( __( 'The ability to control <strong>how many samples users can purchase</strong> is fantastic. Managing samples for multiple users is seamless, and adding specific products as <strong>samples with minimal charges is incredible</strong>.', 'free-product-sample' ), array(
					                'strong' => array(),
					            ) ); 
				            ?>
			            </p>
						<div class="review-customer">
							<div class="customer-img">
								<img src="<?php echo esc_url(DSFPS_PLUGIN_URL . 'admin/images/premium-upgrade-img/customer-profile-img.jpeg'); ?>" alt="<?php esc_attr_e('Customer Profile Image', 'free-product-sample'); ?>">
							</div>
							<div class="customer-name">
								<span><?php esc_html_e( 'Richard Berkel', 'free-product-sample' ) ?></span>
								<div class="customer-rating-bottom">
									<div class="customer-ratings">
										<span class="dashicons dashicons-star-filled"></span>
										<span class="dashicons dashicons-star-filled"></span>
										<span class="dashicons dashicons-star-filled"></span>
										<span class="dashicons dashicons-star-filled"></span>
										<span class="dashicons dashicons-star-filled"></span>
									</div>
									<div class="verified-customer">
										<span class="dashicons dashicons-yes-alt"></span>
										<?php esc_html_e( 'Verified Customer', 'free-product-sample' ) ?>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="upgrade-to-pro-faqs">
				<h2><?php esc_html_e( 'FAQs', 'free-product-sample' ); ?></h2>
				<div class="upgrade-faqs-main">
					<div class="upgrade-faqs-list">
						<div class="upgrade-faqs-header">
							<h3><?php esc_html_e( 'Do you offer support for the plugin? What’s it like?', 'free-product-sample' ); ?></h3>
						</div>
						<div class="upgrade-faqs-body">
							<p>
								<?php 
								echo sprintf(
								    esc_html__('Yes! You can read our %1$s or submit a %2$s. We are very responsive and strive to do our best to help you.', 'free-product-sample'),
								    '<a href="' . esc_url('https://docs.thedotstore.com/collection/470-product-sample') . '" target="_blank">' . esc_html__('knowledge base', 'free-product-sample') . '</a>',
								    '<a href="' . esc_url('https://www.thedotstore.com/support-ticket/') . '" target="_blank">' . esc_html__('support ticket', 'free-product-sample') . '</a>',
								);
								?>
							</p>
						</div>
					</div>
					<div class="upgrade-faqs-list">
						<div class="upgrade-faqs-header">
							<h3><?php esc_html_e( 'What payment methods do you accept?', 'free-product-sample' ); ?></h3>
						</div>
						<div class="upgrade-faqs-body">
							<p><?php esc_html_e( 'You can pay with your credit card using Stripe checkout. Or your PayPal account.', 'free-product-sample' ) ?></p>
						</div>
					</div>
					<div class="upgrade-faqs-list">
						<div class="upgrade-faqs-header">
							<h3><?php esc_html_e( 'What’s your refund policy?', 'free-product-sample' ); ?></h3>
						</div>
						<div class="upgrade-faqs-body">
							<p><?php esc_html_e( 'We have a 14-day money-back guarantee.', 'free-product-sample' ) ?></p>
						</div>
					</div>
					<div class="upgrade-faqs-list">
						<div class="upgrade-faqs-header">
							<h3><?php esc_html_e( 'I have more questions…', 'free-product-sample' ); ?></h3>
						</div>
						<div class="upgrade-faqs-body">
							<p>
							<?php 
								echo sprintf(
								    esc_html__('No problem, we’re happy to help! Please reach out at %s.', 'free-product-sample'),
								    '<a href="' . esc_url('mailto:hello@thedotstore.com') . '" target="_blank">' . esc_html('hello@thedotstore.com') . '</a>',
								);

							?>
							</p>
						</div>
					</div>
				</div>
			</div>
			<div class="upgrade-to-premium-btn">
				<a href="javascript:void(0);" target="_blank" class="upgrade-now"><?php esc_html_e( 'Get The Premium Version', 'free-product-sample' ) ?><svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="crown" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512" class="svg-inline--fa fa-crown fa-w-20 fa-3x" width="22" height="20"><path fill="#000" d="M528 448H112c-8.8 0-16 7.2-16 16v32c0 8.8 7.2 16 16 16h416c8.8 0 16-7.2 16-16v-32c0-8.8-7.2-16-16-16zm64-320c-26.5 0-48 21.5-48 48 0 7.1 1.6 13.7 4.4 19.8L476 239.2c-15.4 9.2-35.3 4-44.2-11.6L350.3 85C361 76.2 368 63 368 48c0-26.5-21.5-48-48-48s-48 21.5-48 48c0 15 7 28.2 17.7 37l-81.5 142.6c-8.9 15.6-28.9 20.8-44.2 11.6l-72.3-43.4c2.7-6 4.4-12.7 4.4-19.8 0-26.5-21.5-48-48-48S0 149.5 0 176s21.5 48 48 48c2.6 0 5.2-.4 7.7-.8L128 416h384l72.3-192.8c2.5.4 5.1.8 7.7.8 26.5 0 48-21.5 48-48s-21.5-48-48-48z" class=""></path></svg></a>
			</div>
		</div>
	</div>
	</div>
</div>
</div>
<?php 

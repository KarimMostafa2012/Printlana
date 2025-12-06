<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WCMMQ_Offer_Management {

    private $coupon;
    private $pricing_url;
    private $plugin_name;
    private $last_date;
    private $message; // supports placeholders {coupon}, {last_date}, {pricing_url}
    private $claim_text;
    private $bundle_one;
    private $bundle_two;
    private $logo;


    //Note args
    private $nonce_key = 'wcmmq-ajax-nonce';
    private $ajax_action = 'wcmmq_dismiss_offer';
    private $dismiss_option_key_prefix = 'wcmmq_offer_dismiss_';
    private $slug_cond = 'wcmmq'; // to identify plugin page in WP admin

    /**
     * Constructor
     *
     * $args keys:
     *  - coupon_code (string)
     *  - pricing_url (string) -- if you'd like coupon appended automatically, you can pass base url and it will add ?coupon=...
     *  - last_date (string) -- any strtotime()-parsable date (e.g. '15 Dec 2025')
     *  - message (string) -- HTML/text with placeholders {coupon}, {last_date}, {pricing_url}
     *  - claim_text (string) -- text for primary claim button
     *  - bundle_one (array ['label' => '', 'url' => ''])
     *  - bundle_two (array ['label' => '', 'url' => ''])
     *  - logo (string) -- logo URL
     */
    public function __construct( $args = array() ) {

        $this->coupon      = $args['coupon_code'] ?? 'BLACKFRIDAY50';
        $this->pricing_url = $args['pricing_url'] ?? 'https://wooproducttable.com/pricing/';
        $this->plugin_name = $args['plugin_name'] ?? 'Woo Product Table';
        // ensure coupon param present in pricing_url
        if ( false === strpos( $this->pricing_url, 'coupon=' ) ) {
            $sep = ( strpos( $this->pricing_url, '?' ) === false ) ? '?' : '&';
            $this->pricing_url = $this->pricing_url . $sep . 'coupon=' . rawurlencode( $this->coupon );
        }

        $this->last_date = $args['last_date'] ?? '15 Dec 2025';

        $this->message = $args['message'] ?? sprintf(
            '🔥 <strong>Black Friday Offer!</strong><br>Use Coupon: <b>%1$s</b> — Valid until %2$s. Get the deal from <a href="%3$s" target="_blank">pricing page</a>.',
            esc_html( $this->coupon ),
            esc_html( $this->last_date ),
            esc_url( $this->pricing_url )
        );

        $this->claim_text = $args['claim_text'] ?? 'Claim Offer →';

        // Static bundles (default to the two links you gave). These are overridable via $args.
        $this->bundle_one = $args['bundle_one'] ?? array(
            'label' => '3 IN 1 Bundle',
            'url'   => 'https://checkout.freemius.com/bundle/21668/plan/36135/' . '?coupon=' . rawurlencode( $this->coupon ),
        );
        $this->bundle_two = $args['bundle_two'] ?? array(
            'label' => 'All IN 1 Bundle',
            'url'   => 'https://checkout.freemius.com/bundle/21669/plan/36136/' . '?coupon=' . rawurlencode( $this->coupon ),
        );

        $this->logo = $args['logo'] ?? 'https://ps.w.org/woo-product-table/assets/icon-128x128.gif';
        
        // hooks
        // add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'admin_notices', array( $this, 'maybe_show_offer_notice' ) );
        add_action( 'wp_ajax_' . $this->ajax_action, array( $this, 'ajax_dismiss_offer' ) );

    }


    public function ajax_dismiss_offer() {

        check_ajax_referer( $this->nonce_key, 'nonce' );

        //check nonce actually

        $coupon = sanitize_text_field( $_POST['coupon'] ?? '' );
        if ( empty( $coupon ) ) {
            wp_send_json_error();
        }

        $dismiss_key = $this->dismiss_option_key_prefix . md5( $coupon );
        update_option( $dismiss_key, 1 ); // mark dismissed

        wp_send_json_success();
    }

    /**
     * Decide whether to show notice and render if needed.
     * Uses rand() logic to avoid DB writes.
     */
    public function maybe_show_offer_notice() {

        // Condition #1: If premium installed => no notice
        if ( function_exists( 'wcmmq_is_premium_installed' ) && wcmmq_is_premium_installed() ) {
            return;
        }

        $dismiss_key = $this->dismiss_option_key_prefix . md5( $this->coupon );
        if ( get_option( $dismiss_key ) ) {
            return;
        }

        $return_true = apply_filters( 'wcmmq_offer_show', true );
        if( ! $return_true ) return;

        
        $return_true = apply_filters( 'wcmmq_offer_show_all', true );
        if( ! $return_true ) return;


        // Condition #3: Offer expiry date check
        $now = time();
        $expire = strtotime( $this->last_date );
        if ( $expire === false ) {
            // invalid date string -> don't show
            return;
        }
        if ( $now > $expire ) {
            return;
        }

        // Get current request URI safe
        $current_url = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) );
        $is_plugin_page = ( strpos( $current_url, $this->slug_cond ) !== false );

        // Use rand() to decide show:
        // - plugin page: 10 loads -> ~6 shows => probability 60% => rand(1,10) <= 6
        // - other admin pages: 10 loads -> ~1 show => probability 10% => rand(1,10) == 1
        $rand = rand( 1, 10 );

        if ( $is_plugin_page ) {
            $should_show = ( $rand <= 6 );
        } else {
            $should_show = ( $rand === 1 );
        }

        if ( $should_show ) {
            $this->render_notice();
        }
    }

    /**
     * Render the notice HTML.
     * Safe-escapes are applied where appropriate.
     */
    public function render_notice() {

        $coupon_html = '<code class="wcmmq-coupon-badge">' . esc_html($this->coupon) . '</code>';

        // Replace placeholders in message
        $message_html = str_replace(
            array('{coupon}', '{last_date}', '{pricing_url}', '{plugin_name}'),
            array(
                $coupon_html,
                esc_html($this->last_date),
                esc_url($this->pricing_url),
                esc_html($this->plugin_name)
            ),
            $this->message
        );


        // prepare bundle buttons (escape)
        $b1_label = esc_html( $this->bundle_one['label'] );
        $b1_url   = esc_url( $this->bundle_one['url'] );

        $b2_label = esc_html( $this->bundle_two['label'] );
        $b2_url   = esc_url( $this->bundle_two['url'] );

        $logo = esc_url( $this->logo );

        ?>
        <div class="wcmmq-offer-notice notice is-dismissible" 
        data-action="<?php echo esc_attr( $this->ajax_action ); ?>"
        data-coupon_ajax_url="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>"
        data-nonce="<?php echo esc_attr( wp_create_nonce($this->nonce_key) ); ?>" 
        data-coupon="<?php echo esc_attr( $this->coupon ); ?>" 
        style="border-left:4px solid #e8655b;background:#fff9f8;padding:12px 15px;margin-top:10px;display:flex;align-items:center;">
            <div class="wcmmq-offer-logo" style="margin-right:12px;flex:0 0 auto;">
                <img src="<?php echo esc_url( $logo ); ?>" width="48" height="48" alt="offer logo" />
            </div>
            <div class="wcmmq-offer-content" style="flex:1;">
                <div class="wcmmq-offer-message" style="font-size:14px;color:#333;margin-bottom:8px;">
                    <?php echo $message_html; // message already escaped/constructed above ?>
                    <?php 
                    // Close link with action=close_offer&nonce=...
                    //on current page url actually
                    $close_url = add_query_arg( array(
                        'action' => $this->ajax_action,
                        'nonce'  => wp_create_nonce( $this->nonce_key ),
                    ), admin_url( 'admin-ajax.php' ) );
                    // <a href="#" class="wcmmq-offer-close" style="float:right;color:#888;text-decoration:none;font-size:16px;" title="Dismiss Offer">&times;</a>
                     ?> 
                    <!-- <a href="<?php echo esc_url( $close_url ); ?>" class="wcmmq-offer-close" style="float:right;color:#888;text-decoration:none;font-size:16px;" title="Dismiss Offer">&times;</a> -->
                    
                </div>

                <div class="wcmmq-offer-actions" style="margin-top:8px;display:flex;gap:8px;flex-wrap:wrap;">
                    <a class="wcmmq-offer-btn" href="<?php echo esc_url( $this->pricing_url ); ?>" target="_blank" style="padding:7px 12px;border-radius:4px;text-decoration:none;background:#d6453a;color:#fff;display:inline-block;"><?php echo esc_html( $this->claim_text ); ?></a>

                    <a class="wcmmq-offer-btn-secondary" href="<?php echo $b1_url; ?>" target="_blank" style="padding:7px 12px;border-radius:4px;text-decoration:none;border:1px solid #d0d0d0;background:#fff;color:#333;display:inline-block;"><?php echo $b1_label; ?></a>

                    <a class="wcmmq-offer-btn-secondary" href="<?php echo $b2_url; ?>" target="_blank" style="padding:7px 12px;border-radius:4px;text-decoration:none;border:1px solid #d0d0d0;background:#fff;color:#333;display:inline-block;"><?php echo $b2_label; ?></a>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Public helper: return true if this request is considered "plugin page"
     * (you can call this externally if needed)
     */
    public function is_plugin_page( $uri = null ) {
        $uri = $uri ?? ( $_SERVER['REQUEST_URI'] ?? '' );
        $uri = sanitize_text_field( wp_unslash( $uri ) );
        return ( strpos( $uri, 'product_table' ) !== false );
    }

}

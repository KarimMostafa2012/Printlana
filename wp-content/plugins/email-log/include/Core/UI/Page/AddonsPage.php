<?php

namespace EmailLog\Core\UI\Page;

use EmailLog\Core\UI\Component\EmailLogAddons;

defined('ABSPATH') || exit; // Exit if accessed directly.

/**
 * Addons Page.
 *
 * This page displays information about the current WordPress install that can be used in support requests.
 *
 * @since 2.3.0
 */
class AddonsPage extends BasePage
{
    const PAGE_SLUG = 'email-log-addons';

    /**
     * Capability to Manage system info.
     */
    const CAPABILITY = 'manage_email_logs';

    /**
     * SystemInfo class.
     *
     * @var EmailLogAddons
     */
    protected $system_info;

    public function load()
    {
        parent::load();


		add_action( 'admin_enqueue_scripts', array( $this, 'load_addons_assets' ) );
    }

    public function register_page()
    {
        $this->page = add_submenu_page(
            LogListPage::PAGE_SLUG,
            __('Add-ons', 'email-log'),
            __('Add-ons', 'email-log'),
            self::CAPABILITY,
            self::PAGE_SLUG,
            array($this, 'render_page')
        );

        add_action("load-{$this->page}", array($this, 'render_help_tab'));

        /**
         * Fires before loading Sytem Info page.
         *
         * @since 2.3.0
         *
         * @param string $page Page slug.
         */
        do_action('el_load_addons_page', $this->page);
    }

    /**
     * Render the page.
     */
    public function render_page()
    {
?>

        <div class="wrap">
            <h1>Email Log Add-ons</h1>

            <p>
                These add-ons provide additional functionality to Email Log plugin and are available for purchase. If your license includes the add-ons below, you will be able to install them from here with one-click. </p>


            <div class="el-addon-list">
                <div class="el-addon">
                    <h3 class="el-addon-title"> More Fields </h3>

                    <a rel="noopener" target="_blank" href="https://wpemaillog.com/addons/more-fields/?utm_campaign=Upsell&amp;utm_medium=wpadmin&amp;utm_source=addon-grid&amp;utm_content=More Fields" title="More Fields">
                        <img src="https://wpemaillog.com/wp-content/uploads/edd/2016/11/more-fields-addon.png" class="el-addon-image" alt="More Fields" title="More Fields">
                    </a>

                    <p> More Fields add-on shows additional fields in the email log page like From, CC, BCC, Reply To, Attachment etc. </p>

                    <a class="button button-primary" target="_blank" href="https://wpemaillog.com/addons/more-fields/?utm_campaign=Upsell&amp;utm_medium=wpadmin&amp;utm_source=addon-grid&amp;utm_content=More Fields">View Details</a>
                </div> <!-- .el-addon -->

                <div class="el-addon">
                    <h3 class="el-addon-title"> Resend Email </h3>

                    <a rel="noopener" target="_blank" href="https://wpemaillog.com/addons/resend-email/?utm_campaign=Upsell&amp;utm_medium=wpadmin&amp;utm_source=addon-grid&amp;utm_content=Resend Email" title="Resend Email">
                        <img src="https://wpemaillog.com/wp-content/uploads/edd/2016/11/resend-email-addon.png" class="el-addon-image" alt="Resend Email" title="Resend Email">
                    </a>

                    <p> Resend Email add-on allows you to resend the entire email directly from the email log. You can also modify the different fields before re-sending the email. </p>

                    <a class="button button-primary" target="_blank" href="https://wpemaillog.com/addons/resend-email/?utm_campaign=Upsell&amp;utm_medium=wpadmin&amp;utm_source=addon-grid&amp;utm_content=Resend Email">View Details</a>
                </div> <!-- .el-addon -->

                <div class="el-addon">
                    <h3 class="el-addon-title"> Auto Delete Logs </h3>

                    <a rel="noopener" target="_blank" href="https://wpemaillog.com/addons/auto-delete-logs/?utm_campaign=Upsell&amp;utm_medium=wpadmin&amp;utm_source=addon-grid&amp;utm_content=Auto Delete Logs" title="Auto Delete Logs">
                        <img src="https://wpemaillog.com/wp-content/uploads/edd/2017/03/delete-logs-addon.png" class="el-addon-image" alt="Auto Delete Logs" title="Auto Delete Logs">
                    </a>

                    <p> The Auto Delete Logs add-on allows you to automatically delete logs based on a schedule. </p>

                    <a class="button button-primary" target="_blank" href="https://wpemaillog.com/addons/auto-delete-logs/?utm_campaign=Upsell&amp;utm_medium=wpadmin&amp;utm_source=addon-grid&amp;utm_content=Auto Delete Logs">View Details</a>
                </div> <!-- .el-addon -->

                <div class="el-addon">
                    <h3 class="el-addon-title"> Forward Email </h3>

                    <a rel="noopener" target="_blank" href="https://wpemaillog.com/addons/forward-email/?utm_campaign=Upsell&amp;utm_medium=wpadmin&amp;utm_source=addon-grid&amp;utm_content=Forward Email" title="Forward Email">
                        <img src="https://wpemaillog.com/wp-content/uploads/edd/2016/11/forward-email-addon.png" class="el-addon-image" alt="Forward Email" title="Forward Email">
                    </a>

                    <p> Forward Email add-on allows you to send a copy of all the emails send from WordPress, to another email address </p>

                    <a class="button button-primary" target="_blank" href="https://wpemaillog.com/addons/forward-email/?utm_campaign=Upsell&amp;utm_medium=wpadmin&amp;utm_source=addon-grid&amp;utm_content=Forward Email">View Details</a>
                </div> <!-- .el-addon -->

                <div class="el-addon">
                    <h3 class="el-addon-title"> Export Logs </h3>

                    <a rel="noopener" target="_blank" href="https://wpemaillog.com/addons/export-logs/?utm_campaign=Upsell&amp;utm_medium=wpadmin&amp;utm_source=addon-grid&amp;utm_content=Export Logs" title="Export Logs">
                        <img src="https://wpemaillog.com/wp-content/uploads/edd/2017/03/export-logs-addon.png" class="el-addon-image" alt="Export Logs" title="Export Logs">
                    </a>

                    <p> Export Logs add-on allows you to export the logged email logs for further processing or record keeping. </p>

                    <a class="button button-primary" target="_blank" href="https://wpemaillog.com/addons/export-logs/?utm_campaign=Upsell&amp;utm_medium=wpadmin&amp;utm_source=addon-grid&amp;utm_content=Export Logs">View Details</a>
                </div> <!-- .el-addon -->

            </div> <!-- .el-container -->
        </div>

<?php
        $this->render_page_footer();
    }

    /**
     * Loads assets on the Log List page.
     *
     * @since 2.0.0
     *
     * @param string $hook The current admin page.
     */
    public function load_addons_assets($hook)
    {
        // Don't load assets if not Addons page.
        if ('email-log_page_email-log-addons' !== $hook) {
            return;
        }

        $email_log      = email_log();
        $plugin_dir_url = plugin_dir_url($email_log->get_plugin_file());

        wp_enqueue_style('el-addons-list-css', $plugin_dir_url . 'assets/css/admin/addon-list.css', false, $email_log->get_version());
    }
}

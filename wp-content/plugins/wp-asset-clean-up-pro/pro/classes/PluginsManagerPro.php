<?php
namespace WpAssetCleanUpPro;

use WpAssetCleanUp\Main;
use WpAssetCleanUp\Misc;
use WpAssetCleanUp\Settings;

/**
 *
 */
class PluginsManagerPro
{
    /**
     * PluginsManager constructor.
     */
    public function __construct()
    {
        // Note: The rules' update takes place in /pro/classes/UpdatePro.php
        if (Misc::getVar('get', 'page') === WPACU_PLUGIN_ID . '_plugins_manager') {
            add_action('wpacu_admin_notices', array($this, 'notices'));
        }
    }

    /**
     * @param $type
     *
     * @return mixed
     */
    public static function isPluginsManagerDisabled($type = 'front')
    {
        $currentValue = (int)(new Settings())->getOption('plugins_manager_' . $type . '_disable');

        return $currentValue === 1;
    }

    /**
     * Make sure there is a status for the rule, otherwise it's likely set to "Load it",
     * thus the rule wouldn't count
     * @param bool $checkIfPluginIsActive
     * @param bool $getRulesForAllLocations
     *
     * @return array
     */
    public static function getPluginRulesFiltered($checkIfPluginIsActive = true, $getRulesForAllLocations = false)
    {
        $pluginsWithRules = array();

        $pluginsAllDbRules = self::getAllRules($getRulesForAllLocations);

        // Are there any load exceptions / unload RegExes?
        if (! empty( $pluginsAllDbRules ) ) {
            foreach ($pluginsAllDbRules as $locationKey => $pluginsRules) {
                foreach ( $pluginsRules as $pluginPath => $pluginData ) {
                    // Only the rules for the active plugins are retrieved
                    if ( $checkIfPluginIsActive && ! Misc::isPluginActive( $pluginPath ) ) {
                        continue;
                    }

                    // 'status' refers to the Unload Status (any option that was chosen)
                    $pluginStatus = ! empty( $pluginData['status'] ) ? $pluginData['status'] : array();

                    if ( ! empty( $pluginStatus ) ) {
                        $pluginsWithRules[ $locationKey ][ $pluginPath ] = $pluginData;
                    }
                }
            }

            }

        return $pluginsWithRules;
    }

    /**
     * @param $wpacuSubPage
     *
     * @return array|mixed
     */
    public static function getPluginsContractedList($wpacuSubPage)
    {
        $optionToFetch     = WPACU_PLUGIN_ID . '_global_data';
        $globalKey         = 'plugin_row_contracted'; // Contracted or Expanded (default)

        $existingListEmpty = array($globalKey => array('front' => array(), 'dash' => array()));
        $existingListJson  = get_option($optionToFetch);

        $existingListData  = Main::instance()->existingList($existingListJson, $existingListEmpty);
        $existingList      = $existingListData['list'];

        $wpacuPluginsArea = ($wpacuSubPage === 'manage_plugins_front') ? 'front' : 'dash';

        if (isset($existingList[$globalKey][$wpacuPluginsArea])) {
            return $existingList[$globalKey][$wpacuPluginsArea];
        }

        return array();
    }

    /**
     * @param $data
     *
     * @return mixed
     */
    public static function filterPageData($data)
    {
        $data['rules'] = self::getAllRules(); // get all rules from the database (for either the frontend or dash view)

        $data['plugins_contracted_list'] = self::getPluginsContractedList($data['wpacu_sub_page']);

        $data['mu_file_missing']  = false; // default
        $data['mu_file_rel_path'] = '/' . str_replace(Misc::getWpRootDirPathBasedOnPath(WPMU_PLUGIN_DIR), '', WPMU_PLUGIN_DIR)
            . '/' . PluginPro::$muPluginFileName;

        if ( ! is_file(WPMU_PLUGIN_DIR . '/' . PluginPro::$muPluginFileName) ) {
            $data['mu_file_missing'] = true; // alert the user in the "Plugins Manager" area
        }

        $postTypes               = get_post_types( array( 'public' => true ) );
        $data['post_types_list'] = Misc::filterPostTypesList($postTypes);

        global $wp_roles;
        $data['all_users_roles'] = $wp_roles->roles;

        return $data;
    }

    /**
     * @param false $fetchAllLocations (if set to true, it will return the rules for both the frontend and the backend
     *
     * @return array
     */
    public static function getAllRules($fetchAllLocations = false)
    {
        $pluginsRulesDbListJson = get_option(WPACU_PLUGIN_ID . '_global_data');

        if ($pluginsRulesDbListJson) {
            $pluginsRulesDbList = @json_decode($pluginsRulesDbListJson, true);

            // Issues with decoding the JSON file? Return an empty list
            if (Misc::jsonLastError() !== JSON_ERROR_NONE) {
                return array();
            }

            // 1) For listing them in "Overview"
            if ($fetchAllLocations) {
                $rulesList = array();

                if ( ! empty( $pluginsRulesDbList['plugins'] )) {
                    $rulesList['plugins'] = $pluginsRulesDbList['plugins'];
                }

                if ( ! empty( $pluginsRulesDbList['plugins_dash'] )) {
                    $rulesList['plugins_dash'] = $pluginsRulesDbList['plugins_dash'];
                }

                return $rulesList;
            }

            // 2) For listing them within "Plugins Manager" -> "In Frontend View" or "In the Dashboard" when the admin is managing the rules
            $wpacuSubPage = ( isset($_GET['wpacu_sub_page']) && $_GET['wpacu_sub_page'] ) ? $_GET['wpacu_sub_page'] : 'manage_plugins_front';

            $mainGlobalKey = ($wpacuSubPage === 'manage_plugins_front') ? 'plugins' : 'plugins_dash';

            if ( ! empty( $pluginsRulesDbList[$mainGlobalKey] )) {
                return $pluginsRulesDbList[$mainGlobalKey];
            }
        }

        return array();
    }

    /**
     * @param $ddKeyName
     * @param $enabled
     * @param $postTypesList
     * @param $currentPostTypes
     * @param $pluginPath
     */
    public static function buildPostTypesListDd($ddKeyName, $enabled, $postTypesList, $currentPostTypes, $pluginPath)
    {
        $settings = new Settings();
        $inputStyle = $settings->getOption('input_style');
        $enableChosenDd = $enabled && ! empty($currentPostTypes) && $inputStyle === 'enhanced';

        $ddList = array();

        // "WooCommerce" (or any other plugins that generates custom post types) creates the "product" page
        // It doesn't make sense to unload "WooCommerce" when a "WooCommerce" product page is visited
        // as that page would not exist anyway without WooCommerce (thus, hide it from the drop-down list)
        if ($pluginPath === 'woocommerce/woocommerce.php' && array_key_exists('product', $postTypesList)) {
            unset($postTypesList['product']);
        }

        if ($pluginPath === 'easy-digital-downloads/easy-digital-downloads.php' && array_key_exists('product', $postTypesList)) {
            unset($postTypesList['download']);
        }

        foreach ($postTypesList as $postTypeKey => $postTypeValue) {
            if (in_array($postTypeKey, array('post', 'page', 'attachment'))) {
                $ddList['WordPress (default)'][$postTypeKey] = $postTypeValue;
            } else {
                $ddList['Custom Post Types (Singular pages)'][$postTypeKey] = $postTypeValue;
            }
        }
        ?>
        <select multiple="multiple"
                style="width: 100%;"
                data-placeholder="<?php esc_attr_e('Choose the post types', 'wp-asset-clean-up-pro'); ?>..."
                class="<?php if ( $enableChosenDd ) { ?>wpacu_chosen_select<?php } if ($inputStyle === 'enhanced') { echo ' wpacu_chosen_can_be_later_enabled '; } ?> wpacu_plugin_manage_via_post_type_dd wpacu_plugin_manage_<?php echo $ddKeyName; ?>"
                name="wpacu_plugins[<?php echo esc_attr($pluginPath); ?>][<?php echo $ddKeyName; ?>][values][]">
            <?php
            foreach ($ddList as $groupLabel => $groupPostTypesList) {
                echo '<optgroup label="'.esc_attr($groupLabel).'">';

                foreach ($groupPostTypesList as $postTypeKey => $postTypeValue) {
                    ?>
                    <option <?php if (in_array($postTypeKey, $currentPostTypes)) { echo 'selected="selected"'; } ?> value="<?php echo esc_attr($postTypeKey); ?>"><?php echo esc_html($postTypeValue); ?></option>
                    <?php
                }

                echo '</optgroup>';
            }
            ?>
        </select>
        <?php
    }

    /**
     * $pluginPath (the plugin from "Plugins Manager" for which the rules are shown)
     *
     * @return array
     */
    public static function generatePublicTaxonomyListForDd($pluginPath)
    {
        $taxGroupList = array(
            __('Default (built in)', 'wp-asset-clean-up') => array(
                'category_all' => __('Categories', 'wp-asset-clean-up'),
                'post_tag_all' => __('Tags', 'wp-asset-clean-up')
            )
        );

        $possibleTaxonomiesWithLink = get_taxonomies( array( 'public' => true, 'show_ui' => true, 'query_var' => true, 'rewrite' => true, 'show_in_menu' => true, '_builtin' => false ) );

        // "WooCommerce" (or any other plugins that generates custom taxonomies) creates the "product_cat" taxonomy page
        // It doesn't make sense to unload "WooCommerce" when a "WooCommerce" taxonomy page is visited
        // as that page would not exist anyway without WooCommerce (thus, hide it from the drop-down list)
        if ($pluginPath === 'woocommerce/woocommerce.php') {
            if (array_key_exists('product_cat', $possibleTaxonomiesWithLink)) {
                unset($possibleTaxonomiesWithLink['product_cat']);
            }

            if (array_key_exists('product_tag', $possibleTaxonomiesWithLink)) {
                unset($possibleTaxonomiesWithLink['product_tag']);
            }
        }

        if ($pluginPath === 'easy-digital-downloads/easy-digital-downloads.php') {
            if (array_key_exists('download_category', $possibleTaxonomiesWithLink)) {
                unset($possibleTaxonomiesWithLink['download_category']);
            }

            if (array_key_exists('download_tag', $possibleTaxonomiesWithLink)) {
                unset($possibleTaxonomiesWithLink['download_tag']);
            }
        }

        if ( array_key_exists('product_cat', $possibleTaxonomiesWithLink) &&
            array_key_exists('product_tag', $possibleTaxonomiesWithLink) &&
            Misc::isPluginActive('woocommerce/woocommerce.php') ) {
            $taxGroupList[__('WooCommerce', 'wp-asset-clean-up')] = array(
                'product_cat_all' => __('Product categories', 'wp-asset-clean-up'),
                'product_tag_all' => __('Product tags', 'wp-asset-clean-up'),
            );
            unset( $possibleTaxonomiesWithLink['product_cat'], $possibleTaxonomiesWithLink['product_tag'] );
        }

        if ( array_key_exists('download_category', $possibleTaxonomiesWithLink) &&
            array_key_exists('download_tag', $possibleTaxonomiesWithLink) &&
            Misc::isPluginActive('easy-digital-downloads/easy-digital-downloads.php') ) {
            $taxGroupList[__('Easy Digital Downloads', 'wp-asset-clean-up')] = array(
                'download_category_all' => __('Download Categories', 'wp-asset-clean-up'),
                'download_tag_all' => __('Download Tags', 'wp-asset-clean-up'),
            );
            unset( $possibleTaxonomiesWithLink['download_category'], $possibleTaxonomiesWithLink['download_tag'] );
        }

        if ( ! empty($possibleTaxonomiesWithLink) ) {
            $taxGroupList['Others'] = $possibleTaxonomiesWithLink;
        }

        return $taxGroupList;
    }

    /**
     * @param $ddKeyName
     * @param $enabled
     * @param $taxGroupList
     * @param $unloadViaTaxChosen
     * @param $pluginPath
     *
     * @return void
     */
    public static function buildTaxListDd($ddKeyName, $enabled, $taxGroupList, $unloadViaTaxChosen, $pluginPath)
    {
        $settings = new Settings();
        $inputStyle = $settings->getOption('input_style');
        $enableChosenDd = $enabled && ! empty($unloadViaTaxChosen) && $inputStyle === 'enhanced';
        ?>
        <select multiple="multiple"
                style="width: 100%;"
                data-placeholder="<?php esc_attr_e('Choose the taxonomies'); ?>..."
                class="<?php if ($enableChosenDd) { ?>wpacu_chosen_select<?php } if ($inputStyle === 'enhanced') { echo ' wpacu_chosen_can_be_later_enabled '; } ?> wpacu_plugin_manage_via_tax_dd wpacu_plugin_manage_<?php echo $ddKeyName; ?>"
                name="wpacu_plugins[<?php echo esc_attr($pluginPath); ?>][<?php echo $ddKeyName; ?>][values][]">
            <?php
            foreach ($taxGroupList as $taxGroupLabel => $taxList) {
                echo '<optgroup label="'.esc_attr($taxGroupLabel).'">';

                foreach ($taxList as $taxValue => $taxText) {
                    ?>
                    <option <?php if (in_array($taxValue, $unloadViaTaxChosen)) { echo 'selected="selected"'; } ?> value="<?php echo $taxValue; ?>"><?php echo $taxText; ?></option>
                    <?php
                }

                echo '</optgroup>';
            }
            ?>
        </select>
        <?php
    }

    /**
     * @return string[]
     */
    public static function generateArchivePageTypesList()
    {
        return array(
            'search' => 'Search',
            'author' => 'Author',
            'date'   => 'Date'
        );
    }

    /**
     * @param $ddKeyName
     * @param $enabled
     * @param $options
     * @param $ruleViaArchiveTypeChosen
     * @param $pluginPath
     *
     * @return void
     */
    public static function buildArchiveTypesListDd($ddKeyName, $enabled, $options, $ruleViaArchiveTypeChosen, $pluginPath)
    {
        $settings = new Settings();
        $inputStyle = $settings->getOption('input_style');
        $enableChosenDd = $enabled && ! empty($ruleViaArchiveTypeChosen) && $inputStyle === 'enhanced';
        ?>
        <select multiple="multiple"
                style="width: 100%;"
                data-placeholder="<?php esc_attr_e('Choose the archive types'); ?>..."
                class="<?php if ($enableChosenDd) { ?>wpacu_chosen_select<?php } if ($inputStyle === 'enhanced') { echo ' wpacu_chosen_can_be_later_enabled '; } ?> wpacu_plugin_manage_via_archive_dd wpacu_plugin_manage_<?php echo $ddKeyName; ?>"
                name="wpacu_plugins[<?php echo esc_attr($pluginPath); ?>][<?php echo $ddKeyName; ?>][values][]">
            <?php
            foreach ($options as $optionValue => $optionText) {
            ?>
                <option <?php if (in_array($optionValue, $ruleViaArchiveTypeChosen)) { echo 'selected="selected"'; } ?> value="<?php echo $optionValue; ?>"><?php echo $optionText; ?></option>
            <?php
            }
            ?>
        </select>
        <?php
    }

    /**
     * @param $ddKeyName
     * @param $enabled
     * @param $allRoles
     * @param $unloadViaLoggedInUserChosenRoles
     * @param $pluginPath
     *
     * @return void
     */
    public static function buildUserRolesDd($ddKeyName, $enabled, $allRoles, $unloadViaLoggedInUserChosenRoles, $pluginPath)
    {
        $settings = new Settings();
        $inputStyle = $settings->getOption('input_style');
        $enableChosenDd = $enabled && ! empty($unloadViaLoggedInUserChosenRoles) && $inputStyle === 'enhanced';
        ?>
        <select multiple="multiple"
                style="width: 100%;"
                data-placeholder="<?php esc_attr_e('Choose the user roles'); ?>..."
                class="<?php if ($enableChosenDd) { ?>wpacu_chosen_select<?php } if ($inputStyle === 'enhanced') { echo ' wpacu_chosen_can_be_later_enabled '; } ?> wpacu_plugin_manage_logged_in_via_role_dd wpacu_plugin_manage_<?php echo $ddKeyName; ?>"
                name="wpacu_plugins[<?php echo esc_attr($pluginPath); ?>][<?php echo $ddKeyName; ?>][values][]">
            <?php
            foreach ($allRoles as $roleKey => $roleValues) {
                ?>
                <option <?php if (in_array($roleKey, $unloadViaLoggedInUserChosenRoles)) { echo 'selected="selected"'; } ?> value="<?php echo $roleKey; ?>"><?php echo translate_user_role($roleValues['name']); ?> (<?php echo $roleKey; ?>)</option>
                <?php
            }
            ?>
        </select>
        <?php
    }

    /**
     *
     */
    public function notices()
    {
        // After "Save changes" is clicked
        if (Misc::getVar('get', 'page') === WPACU_PLUGIN_ID.'_plugins_manager' &&
            Misc::getVar('get', 'wpacu_sub_page') &&
            get_transient('wpacu_plugins_manager_updated')) {
            delete_transient('wpacu_plugins_manager_updated');

            $appliedForText = '';
            if ( isset($_GET['wpacu_sub_page']) ) {
                if ( $_GET['wpacu_sub_page'] === 'manage_plugins_front' ) {
                    $appliedForText = 'the frontend view';
                } elseif ( $_GET['wpacu_sub_page'] === 'manage_plugins_dash' ) {
                    $appliedForText = 'the Dashboard view (/wp-admin/)';
                }
            }

            if ($appliedForText !== '') {
                ?>
                <div style="margin-bottom: 15px; margin-left: 0; width: 90%;" class="notice notice-success is-dismissible">
                    <p><span class="dashicons dashicons-yes"></span> <?php echo sprintf(__('The plugins\' rules were successfully applied within %s.', 'wp-asset-clean-up-pro'), $appliedForText); ?></p>
                </div>
                <?php
            }
        }
    }
}

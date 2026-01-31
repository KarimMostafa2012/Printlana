<?php declare(strict_types=1);
namespace Aikinomi\Wbsng;

use Aikinomi\Wbsng\Model\Order\Item;
use WP_Term;


class Client
{
    public function __construct(ShippingMethod $method)
    {
        $this->method = $method;
    }

    public function html(): void
    {
        $paths = Plugin::instance()->meta->paths;
        Client\hide(!$this->method->instance_id);
        Client\root($paths->serverAssetUrl('client.css'));
        Client\review();
    }

    public function enqueueAssets(): void
    {
        // @font-face doesn't work inside shadow dom in Chrome, so define it globally
        wp_enqueue_style('gzp-wbsng-roboto', 'https://fonts.googleapis.com/css?family=Roboto:300,400,500,700&display=block');

        // the header save button uses the wordpress react component library
        wp_enqueue_style('wp-components');

        $paths = Plugin::instance()->meta->paths;
        $clientjsid = 'gzp-wbsng-client-js';
        wp_enqueue_script($clientjsid, $paths->serverAssetUrl('client.js'));
        
        $currencyPlacement = explode('_', get_option('woocommerce_currency_pos'));

        $globalMethods = null;
        if (!$this->method->instance_id && class_exists('Wbs\Api') && class_exists('Wbs\Plugin')) {
            $globalMethods = [
                'state' => get_option('wbs_global_methods') ?: 'only-wbsng',
                'endpoint' => \Wbs\Api::$globalSwitch->url(),
                'wbsRedirectUrl' => \Wbs\Plugin::shippingUrl(\Wbs\Plugin::ID),
            ];
        }

        $config = $this->method->configData();

        wp_localize_script($clientjsid, 'gzp_wbsng_js_data', [

            'config' => $config,

            'endpoints' => [
                'config' => Api::configEndpointUrl($this->method->instance_id),
            ],

            'nextUrlHeader' => Api::NextUrlHeader,

            'units' => [
                'weight' => [
                    'symbol' => get_option('woocommerce_weight_unit'),
                ],
                'price' => [
                    'symbol' => html_entity_decode(get_woocommerce_currency_symbol()),
                    'right' => $currencyPlacement[0] === 'right',
                    'withSpace' => isset($currencyPlacement[1]) && $currencyPlacement[1] === 'space',
                    'decimals' => wc_get_price_decimals(),
                ],
                'volume' => [
                    'symbol' => get_option('woocommerce_dimension_unit').'³',
                ],
            ],

            'dicts' => [
                'classes' => self::getAllShippingClasses(),
                'locations' => self::getAllLocations(),
            ],

            'globalMethods' => $globalMethods,

            'goToShippingZones' => $this->showGlobalMethodStub()
                ? admin_url('admin.php?page=wc-settings&tab=shipping')
                : null,
        ]);
    }

    private function showGlobalMethodStub(): bool
    {
        if ($this->method->instance_id || array_key_exists('wbs_global', $_GET)) return false;

        try { $doc = $this->method->config(); }
        catch (\Throwable $e) { return false; }

        $methods = $doc->methods;
        if (count($methods) > 1) return false;
        if (count($methods) === 1 && !reset($methods)->empty()) return false;

        return true;
    }

    private static function getAllLocations(): array
    {
        $locations = [];

        foreach (WC()->countries->get_shipping_countries() as $cc => $countryName) {

            $country = [
                'id' => (string)$cc,
                'label' => html_entity_decode($countryName),
            ];

            if ($states = WC()->countries->get_states($cc)) {
                foreach ($states as $sc => $state) {
                    $country['nodes'][] = [
                        'id' => $sc,
                        'label' => html_entity_decode($state),
                    ];
                }
            }

            $locations[] = $country;
        }

        return $locations;
    }

    private static function getAllShippingClasses(): array
    {
        $shclasses = [];

        $shclasses[] = [
            'id' => Item::NONE_VIRTUAL_TERM_ID,
            'label' => __('No shipping class', 'woocommerce'),
        ];

        /** @var WP_Term $term */
        foreach (WC()->shipping()->get_shipping_classes() as $term) {
            $shclasses[] = [
                'id' => (int)$term->term_id,
                'label' => (string)$term->name,
            ];
        }

        return $shclasses;
    }

    private $inited = false;
    private $method;
}


namespace Aikinomi\Wbsng\Client;

use Aikinomi\Wbsng\Api;
use Aikinomi\Wbsng\ReviewEndpoint;


function hide(bool $globalInstance): void {

    $hide = "#mainform p.submit";
    if ($globalInstance) { // global instance
        $hide .= ",#mainform>h2";
    }
    
    ?>
        <style>
            <?= $hide ?> {display:none}
            .woocommerce-recommended-shipping-extensions {display: none !important}
        </style>
    <?php
}

function root(string $styles): void {
    ?>
        <div id="gzp_wbsng_root">
            <link rel="stylesheet" href="<?= htmlspecialchars($styles) ?>">
        </div>
        <script>document.getElementById("gzp_wbsng_root").attachShadow({mode: "open"})</script>
    <?php
}

function review(): void {

    if (!ReviewEndpoint::active()) {
        ?><style>#wpfooter{display:none}</style><?php
        return;
    }

    ?>
        <style>#wpfooter { bottom: -2em; }</style>
        <template>
            <div style="font-size: 110%">
                If you like <b>Weight Based Shipping</b> please leave us a
                <a
                    href="https://wordpress.org/support/plugin/weight-based-shipping-for-woocommerce/reviews/?rate=5#new-post"
                    target="_blank"
                    aria-label="five star"
                >&#9733;&#9733;&#9733;&#9733;&#9733;</a>
                rating. A huge thanks in advance!
            </div>
        </template>
        <script>
            /** @type {HTMLTemplateElement} */
            const tmpl = document.currentScript.previousElementSibling
            document.addEventListener('DOMContentLoaded', function() {
                /** @type {HTMLElement} */
                const footer = document.querySelector('#wpfooter')
                footer.replaceChildren(tmpl.content)
                footer.querySelector('a').addEventListener('click', function() {
                    fetch(<?= json_encode(Api::reviewEndpointUrl(), JSON_HEX_TAG) ?>, {method: 'POST'})
                    footer.innerText = 'Thank you for your review!'
                })
            })
        </script>
    <?php
}
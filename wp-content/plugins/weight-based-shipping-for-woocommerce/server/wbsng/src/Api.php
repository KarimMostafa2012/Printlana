<?php declare(strict_types=1);
namespace Aikinomi\Wbsng;

use WbsngVendors\Dgm\WpAjaxApi\Endpoint;
use WbsngVendors\Dgm\WpAjaxApi\RegisteredEndpoint;
use WbsngVendors\Dgm\WpAjaxApi\Request;
use WbsngVendors\Dgm\WpAjaxApi\Response;
use WbsngVendors\Dgm\WpAjaxApi\WpAjaxApi;


class Api
{
    public const NextUrlHeader = WpAjaxApi::NextUrlHeader;

    public static function init(): void
    {
        $wpAjaxApi = new WpAjaxApi();

        self::$config = $wpAjaxApi->register(new ConfigEndpoint());

        if (ReviewEndpoint::active()) {
            self::$reviewed = $wpAjaxApi->register(new ReviewEndpoint());
        }

        $wpAjaxApi->install();
    }

    public static function configEndpointUrl(?int $instanceId = null): string
    {
        return self::$config->url([ConfigEndpoint::InstanceIdArg => $instanceId]);
    }

    public static function reviewEndpointUrl(): string
    {
        return self::$reviewed ? self::$reviewed->url() : '';
    }

    /**
     * @var RegisteredEndpoint
     */
    private static $config;

    /**
     * @var ?RegisteredEndpoint
     */
    private static $reviewed;
}


/**
 * @internal
 */
class ConfigEndpoint extends Endpoint
{
    public const InstanceIdArg = 'instance_id';

    public $permissions = ['manage_woocommerce'];
    public $urlParams = [self::InstanceIdArg];


    public function get(Request $request): Response
    {
        $instanceId = @$request->query[self::InstanceIdArg];
        $method = new ShippingMethod($instanceId);
        return Response::json($method->configData());
    }

    public function post(Request $request): Response
    {
        $config = $request->json();

        $instanceId = @$request->query[self::InstanceIdArg];

        $method = new ShippingMethod($instanceId);

        try {
            $method->updateConfigData($config);
        }
        catch (ConfigError $e) {
            return Response::text($e->getMessage(), Response::BadRequest);
        }

        return Response::empty();
    }
}

class ReviewEndpoint extends Endpoint
{
    public $permissions = [];
    
    public static function active(): bool
    {
        return !get_option('wbsng_reviewed');
    }
    
    public function post(Request $request): Response
    {
        update_option('wbsng_reviewed', true);
        return Response::empty();
    }
}
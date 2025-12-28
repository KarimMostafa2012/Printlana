<?php declare(strict_types=1);

namespace Aikinomi\Wbsng;

use Aikinomi\Wbsng\Common\Decimal;
use WbsngVendors\Aikinomi\Multicurrency\Numbers;
use WbsngVendors\Aikinomi\Multicurrency\Service;


class Multicurrency {

    /** @var Service */
    private static $service;

    public static function install() {
        if (class_exists(Service::class) && get_option('wbsng_settings_multicurrency', true)) {
            self::$service = Service::install(true, new class implements Numbers {
                function multiply($price, $rate) {
                    if ($price instanceof Decimal) {
                        return $price->multipliedBy(Decimal::of($rate));
                    }
                    return $price * $rate;
                }
                function adapt($price, callable $fn) {
                    if ($price instanceof Decimal) {
                        return Decimal::of($fn($price->toFloat()));
                    }
                    return $fn($price);
                }
            });
        }
    }

    public static function convert(Decimal $price): Decimal {
        return isset(self::$service) ? self::$service->convert($price) : $price;
    }
}


<?php
if ( ! defined( 'ABSPATH' ) ) exit;
class Moyasar_Currency_Helper
{
    public static function amount_to_minor($amount, $currency)
    {
        // Rounding here is needed to mitigate the problems of IEEE 754 standard.
        // If for example we did amount_to_minor(1263.85, 'SAR') without rounding
        // we will get (126384.99999999999) and when we cut out the fraction
        // we will be short one Halalah
        return round($amount * (10 ** static::fractionFor($currency)), 0);
    }

    public static function amount_to_major($amount, $currency)
    {
        return floatval($amount) / (10 ** static::fractionFor($currency));
    }

    public static function fractionFor($currency)
    {
        $fractions = static::fractions();
        return isset($fractions[$currency]) ? $fractions[$currency] : 2;
    }

    public static function fractions()
    {
        return array(
            'ADP' => 0,
            'AFN' => 0,
            'ALL' => 0,
            'AMD' => 0,
            'BHD' => 3,
            'BIF' => 0,
            'BYN' => 2,
            'BYR' => 0,
            'CAD' => 2,
            'CHF' => 2,
            'CLF' => 4,
            'CLP' => 0,
            'COP' => 0,
            'CRC' => 0,
            'CZK' => 0,
            'DJF' => 0,
            'DKK' => 2,
            'ESP' => 0,
            'GNF' => 0,
            'GYD' => 0,
            'HUF' => 0,
            'IDR' => 0,
            'IQD' => 0,
            'IRR' => 0,
            'ISK' => 0,
            'ITL' => 0,
            'JOD' => 3,
            'JPY' => 0,
            'KMF' => 0,
            'KPW' => 0,
            'KRW' => 0,
            'KWD' => 3,
            'LAK' => 0,
            'LBP' => 0,
            'LUF' => 0,
            'LYD' => 3,
            'MGA' => 0,
            'MGF' => 0,
            'MMK' => 0,
            'MNT' => 0,
            'MRO' => 0,
            'MUR' => 0,
            'NOK' => 0,
            'OMR' => 3,
            'PKR' => 0,
            'PYG' => 0,
            'RSD' => 0,
            'RWF' => 0,
            'SEK' => 0,
            'SLL' => 0,
            'SOS' => 0,
            'STD' => 0,
            'SYP' => 0,
            'TMM' => 0,
            'TND' => 3,
            'TRL' => 0,
            'TWD' => 0,
            'TZS' => 0,
            'UGX' => 0,
            'UYI' => 0,
            'UYW' => 4,
            'UZS' => 0,
            'VEF' => 0,
            'VND' => 0,
            'VUV' => 0,
            'XAF' => 0,
            'XOF' => 0,
            'XPF' => 0,
            'YER' => 0,
            'ZMK' => 0,
            'ZWD' => 0
        );
    }
}

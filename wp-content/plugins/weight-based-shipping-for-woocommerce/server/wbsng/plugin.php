<?php declare(strict_types=1);
/**
 * Plugin Name: Weight Based Shipping 6 (isolated)
 */

require_once(__DIR__.'/vendor/autoload.php');

/** @noinspection PhpFullyQualifiedNameUsageInspection */
\Aikinomi\Wbsng\Plugin::setupOnce(wp_normalize_path(__FILE__));
<?php
/**
 * PHPUnit bootstrap: fakes a WordPress runtime before the plugin classes load.
 */

define( 'ABSPATH', __DIR__ . '/wp/' );

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/wp-stubs.php';

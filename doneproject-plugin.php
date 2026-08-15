<?php
/**
 * Plugin Name: DoneProject Plugin
 * Plugin URI: https://github.com/liikane/doneproject
 * Description: Integrates DoneProject features into WordPress — shortcode, admin settings, and REST endpoint. Skeleton with PSR-4 structure.
 * Version: 0.1.0
 * Author: liikane
 * Text Domain: doneproject-plugin
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Try Composer autoload if present
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// Fallback autoloader for PSR-4 simple map if Composer isn't used
spl_autoload_register( function ( $class ) {
    $prefix = 'DoneProject\\';
    $base_dir = __DIR__ . '/src/';
    $len = strlen( $prefix );
    if ( strncmp( $prefix, $class, $len ) !== 0 ) {
        return;
    }
    $relative_class = substr( $class, $len );
    $file = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';
    if ( file_exists( $file ) ) {
        require $file;
    }
} );

// Boot the plugin
function doneproject_plugin_boot() {
    \DoneProject\Plugin::instance();
}
add_action( 'plugins_loaded', 'doneproject_plugin_boot' );

<?php
// If uninstall is not called by WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Remove options created by the plugin
delete_option( 'doneproject-plugin_greeting' );

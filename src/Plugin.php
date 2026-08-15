<?php
namespace DoneProject;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Plugin {
    private static $instance = null;
    public $slug = 'doneproject-plugin';
    public $version = '0.1.0';

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
            self::$instance->setup();
        }
        return self::$instance;
    }

    private function setup() {
        register_activation_hook( __DIR__ . '/../doneproject-plugin.php', array( $this, 'on_activate' ) );
        register_deactivation_hook( __DIR__ . '/../doneproject-plugin.php', array( $this, 'on_deactivate' ) );

        add_action( 'admin_menu', array( $this, 'init_admin' ) );
        add_action( 'init', array( $this, 'init_frontend' ) );
        add_action( 'rest_api_init', array( $this, 'init_rest' ) );
    }

    public function on_activate() {
        if ( false === get_option( $this->slug . '_greeting' ) ) {
            update_option( $this->slug . '_greeting', 'Tere' );
        }
    }

    public function on_deactivate() {
        // noop for now
    }

    public function init_admin() {
        if ( is_admin() ) {
            new Admin( $this );
        }
    }

    public function init_frontend() {
        new Frontend( $this );
    }

    public function init_rest() {
        new REST( $this );
    }
}

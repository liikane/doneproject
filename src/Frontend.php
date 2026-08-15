<?php
namespace DoneProject;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Frontend {
    private $plugin;

    public function __construct( $plugin ) {
        $this->plugin = $plugin;
        add_shortcode( 'doneproject', array( $this, 'shortcode' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    public function enqueue_assets() {
        wp_register_style( 'doneproject-style', plugins_url( 'assets/css/style.css', __DIR__ . '/../doneproject-plugin.php' ), array(), $this->plugin->version );
        wp_register_script( 'doneproject-script', plugins_url( 'assets/js/script.js', __DIR__ . '/../doneproject-plugin.php' ), array(), $this->plugin->version, true );
    }

    public function shortcode( $atts ) {
        $atts = shortcode_atts( array( 'name' => 'maailm' ), $atts, 'doneproject' );
        $greeting = esc_html( get_option( $this->plugin->slug . '_greeting', 'Tere' ) );
        $name = esc_html( $atts['name'] );

        // Enqueue when shortcode is used
        wp_enqueue_style( 'doneproject-style' );
        wp_enqueue_script( 'doneproject-script' );

        return sprintf( '<div class="doneproject-greeting">%s, %s!</div>', $greeting, $name );
    }
}

<?php
namespace DoneProject;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class REST {
    private $plugin;

    public function __construct( $plugin ) {
        $this->plugin = $plugin;
        $this->register_routes();
    }

    public function register_routes() {
        register_rest_route( 'doneproject/v1', '/hello', array(
            'methods' => 'GET',
            'callback' => array( $this, 'hello' ),
            'permission_callback' => function() {
                return current_user_can( 'read' );
            },
        ) );
    }

    public function hello( $request ) {
        $greeting = get_option( $this->plugin->slug . '_greeting', 'Tere' );
        return rest_ensure_response( array( 'message' => $greeting . ' maailm' ) );
    }
}

<?php
namespace DoneProject;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Admin {
    private $plugin;

    public function __construct( $plugin ) {
        $this->plugin = $plugin;
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_menu', array( $this, 'add_menu' ) );
    }

    public function add_menu() {
        add_options_page(
            'DoneProject Settings',
            'DoneProject',
            'manage_options',
            $this->plugin->slug,
            array( $this, 'settings_page' )
        );
    }

    public function register_settings() {
        register_setting( $this->plugin->slug . '_group', $this->plugin->slug . '_greeting', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'Tere',
        ) );

        add_settings_section( $this->plugin->slug . '_section', 'Põhiseaded', null, $this->plugin->slug );

        add_settings_field(
            $this->plugin->slug . '_greeting_field',
            'Tervitussõna',
            array( $this, 'field_html' ),
            $this->plugin->slug,
            $this->plugin->slug . '_section'
        );
    }

    public function field_html() {
        $val = esc_attr( get_option( $this->plugin->slug . '_greeting', 'Tere' ) );
        printf( '<input type="text" name="%1$s_greeting" value="%2$s" class="regular-text" />', esc_attr( $this->plugin->slug ), $val );
    }

    public function settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        ?>
        <div class="wrap">
            <h1>DoneProject seaded</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( $this->plugin->slug . '_group' );
                do_settings_sections( $this->plugin->slug );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
}

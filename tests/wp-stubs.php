<?php
/**
 * Minimal stand-ins for the WordPress functions and classes the plugin uses, so
 * the plugin classes can be unit tested without a WordPress installation.
 */

use DoneProject\Tests\WPStub;

class WP_Error {
    private $code;
    private $message;

    public function __construct( $code = '', $message = '' ) {
        $this->code = $code;
        $this->message = $message;
    }

    public function get_error_code() {
        return $this->code;
    }

    public function get_error_message() {
        return $this->message;
    }
}

/**
 * Iterates over WPStub::$posts the way WP_Query iterates over found posts.
 */
class WP_Query {
    public $args;
    public $posts;
    private $position = -1;

    public function __construct( $args = array() ) {
        $this->args = $args;
        $this->posts = WPStub::$posts;
        WPStub::record( 'WP_Query', array( $args ) );
    }

    public function have_posts() {
        return $this->position + 1 < count( $this->posts );
    }

    public function the_post() {
        $this->position++;
        $GLOBALS['doneproject_current_post'] = $this->posts[ $this->position ];
    }
}

/**
 * Image editor double: records resize()/save() calls and returns configured results.
 */
class DoneProject_Fake_Image_Editor {
    public $resize_result;
    public $save_result;
    public $resize_calls = array();
    public $save_calls = array();

    public function __construct( $save_result = array( 'path' => '/uploads/doneproject-thumbs/thumb.jpg' ), $resize_result = true ) {
        $this->save_result = $save_result;
        $this->resize_result = $resize_result;
    }

    public function resize( $width, $height, $crop = false ) {
        $this->resize_calls[] = array( $width, $height, $crop );
        return $this->resize_result;
    }

    public function save( $path ) {
        $this->save_calls[] = $path;
        if ( is_array( $this->save_result ) && ! empty( $this->save_result['create'] ) ) {
            file_put_contents( $path, 'thumb' );
        }
        return $this->save_result;
    }
}

function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
    WPStub::record( 'add_action', array( $hook, $callback, $priority ) );
    return true;
}

function add_shortcode( $tag, $callback ) {
    WPStub::record( 'add_shortcode', array( $tag, $callback ) );
}

function register_activation_hook( $file, $callback ) {
    WPStub::record( 'register_activation_hook', array( $file, $callback ) );
}

function register_deactivation_hook( $file, $callback ) {
    WPStub::record( 'register_deactivation_hook', array( $file, $callback ) );
}

function get_option( $name, $default = false ) {
    WPStub::record( 'get_option', array( $name, $default ) );
    return array_key_exists( $name, WPStub::$options ) ? WPStub::$options[ $name ] : $default;
}

function update_option( $name, $value ) {
    WPStub::record( 'update_option', array( $name, $value ) );
    WPStub::$options[ $name ] = $value;
    return true;
}

function is_admin() {
    return WPStub::$is_admin;
}

function current_user_can( $capability ) {
    WPStub::record( 'current_user_can', array( $capability ) );
    return WPStub::$current_user_can;
}

function add_options_page( $page_title, $menu_title, $capability, $menu_slug, $callback ) {
    WPStub::record( 'add_options_page', array( $page_title, $menu_title, $capability, $menu_slug, $callback ) );
}

function register_setting( $group, $name, $args = array() ) {
    WPStub::record( 'register_setting', array( $group, $name, $args ) );
}

function add_settings_section( $id, $title, $callback, $page ) {
    WPStub::record( 'add_settings_section', array( $id, $title, $callback, $page ) );
}

function add_settings_field( $id, $title, $callback, $page, $section = 'default' ) {
    WPStub::record( 'add_settings_field', array( $id, $title, $callback, $page, $section ) );
}

function settings_fields( $group ) {
    WPStub::record( 'settings_fields', array( $group ) );
    echo '<input type="hidden" name="option_page" value="' . $group . '" />';
}

function do_settings_sections( $page ) {
    WPStub::record( 'do_settings_sections', array( $page ) );
}

function submit_button( $text = null ) {
    WPStub::record( 'submit_button', array( $text ) );
    echo '<input type="submit" value="Save" />';
}

function register_rest_route( $namespace, $route, $args = array() ) {
    WPStub::record( 'register_rest_route', array( $namespace, $route, $args ) );
    return true;
}

function rest_ensure_response( $response ) {
    WPStub::record( 'rest_ensure_response', array( $response ) );
    return $response;
}

function wp_register_style( $handle, $src = '', $deps = array(), $version = false ) {
    WPStub::record( 'wp_register_style', array( $handle, $src, $deps, $version ) );
}

function wp_register_script( $handle, $src = '', $deps = array(), $version = false, $in_footer = false ) {
    WPStub::record( 'wp_register_script', array( $handle, $src, $deps, $version, $in_footer ) );
}

function wp_enqueue_style( $handle ) {
    WPStub::record( 'wp_enqueue_style', array( $handle ) );
}

function wp_enqueue_script( $handle ) {
    WPStub::record( 'wp_enqueue_script', array( $handle ) );
}

function plugins_url( $path = '', $plugin = '' ) {
    return 'http://example.com/wp-content/plugins/doneproject-plugin/' . ltrim( $path, '/' );
}

function shortcode_atts( $pairs, $atts, $shortcode = '' ) {
    $atts = (array) $atts;
    $out = array();
    foreach ( $pairs as $name => $default ) {
        $out[ $name ] = array_key_exists( $name, $atts ) ? $atts[ $name ] : $default;
    }
    return $out;
}

function sanitize_text_field( $value ) {
    return trim( strip_tags( (string) $value ) );
}

function sanitize_title( $value ) {
    $value = strtolower( trim( (string) $value ) );
    $value = preg_replace( '/[^a-z0-9_\-]+/', '-', $value );
    return trim( $value, '-' );
}

function esc_html( $text ) {
    return htmlspecialchars( (string) $text, ENT_QUOTES );
}

function esc_attr( $text ) {
    return htmlspecialchars( (string) $text, ENT_QUOTES );
}

function esc_html__( $text, $domain = 'default' ) {
    WPStub::record( 'esc_html__', array( $text, $domain ) );
    return htmlspecialchars( $text, ENT_QUOTES );
}

function esc_url( $url ) {
    return str_replace( ' ', '%20', (string) $url );
}

function esc_url_raw( $url ) {
    return trim( (string) $url );
}

function wp_kses_post( $content ) {
    return $content;
}

function wpautop( $text ) {
    return '<p>' . $text . '</p>';
}

function get_the_ID() {
    return $GLOBALS['doneproject_current_post']['ID'] ?? 0;
}

function get_the_title() {
    return $GLOBALS['doneproject_current_post']['title'] ?? '';
}

function get_permalink() {
    return $GLOBALS['doneproject_current_post']['permalink'] ?? '';
}

function get_the_excerpt() {
    return $GLOBALS['doneproject_current_post']['excerpt'] ?? '';
}

function get_post_field( $field, $post_id ) {
    WPStub::record( 'get_post_field', array( $field, $post_id ) );
    foreach ( WPStub::$posts as $post ) {
        if ( ( $post['ID'] ?? null ) === $post_id ) {
            return $post[ $field ] ?? '';
        }
    }
    return '';
}

function wp_reset_postdata() {
    WPStub::record( 'wp_reset_postdata', array() );
    unset( $GLOBALS['doneproject_current_post'] );
}

function wp_get_upload_dir() {
    return WPStub::$upload_dir;
}

function wp_mkdir_p( $dir ) {
    WPStub::record( 'wp_mkdir_p', array( $dir ) );
    if ( ! WPStub::$mkdir_p ) {
        return false;
    }
    return is_dir( $dir ) || mkdir( $dir, 0777, true );
}

function wp_get_image_editor( $path ) {
    WPStub::record( 'wp_get_image_editor', array( $path ) );
    return WPStub::$image_editor;
}

function is_wp_error( $thing ) {
    return $thing instanceof WP_Error;
}

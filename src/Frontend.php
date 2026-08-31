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
        add_shortcode( 'doneproject_pages', array( $this, 'pages_list_shortcode' ) );
        add_shortcode( 'doneproject_list', array( $this, 'pages_list_shortcode' ) ); // alias
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

    /**
     * Shortcode to list pages (or other post types) in a given category and show first image from content as thumbnail.
     * Usage: [doneproject_pages category="slug-or-id" post_type="page" count="10" image_width="150" image_height="150" show_excerpt="0"]
     */
    public function pages_list_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'category' => '',
            'post_type' => 'page',
            'count' => 10,
            'image_width' => 150,
            'image_height' => 150,
            'show_excerpt' => 0,
        ), $atts, 'doneproject_pages' );

        $category = sanitize_text_field( $atts['category'] );
        $post_type = sanitize_text_field( $atts['post_type'] );
        $count = intval( $atts['count'] );
        $width = intval( $atts['image_width'] );
        $height = intval( $atts['image_height'] );
        $show_excerpt = intval( $atts['show_excerpt'] );

        if ( empty( $category ) ) {
            return '<p>' . esc_html__( 'Palun määra kategooria (category) shortcode atribuudis.', 'doneproject-plugin' ) . '</p>';
        }

        // Build query
        $tax_query = array();
        if ( is_numeric( $category ) ) {
            $tax_query[] = array(
                'taxonomy' => 'category',
                'field' => 'term_id',
                'terms' => intval( $category ),
            );
        } else {
            $tax_query[] = array(
                'taxonomy' => 'category',
                'field' => 'slug',
                'terms' => sanitize_title( $category ),
            );
        }

        $query_args = array(
            'post_type' => $post_type,
            'posts_per_page' => $count,
            'tax_query' => $tax_query,
            'orderby' => 'date',
            'order' => 'DESC',
        );

        $q = new \WP_Query( $query_args );

        if ( ! $q->have_posts() ) {
            return '<p>' . esc_html__( 'Ühtegi lehte selles kategoorias ei leitud.', 'doneproject-plugin' ) . '</p>';
        }

        // Enqueue assets
        wp_enqueue_style( 'doneproject-style' );
        wp_enqueue_script( 'doneproject-script' );

        $output = '<div class="doneproject-pages-list">';
        while ( $q->have_posts() ) {
            $q->the_post();
            $post_id = get_the_ID();
            $title = get_the_title();
            $permalink = get_permalink();

            // Try to get first image from content
            $content = get_post_field( 'post_content', $post_id );
            $img_src = $this->get_first_image_src_from_content( $content );
            $thumb_url = '';
            if ( $img_src ) {
                $thumb_url = $this->generate_content_image_thumbnail( $img_src, $width, $height );
            }

            $output .= '<div class="doneproject-item">';
            if ( $thumb_url ) {
                $output .= sprintf( '<a class="doneproject-thumb-link" href="%s"><img src="%s" alt="%s" width="%d" height="%d" class="doneproject-thumb" /></a>', esc_url( $permalink ), esc_url( $thumb_url ), esc_attr( $title ), $width, $height );
            }
            $output .= sprintf( '<a class="doneproject-title" href="%s"><h3>%s</h3></a>', esc_url( $permalink ), esc_html( $title ) );

            if ( $show_excerpt ) {
                $excerpt = get_the_excerpt();
                $output .= '<div class="doneproject-excerpt">' . wp_kses_post( wpautop( $excerpt ) ) . '</div>';
            }

            $output .= '</div>'; // .doneproject-item
        }
        wp_reset_postdata();

        $output .= '</div>'; // .doneproject-pages-list

        return $output;
    }

    /**
     * Extract the first <img> src from HTML content.
     */
    private function get_first_image_src_from_content( $content ) {
        if ( empty( $content ) ) {
            return false;
        }

        // Look for img tags
        if ( preg_match( '/<img[^>]+src=["\']?([^"\' >]+)["\']?[^>]*>/i', $content, $matches ) ) {
            return esc_url_raw( $matches[1] );
        }

        return false;
    }

    /**
     * Generate a thumbnail for a content image if it's local (in uploads). Returns URL of thumbnail or original src if generation failed.
     */
    private function generate_content_image_thumbnail( $src, $width = 150, $height = 150 ) {
        if ( empty( $src ) ) {
            return false;
        }

        $src = strtok( $src, '?#' ); // strip query string/fragment

        // Check if image is in uploads dir
        $uploads = wp_get_upload_dir();
        $baseurl = $uploads['baseurl'];
        $basedir = $uploads['basedir'];

        // If src is hosted in uploads, map to path
        if ( 0 === strpos( $src, $baseurl ) ) {
            $relative = substr( $src, strlen( $baseurl ) );
            $file_path = $basedir . $relative;
            // Prevent path traversal attacks
            $file_path = realpath( $file_path );
            $basedir_real = realpath( $basedir );
            if ( ! $file_path || ! $basedir_real || 0 !== strpos( $file_path, $basedir_real ) ) {
                return esc_url( $src );
            }
            if ( file_exists( $file_path ) ) {
                // Prepare thumbs directory
                $thumbs_dir = $basedir . '/doneproject-thumbs';
                if ( ! wp_mkdir_p( $thumbs_dir ) ) {
                    return esc_url( $src );
                }

                $ext = pathinfo( $file_path, PATHINFO_EXTENSION );
                $hash = md5_file( $file_path );
                $thumb_filename = $hash . "-{$width}x{$height}." . $ext;
                $thumb_path = $thumbs_dir . '/' . $thumb_filename;
                $thumb_url = $baseurl . '/doneproject-thumbs/' . $thumb_filename;

                if ( file_exists( $thumb_path ) ) {
                    return esc_url( $thumb_url );
                }

                $editor = wp_get_image_editor( $file_path );
                if ( is_wp_error( $editor ) ) {
                    return esc_url( $src );
                }

                $resized = $editor->resize( $width, $height, true );
                $saved = $editor->save( $thumb_path );

                if ( ! is_wp_error( $saved ) && $saved ) {
                    return esc_url( $thumb_url );
                }

                return esc_url( $src );
            }
        }

        // For non-local images, return original src (browser will scale) - could be improved to sideload
        return esc_url( $src );
    }
}

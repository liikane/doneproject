<?php
namespace DoneProject\Tests;

/**
 * Collects the WordPress API calls made by the plugin and holds the values the
 * stubbed functions should return.
 */
class WPStub {
    /** @var array<string, array<int, array>> */
    public static $calls = array();

    /** @var array<string, mixed> */
    public static $options = array();

    /** @var bool */
    public static $is_admin = false;

    /** @var bool */
    public static $current_user_can = true;

    /** @var array Posts returned by the WP_Query stub. */
    public static $posts = array();

    /** @var array{basedir: string, baseurl: string} */
    public static $upload_dir = array( 'basedir' => '/uploads', 'baseurl' => 'http://example.com/wp-content/uploads' );

    /** @var bool */
    public static $mkdir_p = true;

    /** @var mixed Image editor returned by wp_get_image_editor(). */
    public static $image_editor = null;

    public static function reset(): void {
        self::$calls = array();
        self::$options = array();
        self::$is_admin = false;
        self::$current_user_can = true;
        self::$posts = array();
        self::$upload_dir = array( 'basedir' => '/uploads', 'baseurl' => 'http://example.com/wp-content/uploads' );
        self::$mkdir_p = true;
        self::$image_editor = null;
    }

    public static function record( string $function, array $args ): void {
        self::$calls[ $function ][] = $args;
    }

    /**
     * @return array<int, array> Arguments of every recorded call to $function.
     */
    public static function callsTo( string $function ): array {
        return self::$calls[ $function ] ?? array();
    }

    public static function firstCall( string $function ): ?array {
        return self::$calls[ $function ][0] ?? null;
    }

    public static function called( string $function ): bool {
        return ! empty( self::$calls[ $function ] );
    }
}

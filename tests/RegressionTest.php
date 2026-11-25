<?php
namespace {
    if ( ! defined( 'ABSPATH' ) ) {
        define( 'ABSPATH', __DIR__ . '/' );
    }

    require_once __DIR__ . '/../src/Frontend.php';
    require_once __DIR__ . '/../src/REST.php';
}

namespace DoneProject {
    class WP_Error {}

    class RegressionTestState {
        public static $uploads = array();
        public static $image_editor;
        public static $image_editor_calls = 0;
        public static $route;
        public static $current_user_can = false;
        public static $last_capability;
    }

    function add_shortcode( $tag, $callback ) {}

    function add_action( $hook, $callback ) {}

    function wp_get_upload_dir() {
        return RegressionTestState::$uploads;
    }

    function wp_mkdir_p( $dir ) {
        return is_dir( $dir ) || mkdir( $dir, 0700, true );
    }

    function wp_get_image_editor( $path ) {
        RegressionTestState::$image_editor_calls++;
        return RegressionTestState::$image_editor;
    }

    function is_wp_error( $value ) {
        return $value instanceof WP_Error;
    }

    function esc_url( $url ) {
        return $url;
    }

    function register_rest_route( $namespace, $route, $args ) {
        RegressionTestState::$route = $args;
    }

    function current_user_can( $capability ) {
        RegressionTestState::$last_capability = $capability;
        return RegressionTestState::$current_user_can;
    }
}

namespace DoneProject\Tests {
    use DoneProject\Frontend;
    use DoneProject\RegressionTestState as State;
    use DoneProject\REST;
    use DoneProject\WP_Error;
    use PHPUnit\Framework\TestCase;

    class RegressionImageEditor {
        public $resize_result;
        public $save_calls = array();

        public function __construct( $resize_result ) {
            $this->resize_result = $resize_result;
        }

        public function resize( $width, $height, $crop = false ) {
            return $this->resize_result;
        }

        public function save( $path ) {
            $this->save_calls[] = $path;
            return array( 'path' => $path );
        }
    }

    class RegressionTest extends TestCase {
        private $root;
        private $basedir;
        private $baseurl = 'https://example.test/uploads';

        protected function setUp(): void {
            parent::setUp();
            $this->root = sys_get_temp_dir() . '/doneproject-regression-' . bin2hex( random_bytes( 8 ) );
            $this->basedir = $this->root . '/uploads';
            mkdir( $this->basedir, 0700, true );
            State::$uploads = array(
                'basedir' => $this->basedir,
                'baseurl' => $this->baseurl,
            );
            State::$image_editor = null;
            State::$image_editor_calls = 0;
            State::$route = null;
            State::$current_user_can = false;
            State::$last_capability = null;
        }

        protected function tearDown(): void {
            $this->removeDir( $this->root );
            parent::tearDown();
        }

        public function testThumbnailRejectsFilesInSiblingDirectories(): void {
            $sibling = $this->root . '/uploads-neighbor';
            mkdir( $sibling, 0700, true );
            file_put_contents( $sibling . '/image.jpg', 'image' );
            State::$image_editor = new RegressionImageEditor( true );
            $src = $this->baseurl . '-neighbor/image.jpg';

            $result = $this->callThumbnailGenerator( $src );

            $this->assertSame( $src, $result );
            $this->assertSame( 0, State::$image_editor_calls );
        }

        public function testThumbnailFallsBackWhenResizeFails(): void {
            file_put_contents( $this->basedir . '/image.jpg', 'image' );
            $editor = new RegressionImageEditor( new WP_Error() );
            State::$image_editor = $editor;
            $src = $this->baseurl . '/image.jpg';

            $result = $this->callThumbnailGenerator( $src );

            $this->assertSame( $src, $result );
            $this->assertSame( array(), $editor->save_calls );
        }

        public function testRestRouteRequiresReadCapability(): void {
            new REST( (object) array( 'slug' => 'doneproject-plugin' ) );
            $permission_callback = State::$route['permission_callback'];

            $this->assertFalse( call_user_func( $permission_callback ) );
            $this->assertSame( 'read', State::$last_capability );

            State::$current_user_can = true;
            $this->assertTrue( call_user_func( $permission_callback ) );
        }

        private function callThumbnailGenerator( $src ) {
            $frontend = new Frontend( (object) array( 'version' => '1.0' ) );
            $method = new \ReflectionMethod( $frontend, 'generate_content_image_thumbnail' );
            $method->setAccessible( true );
            return $method->invoke( $frontend, $src );
        }

        private function removeDir( $dir ) {
            if ( ! is_dir( $dir ) ) {
                return;
            }

            foreach ( scandir( $dir ) as $entry ) {
                if ( '.' === $entry || '..' === $entry ) {
                    continue;
                }

                $path = $dir . '/' . $entry;
                if ( is_dir( $path ) ) {
                    $this->removeDir( $path );
                } else {
                    unlink( $path );
                }
            }

            rmdir( $dir );
        }
    }
}

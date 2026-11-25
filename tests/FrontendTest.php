<?php
namespace DoneProject\Tests;

use DoneProject\Frontend;

class FrontendTest extends TestCase {
    /** @var string */
    private $basedir;

    /** @var string */
    private $baseurl = 'http://example.com/wp-content/uploads';

    protected function setUp(): void {
        parent::setUp();
        $this->basedir = sys_get_temp_dir() . '/doneproject-tests-' . uniqid();
        mkdir( $this->basedir . '/2024/01', 0777, true );
        WPStub::$upload_dir = array( 'basedir' => $this->basedir, 'baseurl' => $this->baseurl );
    }

    protected function tearDown(): void {
        $this->removeDir( $this->basedir );
        parent::tearDown();
    }

    private function removeDir( string $dir ): void {
        if ( ! is_dir( $dir ) ) {
            return;
        }
        foreach ( scandir( $dir ) as $entry ) {
            if ( '.' === $entry || '..' === $entry ) {
                continue;
            }
            $path = $dir . '/' . $entry;
            is_dir( $path ) ? $this->removeDir( $path ) : unlink( $path );
        }
        rmdir( $dir );
    }

    private function frontend(): Frontend {
        return new Frontend( $this->pluginStub() );
    }

    private function localImage( string $relative = '/2024/01/photo.jpg' ): string {
        file_put_contents( $this->basedir . $relative, 'image-bytes' );
        return $this->baseurl . $relative;
    }

    public function testConstructorRegistersShortcodesAndAssetHook(): void {
        $frontend = $this->frontend();

        $shortcodes = array();
        foreach ( WPStub::callsTo( 'add_shortcode' ) as $call ) {
            $shortcodes[ $call[0] ] = $call[1];
        }

        $this->assertSame( array( $frontend, 'shortcode' ), $shortcodes['doneproject'] );
        $this->assertSame( array( $frontend, 'pages_list_shortcode' ), $shortcodes['doneproject_pages'] );
        $this->assertSame( array( $frontend, 'pages_list_shortcode' ), $shortcodes['doneproject_list'] );
        $this->assertSame(
            array( 'wp_enqueue_scripts', array( $frontend, 'enqueue_assets' ), 10 ),
            WPStub::firstCall( 'add_action' )
        );
    }

    public function testEnqueueAssetsRegistersStyleAndScriptWithPluginVersion(): void {
        $this->frontend()->enqueue_assets();

        $style = WPStub::firstCall( 'wp_register_style' );
        $this->assertSame( 'doneproject-style', $style[0] );
        $this->assertStringEndsWith( 'assets/css/style.css', $style[1] );
        $this->assertSame( '0.1.0', $style[3] );

        $script = WPStub::firstCall( 'wp_register_script' );
        $this->assertSame( 'doneproject-script', $script[0] );
        $this->assertStringEndsWith( 'assets/js/script.js', $script[1] );
        $this->assertSame( '0.1.0', $script[3] );
        $this->assertTrue( $script[4] );
    }

    public function testShortcodeUsesDefaultNameAndGreeting(): void {
        $html = $this->frontend()->shortcode( array() );

        $this->assertSame( '<div class="doneproject-greeting">Tere, maailm!</div>', $html );
    }

    public function testShortcodeUsesGivenNameAndStoredGreeting(): void {
        WPStub::$options['doneproject-plugin_greeting'] = 'Hei';

        $html = $this->frontend()->shortcode( array( 'name' => 'Tallinn' ) );

        $this->assertSame( '<div class="doneproject-greeting">Hei, Tallinn!</div>', $html );
    }

    public function testShortcodeEscapesNameAndGreeting(): void {
        WPStub::$options['doneproject-plugin_greeting'] = '<b>Tere</b>';

        $html = $this->frontend()->shortcode( array( 'name' => '<script>alert(1)</script>' ) );

        $this->assertStringNotContainsString( '<script>', $html );
        $this->assertStringNotContainsString( '<b>', $html );
        $this->assertStringContainsString( '&lt;b&gt;Tere&lt;/b&gt;', $html );
    }

    public function testShortcodeEnqueuesAssets(): void {
        $this->frontend()->shortcode( array() );

        $this->assertSame( array( 'doneproject-style' ), WPStub::firstCall( 'wp_enqueue_style' ) );
        $this->assertSame( array( 'doneproject-script' ), WPStub::firstCall( 'wp_enqueue_script' ) );
    }

    public function testPagesListRequiresCategory(): void {
        $html = $this->frontend()->pages_list_shortcode( array() );

        $this->assertStringContainsString( 'Palun määra kategooria', $html );
        $this->assertFalse( WPStub::called( 'WP_Query' ) );
    }

    public function testPagesListBuildsQueryFromAttributes(): void {
        WPStub::$posts = array();

        $this->frontend()->pages_list_shortcode( array(
            'category' => 'Valminud Objektid',
            'post_type' => 'post',
            'count' => '4',
        ) );

        $args = WPStub::firstCall( 'WP_Query' )[0];
        $this->assertSame( 'post', $args['post_type'] );
        $this->assertSame( 4, $args['posts_per_page'] );
        $this->assertSame( 'date', $args['orderby'] );
        $this->assertSame( 'DESC', $args['order'] );
        $this->assertSame(
            array( array( 'taxonomy' => 'category', 'field' => 'slug', 'terms' => 'valminud-objektid' ) ),
            $args['tax_query']
        );
    }

    public function testPagesListQueriesByTermIdForNumericCategory(): void {
        $this->frontend()->pages_list_shortcode( array( 'category' => '17' ) );

        $this->assertSame(
            array( array( 'taxonomy' => 'category', 'field' => 'term_id', 'terms' => 17 ) ),
            WPStub::firstCall( 'WP_Query' )[0]['tax_query']
        );
    }

    public function testPagesListReportsWhenNoPostsFound(): void {
        WPStub::$posts = array();

        $html = $this->frontend()->pages_list_shortcode( array( 'category' => 'tyhi' ) );

        $this->assertStringContainsString( 'Ühtegi lehte selles kategoorias ei leitud.', $html );
        $this->assertFalse( WPStub::called( 'wp_enqueue_style' ) );
    }

    public function testPagesListRendersTitlesAndLinks(): void {
        WPStub::$posts = array(
            array( 'ID' => 1, 'title' => 'Esimene', 'permalink' => 'http://example.com/esimene', 'post_content' => '<p>ei pilti</p>' ),
            array( 'ID' => 2, 'title' => 'Teine & <b>', 'permalink' => 'http://example.com/teine', 'post_content' => '' ),
        );

        $html = $this->frontend()->pages_list_shortcode( array( 'category' => 'objektid' ) );

        $this->assertStringContainsString( '<div class="doneproject-pages-list">', $html );
        $this->assertSame( 2, substr_count( $html, 'class="doneproject-item"' ) );
        $this->assertStringContainsString( '<a class="doneproject-title" href="http://example.com/esimene"><h3>Esimene</h3></a>', $html );
        $this->assertStringContainsString( 'Teine &amp; &lt;b&gt;', $html );
        $this->assertStringNotContainsString( 'doneproject-thumb-link', $html );
        $this->assertTrue( WPStub::called( 'wp_reset_postdata' ) );
    }

    public function testPagesListRendersThumbnailFromContentImage(): void {
        $src = $this->localImage();
        WPStub::$image_editor = new \DoneProject_Fake_Image_Editor( array( 'path' => 'thumb', 'create' => true ) );
        WPStub::$posts = array(
            array( 'ID' => 5, 'title' => 'Pildiga', 'permalink' => 'http://example.com/pildiga', 'post_content' => '<p><img src="' . $src . '" /></p>' ),
        );

        $html = $this->frontend()->pages_list_shortcode( array(
            'category' => 'objektid',
            'image_width' => '180',
            'image_height' => '120',
        ) );

        $expectedThumb = $this->baseurl . '/doneproject-thumbs/' . md5( 'image-bytes' ) . '-180x120.jpg';
        $this->assertStringContainsString( 'src="' . $expectedThumb . '"', $html );
        $this->assertStringContainsString( 'width="180" height="120"', $html );
        $this->assertSame( array( array( 180, 120, true ) ), WPStub::$image_editor->resize_calls );
    }

    public function testPagesListRendersExcerptOnlyWhenRequested(): void {
        WPStub::$posts = array(
            array( 'ID' => 7, 'title' => 'Kokkuvõttega', 'permalink' => 'http://example.com/k', 'post_content' => '', 'excerpt' => 'Lühike tekst' ),
        );
        $frontend = $this->frontend();

        $without = $frontend->pages_list_shortcode( array( 'category' => 'objektid' ) );
        $with = $frontend->pages_list_shortcode( array( 'category' => 'objektid', 'show_excerpt' => '1' ) );

        $this->assertStringNotContainsString( 'doneproject-excerpt', $without );
        $this->assertStringContainsString( '<div class="doneproject-excerpt"><p>Lühike tekst</p></div>', $with );
    }

    public function testGetFirstImageSrcReturnsFalseWithoutImage(): void {
        $frontend = $this->frontend();

        $this->assertFalse( $this->callPrivate( $frontend, 'get_first_image_src_from_content', array( '' ) ) );
        $this->assertFalse( $this->callPrivate( $frontend, 'get_first_image_src_from_content', array( '<p>tekst</p>' ) ) );
    }

    public function testGetFirstImageSrcReturnsFirstImageOfContent(): void {
        $content = '<p>tekst</p><img class="x" src="http://example.com/a.jpg" alt="a" /><img src=\'http://example.com/b.jpg\'>';

        $this->assertSame(
            'http://example.com/a.jpg',
            $this->callPrivate( $this->frontend(), 'get_first_image_src_from_content', array( $content ) )
        );
    }

    public function testGetFirstImageSrcHandlesSingleQuotedSrc(): void {
        $this->assertSame(
            'http://example.com/b.jpg',
            $this->callPrivate( $this->frontend(), 'get_first_image_src_from_content', array( "<img src='http://example.com/b.jpg'>" ) )
        );
    }

    public function testThumbnailGenerationReturnsFalseForEmptySrc(): void {
        $this->assertFalse( $this->callPrivate( $this->frontend(), 'generate_content_image_thumbnail', array( '' ) ) );
    }

    public function testThumbnailGenerationReturnsRemoteSrcUntouched(): void {
        $src = 'http://cdn.example.org/photo.jpg';

        $this->assertSame(
            $src,
            $this->callPrivate( $this->frontend(), 'generate_content_image_thumbnail', array( $src, 100, 100 ) )
        );
        $this->assertFalse( WPStub::called( 'wp_get_image_editor' ) );
    }

    public function testThumbnailGenerationReturnsSrcWhenLocalFileIsMissing(): void {
        $src = $this->baseurl . '/2024/01/absent.jpg';

        $this->assertSame(
            $src,
            $this->callPrivate( $this->frontend(), 'generate_content_image_thumbnail', array( $src, 100, 100 ) )
        );
    }

    public function testThumbnailGenerationStripsQueryStringBeforeResolvingPath(): void {
        $src = $this->localImage() . '?ver=2#frag';
        WPStub::$image_editor = new \DoneProject_Fake_Image_Editor( array( 'path' => 'thumb', 'create' => true ) );

        $url = $this->callPrivate( $this->frontend(), 'generate_content_image_thumbnail', array( $src, 60, 60 ) );

        $this->assertSame(
            $this->baseurl . '/doneproject-thumbs/' . md5( 'image-bytes' ) . '-60x60.jpg',
            $url
        );
    }

    public function testThumbnailGenerationReusesExistingThumbnail(): void {
        $src = $this->localImage();
        $thumbsDir = $this->basedir . '/doneproject-thumbs';
        mkdir( $thumbsDir, 0777, true );
        $filename = md5( 'image-bytes' ) . '-150x150.jpg';
        file_put_contents( $thumbsDir . '/' . $filename, 'cached' );

        $url = $this->callPrivate( $this->frontend(), 'generate_content_image_thumbnail', array( $src ) );

        $this->assertSame( $this->baseurl . '/doneproject-thumbs/' . $filename, $url );
        $this->assertFalse( WPStub::called( 'wp_get_image_editor' ) );
    }

    public function testThumbnailGenerationFallsBackWhenThumbsDirCannotBeCreated(): void {
        $src = $this->localImage();
        WPStub::$mkdir_p = false;

        $this->assertSame(
            $src,
            $this->callPrivate( $this->frontend(), 'generate_content_image_thumbnail', array( $src ) )
        );
        $this->assertFalse( WPStub::called( 'wp_get_image_editor' ) );
    }

    public function testThumbnailGenerationFallsBackWhenEditorIsUnavailable(): void {
        $src = $this->localImage();
        WPStub::$image_editor = new \WP_Error( 'no_editor', 'No image editor' );

        $this->assertSame(
            $src,
            $this->callPrivate( $this->frontend(), 'generate_content_image_thumbnail', array( $src ) )
        );
    }

    public function testThumbnailGenerationFallsBackWhenSaveFails(): void {
        $src = $this->localImage();
        WPStub::$image_editor = new \DoneProject_Fake_Image_Editor( new \WP_Error( 'save_failed', 'Save failed' ) );

        $this->assertSame(
            $src,
            $this->callPrivate( $this->frontend(), 'generate_content_image_thumbnail', array( $src ) )
        );
        $this->assertCount( 1, WPStub::$image_editor->save_calls );
    }
}

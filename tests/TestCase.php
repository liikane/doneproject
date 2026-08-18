<?php
namespace DoneProject\Tests;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;

abstract class TestCase extends PHPUnitTestCase {
    protected function setUp(): void {
        parent::setUp();
        WPStub::reset();
    }

    protected function tearDown(): void {
        WPStub::reset();
        unset( $GLOBALS['doneproject_current_post'] );
        parent::tearDown();
    }

    /**
     * Plugin double exposing the slug/version the plugin classes read.
     */
    protected function pluginStub( string $slug = 'doneproject-plugin', string $version = '0.1.0' ): object {
        return new class( $slug, $version ) {
            public $slug;
            public $version;

            public function __construct( $slug, $version ) {
                $this->slug = $slug;
                $this->version = $version;
            }
        };
    }

    /**
     * @param mixed[] $args
     * @return mixed
     */
    protected function callPrivate( object $object, string $method, array $args = array() ) {
        $reflection = new \ReflectionMethod( $object, $method );
        $reflection->setAccessible( true );
        return $reflection->invokeArgs( $object, $args );
    }
}

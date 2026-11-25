<?php
namespace DoneProject\Tests;

use DoneProject\Admin;
use DoneProject\Frontend;
use DoneProject\Plugin;
use DoneProject\REST;

class PluginTest extends TestCase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetSingleton();
    }

    protected function tearDown(): void {
        $this->resetSingleton();
        parent::tearDown();
    }

    private function resetSingleton(): void {
        $property = new \ReflectionProperty( Plugin::class, 'instance' );
        $property->setAccessible( true );
        $property->setValue( null, null );
    }

    public function testInstanceReturnsTheSameObject(): void {
        $first = Plugin::instance();
        $second = Plugin::instance();

        $this->assertSame( $first, $second );
    }

    public function testInstanceRunsSetupOnlyOnce(): void {
        Plugin::instance();
        Plugin::instance();

        $this->assertCount( 1, WPStub::callsTo( 'register_activation_hook' ) );
        $this->assertCount( 1, WPStub::callsTo( 'register_deactivation_hook' ) );
    }

    public function testSetupRegistersLifecycleHooks(): void {
        $plugin = Plugin::instance();

        $activation = WPStub::firstCall( 'register_activation_hook' );
        $deactivation = WPStub::firstCall( 'register_deactivation_hook' );

        $this->assertSame( array( $plugin, 'on_activate' ), $activation[1] );
        $this->assertSame( array( $plugin, 'on_deactivate' ), $deactivation[1] );
        $this->assertStringEndsWith( 'doneproject-plugin.php', $activation[0] );
    }

    public function testSetupRegistersInitActions(): void {
        $plugin = Plugin::instance();

        $hooks = array();
        foreach ( WPStub::callsTo( 'add_action' ) as $call ) {
            $hooks[ $call[0] ] = $call[1];
        }

        $this->assertSame( array( $plugin, 'init_admin' ), $hooks['admin_menu'] );
        $this->assertSame( array( $plugin, 'init_frontend' ), $hooks['init'] );
        $this->assertSame( array( $plugin, 'init_rest' ), $hooks['rest_api_init'] );
    }

    public function testDefaultsAreExposed(): void {
        $plugin = Plugin::instance();

        $this->assertSame( 'doneproject-plugin', $plugin->slug );
        $this->assertSame( '0.1.0', $plugin->version );
    }

    public function testOnActivateSetsDefaultGreetingWhenOptionIsMissing(): void {
        $plugin = Plugin::instance();

        $plugin->on_activate();

        $this->assertSame( 'Tere', WPStub::$options['doneproject-plugin_greeting'] );
        $this->assertSame(
            array( 'doneproject-plugin_greeting', 'Tere' ),
            WPStub::firstCall( 'update_option' )
        );
    }

    public function testOnActivateKeepsAnExistingGreeting(): void {
        $plugin = Plugin::instance();
        WPStub::$options['doneproject-plugin_greeting'] = 'Hei';

        $plugin->on_activate();

        $this->assertSame( 'Hei', WPStub::$options['doneproject-plugin_greeting'] );
        $this->assertFalse( WPStub::called( 'update_option' ) );
    }

    public function testOnDeactivateDoesNothing(): void {
        $plugin = Plugin::instance();

        $plugin->on_deactivate();

        $this->assertFalse( WPStub::called( 'update_option' ) );
    }

    public function testInitAdminCreatesAdminOnlyInAdminContext(): void {
        $plugin = Plugin::instance();
        WPStub::$is_admin = false;

        $plugin->init_admin();

        $this->assertFalse( WPStub::called( 'add_options_page' ) );
        $this->assertSame(
            array(),
            array_filter( WPStub::callsTo( 'add_action' ), static function ( $call ) {
                return 'admin_init' === $call[0];
            } )
        );

        WPStub::$is_admin = true;
        $plugin->init_admin();

        $adminInitHooks = array_filter( WPStub::callsTo( 'add_action' ), static function ( $call ) {
            return 'admin_init' === $call[0];
        } );
        $this->assertCount( 1, $adminInitHooks );
        $this->assertInstanceOf( Admin::class, reset( $adminInitHooks )[1][0] );
    }

    public function testInitFrontendRegistersShortcodes(): void {
        $plugin = Plugin::instance();

        $plugin->init_frontend();

        $tags = array_column( WPStub::callsTo( 'add_shortcode' ), 0 );
        $this->assertSame( array( 'doneproject', 'doneproject_pages', 'doneproject_list' ), $tags );
        $this->assertInstanceOf( Frontend::class, WPStub::firstCall( 'add_shortcode' )[1][0] );
    }

    public function testInitRestRegistersRoute(): void {
        $plugin = Plugin::instance();

        $plugin->init_rest();

        $call = WPStub::firstCall( 'register_rest_route' );
        $this->assertSame( 'doneproject/v1', $call[0] );
        $this->assertInstanceOf( REST::class, $call[2]['callback'][0] );
    }
}

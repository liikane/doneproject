<?php
namespace DoneProject\Tests;

use DoneProject\REST;

class RESTTest extends TestCase {
    public function testConstructorRegistersHelloRoute(): void {
        new REST( $this->pluginStub() );

        $call = WPStub::firstCall( 'register_rest_route' );
        $this->assertNotNull( $call );
        $this->assertSame( 'doneproject/v1', $call[0] );
        $this->assertSame( '/hello', $call[1] );
        $this->assertSame( 'GET', $call[2]['methods'] );
        $this->assertSame( '__return_true', $call[2]['permission_callback'] );
        $this->assertSame( 'hello', $call[2]['callback'][1] );
    }

    public function testHelloUsesStoredGreeting(): void {
        WPStub::$options['doneproject-plugin_greeting'] = 'Hei';
        $rest = new REST( $this->pluginStub() );

        $response = $rest->hello( null );

        $this->assertSame( array( 'message' => 'Hei maailm' ), $response );
    }

    public function testHelloFallsBackToDefaultGreeting(): void {
        $rest = new REST( $this->pluginStub() );

        $response = $rest->hello( null );

        $this->assertSame( array( 'message' => 'Tere maailm' ), $response );
        $this->assertSame( array( 'doneproject-plugin_greeting', 'Tere' ), WPStub::firstCall( 'get_option' ) );
    }

    public function testHelloReadsTheOptionOfTheGivenPluginSlug(): void {
        WPStub::$options['other-slug_greeting'] = 'Moi';
        $rest = new REST( $this->pluginStub( 'other-slug' ) );

        $this->assertSame( array( 'message' => 'Moi maailm' ), $rest->hello( null ) );
    }

    public function testHelloPassesResponseThroughRestEnsureResponse(): void {
        $rest = new REST( $this->pluginStub() );

        $rest->hello( null );

        $this->assertCount( 1, WPStub::callsTo( 'rest_ensure_response' ) );
    }
}

<?php
namespace DoneProject\Tests;

use DoneProject\Admin;

class AdminTest extends TestCase {
    public function testConstructorHooksIntoAdminInitAndMenu(): void {
        $admin = new Admin( $this->pluginStub() );

        $hooks = array();
        foreach ( WPStub::callsTo( 'add_action' ) as $call ) {
            $hooks[ $call[0] ] = $call[1];
        }

        $this->assertSame( array( $admin, 'register_settings' ), $hooks['admin_init'] );
        $this->assertSame( array( $admin, 'add_menu' ), $hooks['admin_menu'] );
    }

    public function testAddMenuRegistersOptionsPage(): void {
        $admin = new Admin( $this->pluginStub() );

        $admin->add_menu();

        $this->assertSame(
            array( 'DoneProject Settings', 'DoneProject', 'manage_options', 'doneproject-plugin', array( $admin, 'settings_page' ) ),
            WPStub::firstCall( 'add_options_page' )
        );
    }

    public function testRegisterSettingsRegistersOptionSectionAndField(): void {
        $admin = new Admin( $this->pluginStub() );

        $admin->register_settings();

        $setting = WPStub::firstCall( 'register_setting' );
        $this->assertSame( 'doneproject-plugin_group', $setting[0] );
        $this->assertSame( 'doneproject-plugin_greeting', $setting[1] );
        $this->assertSame(
            array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'default' => 'Tere' ),
            $setting[2]
        );

        $section = WPStub::firstCall( 'add_settings_section' );
        $this->assertSame( 'doneproject-plugin_section', $section[0] );
        $this->assertSame( 'doneproject-plugin', $section[3] );

        $field = WPStub::firstCall( 'add_settings_field' );
        $this->assertSame( 'doneproject-plugin_greeting_field', $field[0] );
        $this->assertSame( array( $admin, 'field_html' ), $field[2] );
        $this->assertSame( 'doneproject-plugin', $field[3] );
        $this->assertSame( 'doneproject-plugin_section', $field[4] );
    }

    public function testFieldHtmlRendersStoredValue(): void {
        WPStub::$options['doneproject-plugin_greeting'] = 'Hei';
        $admin = new Admin( $this->pluginStub() );

        ob_start();
        $admin->field_html();
        $html = ob_get_clean();

        $this->assertSame(
            '<input type="text" name="doneproject-plugin_greeting" value="Hei" class="regular-text" />',
            $html
        );
    }

    public function testFieldHtmlRendersDefaultAndEscapesValue(): void {
        WPStub::$options['doneproject-plugin_greeting'] = 'Tere "sõber" & <b>';
        $admin = new Admin( $this->pluginStub() );

        ob_start();
        $admin->field_html();
        $html = ob_get_clean();

        $this->assertStringContainsString( 'value="Tere &quot;s', $html );
        $this->assertStringNotContainsString( '<b>', $html );
    }

    public function testSettingsPageRendersFormForPermittedUser(): void {
        $admin = new Admin( $this->pluginStub() );

        ob_start();
        $admin->settings_page();
        $html = ob_get_clean();

        $this->assertStringContainsString( 'DoneProject seaded', $html );
        $this->assertStringContainsString( '<form method="post" action="options.php">', $html );
        $this->assertSame( array( 'doneproject-plugin_group' ), WPStub::firstCall( 'settings_fields' ) );
        $this->assertSame( array( 'doneproject-plugin' ), WPStub::firstCall( 'do_settings_sections' ) );
        $this->assertTrue( WPStub::called( 'submit_button' ) );
    }

    public function testSettingsPageRendersNothingWithoutCapability(): void {
        WPStub::$current_user_can = false;
        $admin = new Admin( $this->pluginStub() );

        ob_start();
        $admin->settings_page();
        $html = ob_get_clean();

        $this->assertSame( '', $html );
        $this->assertFalse( WPStub::called( 'settings_fields' ) );
        $this->assertSame( array( 'manage_options' ), WPStub::firstCall( 'current_user_can' ) );
    }
}

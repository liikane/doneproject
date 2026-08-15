=== DoneProject Plugin ===
Contributors: liikane
Tags: example, shortcode, rest
Requires at least: 5.6
Tested up to: 6.4
Stable tag: 0.1.0
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

A lightweight plugin skeleton for DoneProject. Provides a shortcode [doneproject name="..."] that shows a greeting, admin settings to change the greeting and a REST endpoint /wp-json/doneproject/v1/hello.

== Description ==

This plugin is a minimal PSR-4 structured WordPress plugin meant as a starting point for the DoneProject functionality. It includes:

* Admin settings page under Settings -> DoneProject
* Shortcode: [doneproject name="maailm"]
* REST route: /wp-json/doneproject/v1/hello

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/doneproject-plugin` directory (or install via Git).
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Visit Settings -> DoneProject to configure the greeting.


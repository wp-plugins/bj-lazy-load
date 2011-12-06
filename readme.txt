=== BJ Lazy Load ===
Contributors: bjornjohansen
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=NLUWR4SHCJRBJ
Tags: images, lazy loading, jquery, javascript, optimize, performance, bandwidth
Author URI: http://twitter.com/bjornjohansen
Requires at least: 3.2
Tested up to: 3.3
Stable tag: 0.2

Lazy image loading makes your site load faster and saves bandwidth. Uses jQuery and degrades gracefully for non-js users.

== Description ==
Lazy image loading makes your site load faster and saves bandwidth.

This plugin replaces all your post images and post thumbnails with a placeholder and loads images as they enter the browser window when the visitor scrolls the page.

Non-javascript visitors gets the original img element in noscript.

Includes [JqueryAsynchImageLoader Plugin for jQuery by Sebastiano Armeli-Battana](http://www.sebastianoarmelibattana.com/projects/jail) for the real magic.

= Coming soon =
* More options like defining a threshold, loading effects, custom placeholder etc.
* Serving size optimized images for responsive layouts/adaptive designs
* (Got more ideas? Tell me!)

== Installation ==
1. Download and unzip plugin
2. Upload the 'bj-lazyload' folder to the '/wp-content/plugins/' directory,
3. Activate the plugin through the 'Plugins' menu in WordPress.

== Frequently Asked Questions ==

= Whoa, this plugin is using Javascript. What about visitors without JS? =
No worries. They get the original image in a noscript element.

= I'm using a CDN. Will this plugin interfere? =
Nope. The image will load from your CDN.

= The plugin doesn't work/doesn't replace my images =
Your HTML should be standards compliant.

= How can I verify that the plugin is working? =
Check your HTML source or see the magic at work in FireBug or similar.

== Changelog ==

= Version 0.2 =
* Added options panel in admin
* Added option to lazy load post thumbnails
* Skipped the lazy loading in feeds

= Version 0.1 =
* Released 2011-12-05
* It works (or at least it does for me)

== Upgrade Notice ==

= 0.2 =
Lazy load post thumbnails too and stays out of your feeds.




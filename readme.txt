=== BJ Lazy Load ===
Contributors: bjornjohansen
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=NLUWR4SHCJRBJ
Tags: images, iframes, lazy loading, jquery, javascript, optimize, performance, bandwidth
Author URI: http://twitter.com/bjornjohansen
Requires at least: 3.3
Tested up to: 3.4.1
Stable tag: 0.5.0

Lazy loading makes your site load faster and saves bandwidth. Uses jQuery and degrades gracefully for non-js users. Works with both images and iframes.

== Description ==
Lazy loading makes your site load faster and saves bandwidth.

This plugin replaces all your post images, post thumbnails, gravatar images and content iframes with a placeholder and loads the content as it gets close to enter the browser window when the visitor scrolls the page.

You can also lazy load other images and iframes in your theme, by using a simple function.

Non-javascript visitors gets the original element in noscript.

= Coming soon =
* Serving size optimized images for responsive design/adaptive layout
* (Got more ideas? Tell me!)

== Installation ==
1. Download and unzip plugin
2. Upload the 'bj-lazy-load' folder to the '/wp-content/plugins/' directory,
3. Activate the plugin through the 'Plugins' menu in WordPress.

== Optional usage ==
If you have images output in custom templates or want to lazy load other images in your theme, you may filter the HTML through BJLL::filter():
`<?php
$img = '<img src="myimage.jpg" alt="">';
if ( class_exists( 'BJLL' ) ) {
	$img = BJLL::filter( $img );
}
echo $img;
?>` 

== Frequently Asked Questions ==

= Whoa, this plugin is using JavaScript. What about visitors without JS? =
No worries. They get the original element in a noscript element. No Lazy Loading for them, though.

= Which browsers are supported? =
The included JavaScript is tested in Firefox 2+, Safari 3+, Opera 9+, Chrome 5+, Internet Explorer 6+

= I'm using a CDN. Will this plugin interfere? =
Nope. The images will still load from your CDN.

= The plugin doesn't work/doesn't replace my images =
Probably, your theme does not call wp_footer(). Edit the plugin settings to load in wp_head() instead.

= How can I verify that the plugin is working? =
Check your HTML source or see the magic at work in Web Inspector, FireBug or similar.

== Changelog ==

= Version 0.5.0 =
* Complete rewrite
* Replaced JAIL with jQuery.sonar to accomodate for iframe lazy loading
* Added lazy loading for iframes
* The manual filter code now works as it should, lazy loading all images instead of just the first. 

= Version 0.4.0 =
* Upgraded JAIL to version 0.9.9, fixing some bugs. Note: data-href is now renamed data-src.

= Version 0.3.3 =
* Replaced an anonymous function call causing error in PHP < 5.3

= Version 0.3.2 =
* The wp_head caller selector was added to the option page

= Version 0.3.1 =
* Also with d.sturm's fix (thanks)

= Version 0.3.0 =
* Added BJLL::filter() so you can lazy load any images in your theme
* Added the option to load in wp_head() instead (suboptimal, but some themes actually don't call wp_footer())
* Correctly removed the lazy loader from feeds

= Version 0.2.5 =
* Fixes Unicode-issue with filenames

= Version 0.2.4 =
* Now (more) compliant to the WP coding style guidelines.
* All strings localized
* Translations get loaded
* POT file included (send me your translations)
* Norwegian translation included

= Version 0.2.3 =
* Now using DOMDocument for better HTML parsing. Old regexp parsing as fallback if DOMDocument is not available.

= Version 0.2.2 =
* Added CSS. No longer need for hiding .no-js .lazy
* Added options whether to include JS and CSS or not

= Version 0.2.1 =
* Added options: Timeout, effect, speed, event, offset, ignoreHiddenImages
* Combining the two JS files for faster loading
* Renamed the plugin file from bj-lazyload.php to bj-lazy-load.php to better fit with the plugin name

= Version 0.2 =
* Added options panel in admin
* Added option to lazy load post thumbnails
* Skipped the lazy loading in feeds

= Version 0.1 =
* Released 2011-12-05
* It works (or at least it does for me)

== Upgrade Notice ==

= 0.5.0 =
Lazy load images and iframes. Complete rewrite.

= 0.4.0 =
New JAIL version.

= 0.3.2 =
Lazy load any image in your theme. Load in head.

= 0.3.1 =
Lazy load any image in your theme. Load in head.

= 0.3.0 =
Lazy load any image in your theme

= 0.2.5 =
Now works with Unicode filenames

= 0.2.4 =
Better localization

= 0.2.3 =
Improved image replacement

= 0.2.2 =
More options and improved non-JS display.

= 0.2.1 =
More options and faster loading.

= 0.2 =
Lazy load post thumbnails too and stays out of your feeds.




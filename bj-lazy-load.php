<?php
/*
Plugin Name: BJ Lazy Load
Plugin URI: http://wordpress.org/extend/plugins/bj-lazy-load/
Description: Lazy image loading makes your site load faster and saves bandwidth.
Version: 0.4.0
Author: Bjørn Johansen
Author URI: http://twitter.com/bjornjohansen
License: GPL2

    Copyright 2011–2012  Bjørn Johansen  (email : post@bjornjohansen.no)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

*/


class BJLL {

	const version = '0.4.0';
	private $_placeholder_url;
	
	private static $_instance;

	function __construct() {
		
		$this->_placeholder_url = plugins_url( '/img/placeholder.gif', __FILE__ );
		
		if (get_option( 'bjll_include_css', 1 )) {
			add_action( 'wp_print_styles', array($this, 'enqueue_styles' ) );
		}
		
		if (get_option( 'bjll_include_js', 1 )) {
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		}
		
		$theme_caller = get_option( 'bjll_theme_caller' );
		if ( $theme_caller == 'wp_head' ) {
			add_action( 'wp_print_scripts', array( $this, 'output_js_options' ) );
		} else {
			add_action( 'wp_print_footer_scripts', array( $this, 'output_js_options' ) );
		}

		add_action( 'wp_ajax_BJLL_get_images', array( $this, 'get_images_json' ) );
		add_action( 'wp_ajax_nopriv_BJLL_get_images', array( $this, 'get_images_json') );

		add_filter( 'the_content', array( $this, 'filter_post_images' ), 200 );
		
		if ( intval( get_option( 'bjll_filter_post_thumbnails', 1 ) ) ) {
			add_filter( 'post_thumbnail_html', array( $this, 'filter_post_thumbnail_html' ), 10 );
		}
	}
	
	public static function singleton() {
        if (!isset(self::$_instance)) {
            $className = __CLASS__;
            self::$_instance = new $className;
        }
        return self::$_instance;
    }
	
	public function enqueue_styles() {
		wp_enqueue_style( 'BJLL', plugins_url( '/css/bjll.css', __FILE__ ), array(), self::version );
	}

	public function enqueue_scripts() {
	
		$in_footer = true;
		$theme_caller = get_option( 'bjll_theme_caller' );
		if ( $theme_caller == 'wp_head' ) {
			$in_footer = false;
		}
		
		//wp_enqueue_script( 'JAIL', plugins_url( '/js/jail.min.js', __FILE__ ), array( 'jquery'), '0.9.7', true );
		//wp_enqueue_script( 'BJLL', plugins_url( '/js/bjll.js', __FILE__ ), array( 'jquery', 'JAIL' ), self::version, true );
		
		wp_enqueue_script( 'BJLL', plugins_url( '/js/bjll.min.js', __FILE__ ), array( 'jquery' ), self::version, $in_footer );

	} 
	
	public function output_js_options() {
		/*
		wp_localize_script( 'BJLL', 'BJLL', array(
			'timeout' => get_option('bjll_timeout', 10),
			'effect' => get_option('bjll_effect', 'fadeIn'),
			'speed' => get_option('bjll_speed', 400),
			'event' => get_option('bjll_event', 'load+scroll'),
			'callback' => get_option('bjll_callback', ''),
			//'callbackAfterEachImage' => get_option('bjll_callbackAfterEachImage', ''),
			'placeholder' => get_option('bjll_placeholder', ''),
			'offset' => get_option('bjll_offset', 200),
			'ignoreHiddenImages' => get_option('bjll_ignoreHiddenImages', 0),
		) );
		*/
		?>
<script type='text/javascript'>
/* <![CDATA[ */
var BJLL = {
	options: {
		timeout: <?php echo intval( get_option( 'bjll_timeout', 10 ) ); ?>,
		effect: <?php echo ( strlen($val = get_option('bjll_effect', '')) ? '"' . $val . '"' : 'null' ); ?>,
		speed: <?php echo intval( get_option( 'bjll_speed', 400 ) ); ?>,
		event: "<?php echo get_option( 'bjll_event', 'load+scroll' ); ?>",
		callback: "<?php echo get_option( 'bjll_callback', '' ); ?>",
		placeholder: "<?php echo get_option( 'bjll_placeholder', '' ); ?>",
		offset: <?php echo intval( get_option( 'bjll_offset', '' ) ); ?>,
		ignoreHiddenImages: <?php echo intval( get_option( 'bjll_ignoreHiddenImages', 0 ) ); ?>

	},
	ajaxurl: "<?php echo admin_url( 'admin-ajax.php' ); ?>"
};
/* ]]> */
</script>
		<?php
	}

	public function get_images_json() {
		echo json_encode( $_POST['attachmentIDs'] );
		exit;
	}
	
	public function filter_post_images( $content ) {
	
		$matches = array();
		preg_match_all( '/<img\s+.*?>/', $content, $matches );
		
		$search = array();
		$replace = array();
		
		foreach ( $matches[0] as $imgHTML ) {
			
			$replaceHTML = $this->_get_placeholder_html( $imgHTML );
			
			array_push( $search, $imgHTML );
			array_push( $replace, $replaceHTML );
		}
		
		$content = str_replace( $search, $replace, $content );
	
		return $content;
	}
	
	public function filter_post_thumbnail_html( $html ) {

	  $html = $this->_get_placeholder_html( $html );
	  
	  return $html;

	}
	
	static function filter ( $html ) {
		$BJLL = BJLL::singleton();
		return $BJLL->get_placeholder_html ( $html );
	}
	
	public function get_placeholder_html ( $html ) {
		return $this->_get_placeholder_html ( $html );
	}
	
	protected function _get_placeholder_html ( $html ) {
	
		if ( class_exists( 'DOMDocument') ) {
			$html = $this->_get_placeholder_html_dom( $html );
		} else {
			$html = $this->_get_placeholder_html_regexp( $html );
		}
	
		return $html;
	}
	
	protected function _get_placeholder_html_dom ( $html ) {
	
		$loadhtml = sprintf( '<html><head><meta http-equiv="content-type" content="text/html; charset=utf-8"><title></title></head><body>%s</body></html>', $html );
	
		$doc = @DOMDocument::loadHTML( $loadhtml );
		if ( ! $doc ) {
			return $this->_get_placeholder_html_regexp( $html );
		}
		
		$images = $doc->getElementsByTagName( 'img' );
		
		//$img = $images->item( 0 );
		// Thanks to d.sturm for pointing this out: http://wordpress.org/support/topic/plugin-bj-lazy-load?replies=3#post-2539962
		if ( ! $img = $images->item( 0 ) ) {
			return $this->_get_placeholder_html_regexp( $html );
		}
		
		//foreach ( $images as $img ) {
		
			$noscriptImg = $img->cloneNode( true );
			$noscript = $doc->createElement( 'noscript' );
			$noscript->appendChild( $noscriptImg );
		
			$src = $img->getAttribute( 'src' );
			$class = $img->getAttribute( 'class' );
			
			$class .= ' lazy lazy-hidden';
			
			$img->setAttribute( 'data-src' , $src );
			$img->setAttribute( 'src' , $this->_placeholder_url );
			$img->setAttribute( 'class' , trim( $class ) );
			
			$img->parentNode->appendChild( $noscript );
			
		//}
	
		$rethtml = $doc->saveHTML();
		
		$rethtml = substr( $rethtml, strpos( $rethtml, '<body>' ) + 6 );
		$rethtml = substr( $rethtml, 0, strpos( $rethtml, '</body>' ) );
	
		return $rethtml;
	}
	protected function _get_placeholder_html_regexp ( $html ) {
		$orig_html = $html;
		
		/**/
		// replace the src and add the data-src attribute
		$html = preg_replace( '/<img(.*?)src=/i', '<img$1src="' . $this->_placeholder_url . '" data-src=', $html );
		
		// add the lazy class to the img element
		if ( preg_match( '/class="/i', $html ) ) {
			$html = preg_replace( '/class="(.*?)"/i', ' class="lazy lazy-hidden $1"', $html );
		} else {
			$html = preg_replace( '/<img/i', '<img class="lazy lazy-hidden"', $html );
		}
		
		$html .= '<noscript>' . $orig_html . '</noscript>';
		
		
		
		// http://24ways.org/2011/adaptive-images-for-responsive-designs-again
		// This is a no-go. <img> within <noscript> within <a> gets parsed horribly wrong
		//$html = "<script>document.write('<' + '!--')</script><noscript class=\"lazy-nojs\">" . $orig_html . '<noscript -->';
	
		return $html;
	}
	
}

class BJLL_Admin {

	function __construct () {
		add_action( 'init', array( $this, 'load_i18n' ) );
		add_action( 'admin_menu', array( $this, 'plugin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		
		add_filter( 'plugin_action_links', array( $this, 'plugin_settings_link' ), 10, 2);
	}
	
	function load_i18n() {;
		load_plugin_textdomain( 'bj-lazy-load', false, basename( dirname( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'lang' );
	}

	function plugin_menu () {
		add_options_page( 'BJ Lazy Load', 'BJ Lazy Load', 'manage_options', 'bjll', array( $this, 'plugin_options_page' ) );
	}
	
	public static function plugin_settings_link( $links, $file ) {
		
        if ( plugin_basename( __FILE__ ) == $file ) {
			array_unshift($links, '<a href="' . admin_url( 'admin.php' ) . '?page=bjll">' . __( 'Settings' ) . '</a>');
		}

        return $links;
    }
	
	function register_settings () {
		register_setting( 'bjll_options', 'bjll_filter_post_thumbnails', 'intval' );
		register_setting( 'bjll_options', 'bjll_include_js', 'intval' );
		register_setting( 'bjll_options', 'bjll_include_css', 'intval' );
		register_setting( 'bjll_options', 'bjll_theme_caller' );

		add_settings_section( 'bjll_general', __('General'), array( 'BJLL_Admin', 'settings_section_general' ), 'bjll' );
		
		add_settings_field( 'bjll_filter_post_thumbnails', __( 'Lazy load post thumbnails', 'bj-lazy-load' ), array( 'BJLL_Admin', 'setting_field_filter_post_thumbnails' ), 'bjll', 'bjll_general', array( 'label_for' => 'bjll_filter_post_thumbnails' ) );
		add_settings_field( 'bjll_include_js', __( 'Include JS', 'bj-lazy-load' ), array( 'BJLL_Admin', 'setting_field_include_js'), 'bjll', 'bjll_general', array( 'label_for' => 'bjll_include_js' ) );
		add_settings_field( 'bjll_include_css', __( 'Include CSS', 'bj-lazy-load' ), array( 'BJLL_Admin', 'setting_field_include_css'), 'bjll', 'bjll_general', array( 'label_for' => 'bjll_include_css' ) );
		add_settings_field( 'bjll_theme_caller', __( 'Theme caller function', 'bj-lazy-load' ), array('BJLL_Admin', 'setting_field_theme_caller' ), 'bjll', 'bjll_general', array( 'label_for' => 'bjll_theme_caller' ) );
	
		register_setting( 'bjll_options', 'bjll_timeout', 'intval' );
		register_setting( 'bjll_options', 'bjll_effect' );
		register_setting( 'bjll_options', 'bjll_speed', 'intval' );
		register_setting( 'bjll_options', 'bjll_event', array( 'BJLL_Admin', 'sanitize_setting_event' ) );
		register_setting( 'bjll_options', 'bjll_callback' );
		register_setting( 'bjll_options', 'bjll_callbackAfterEachImage' );
		register_setting( 'bjll_options', 'bjll_placeholder' );
		register_setting( 'bjll_options', 'bjll_offset', 'intval' );
		register_setting( 'bjll_options', 'bjll_ignoreHiddenImages', 'intval' );
		
		add_settings_section( 'bjll_loader', __( 'JAIL Settings', 'bj-lazy-load' ), array( 'BJLL_Admin','settings_section_loader' ), 'bjll' );
		
		add_settings_field( 'bjll_timeout', __( 'Timeout', 'bj-lazy-load' ), array( 'BJLL_Admin', 'setting_field_timeout' ), 'bjll', 'bjll_loader', array( 'label_for' => 'bjll_timeout' ) );
		add_settings_field( 'bjll_effect', __( 'jQuery Effect', 'bj-lazy-load' ), array('BJLL_Admin', 'setting_field_effect' ), 'bjll', 'bjll_loader', array( 'label_for' => 'bjll_effect' ) );
		add_settings_field( 'bjll_speed', __( 'Effect Speed', 'bj-lazy-load' ), array('BJLL_Admin', 'setting_field_speed' ), 'bjll', 'bjll_loader', array( 'label_for' => 'bjll_speed' ) );
		add_settings_field( 'bjll_event', __( 'Trigger Event', 'bj-lazy-load' ), array('BJLL_Admin', 'setting_field_event' ), 'bjll', 'bjll_loader', array( 'label_for' => 'bjll_event' ) );
		add_settings_field( 'bjll_offset', __( 'Offset/Threshold', 'bj-lazy-load' ), array('BJLL_Admin', 'setting_field_offset' ), 'bjll', 'bjll_loader', array( 'label_for' => 'bjll_offset' ) );
		add_settings_field( 'bjll_ignoreHiddenImages', __( 'Ignore Hidden Images', 'bj-lazy-load' ), array( 'BJLL_Admin', 'setting_field_ignoreHiddenImages' ), 'bjll', 'bjll_loader', array( 'label_for' => 'bjll_ignoreHiddenImages' ) );
		
	}
	
	function sanitize_setting_theme_caller ( $val ) {
		$validoptions = self::_get_valid_setting_options_event();
		if ( ! in_array( $val, $validoptions ) ) {
			// get previous saved value
			$val = get_option( 'bjll_theme_caller', 'wp_footer' );
			if ( ! in_array( $val, $validoptions ) ) {
				// if still not valid, set to our default
				$val = $validoptions[0];
			}
		}
		return $val;
	}
	function sanitize_setting_event ( $val ) {
		$validoptions = self::_get_valid_setting_options_event();
		if ( ! in_array( $val, $validoptions ) ) {
			// get previous saved value
			$val = get_option( 'bjll_event', 'load+scroll' );
			if ( ! in_array( $val, $validoptions ) ) {
				// if still not valid, set to our default
				$val = $validoptions[0];
			}
		}
		return $val;
	}
	
	function sanitize_setting_effect ( $val ) {
		if ( ! strlen( $val ) ) {
			$val = null;
		}
		return $val;
	}
	
	private static function _get_valid_setting_options_theme_caller () {
		return array( 'wp_footer', 'wp_head' );
	}
	private static function _get_valid_setting_options_event () {
		return array( 'load+scroll', 'load', 'click', 'mouseover', 'scroll' );
	}
	
	
	function settings_section_general () {
	}
	
	function settings_section_loader () {
	}
	
	function setting_field_filter_post_thumbnails () {
		$checked = '';
		if ( intval( get_option( 'bjll_filter_post_thumbnails', 1 ) ) ) {
			$checked = ' checked="checked"';
		}
		
		echo '<input id="bjll_filter_post_thumbnails" name="bjll_filter_post_thumbnails" type="checkbox" value="1" ' . $checked . ' />';
	}
	
	function setting_field_include_js () {
		$checked = '';
		if ( intval( get_option( 'bjll_include_js', 1 ) ) ) {
			$checked = ' checked="checked"';
		}
		
		echo '<input id="bjll_include_js" name="bjll_include_js" type="checkbox" value="1" ' . $checked . ' /> ';
		_e( 'Needed for the plugin to work, but <a href="http://developer.yahoo.com/performance/rules.html#num_http" target="_blank">for best performance you should include it in your combined JS</a>', 'bj-lazy-load' );
	}
	
	function setting_field_include_css () {
		$checked = '';
		if ( intval( get_option( 'bjll_include_css', 1 ) ) ) {
			$checked = ' checked="checked"';
		}
		
		echo '<input id="bjll_include_css" name="bjll_include_css" type="checkbox" value="1" ' . $checked . ' /> '; 
		_e( 'Needed for the plugin to work, but <a href="http://developer.yahoo.com/performance/rules.html#num_http" target="_blank">for best performance you should include it in your combined CSS</a>' , 'bj-lazy-load');
	}
	function setting_field_ignoreHiddenImages () {

		$checked = '';
		if ( intval( get_option( 'bjll_ignoreHiddenImages', 0 ) ) ) {
			$checked = ' checked="checked"';
		}
		
		echo '<input id="bjll_ignoreHiddenImages" name="bjll_ignoreHiddenImages" type="checkbox" value="1" ' . $checked . ' /> ';
		_e( 'Whether to ignore hidden images to be loaded - Default: false/unchecked (so hidden images are loaded)', 'bj-lazy-load' );

	}
	function setting_field_theme_caller () {
		
		$options = self::_get_valid_setting_options_theme_caller();
	
		$currentval = get_option( 'bjll_theme_caller' );
		
		echo '<select id="bjll_theme_caller" name="bjll_theme_caller">';
		foreach ( $options as $option ) {
			$selected = '';
			if ( $option == $currentval ) {
				$selected = ' selected="selected"';
			}
			echo sprintf( '<option value="%1$s"%2$s>%1$s</option>', $option, $selected );
		}
		echo '</select> ';
		_e( 'Put the script in either wp_footer() (should be right before &lt;/body&gt;) or wp_head() (in the &lt;head&gt;-element).', 'bj-lazy-load' );
	}
	function setting_field_event () {
		
		$options = self::_get_valid_setting_options_event();
	
		$currentval = get_option( 'bjll_event' );
		
		echo '<select id="bjll_event" name="bjll_event">';
		foreach ( $options as $option ) {
			$selected = '';
			if ( $option == $currentval ) {
				$selected = ' selected="selected"';
			}
			echo sprintf( '<option value="%1$s"%2$s>%1$s</option>', $option, $selected );
		}
		echo '</select> ';
		_e( 'Event that triggers the image to load. Default: load+scroll', 'bj-lazy-load' );
	}
	function setting_field_timeout () {
		$val = get_option( 'bjll_timeout', 10 );
		echo '<input id="bjll_timeout" name="bjll_timeout" type="text" value="' . $val . '" /> ';
		_e( 'Number of msec after that the images will be loaded - Default: 10', 'bj-lazy-load' );
	}
	function setting_field_effect () {
		$val = get_option( 'bjll_effect', '' );
		if ( 'null' == strtolower( $val ) ) {
			$val = '';
		}
		echo '<input id="bjll_effect" name="bjll_effect" type="text" value="' . $val . '" />';
		_e( 'Any jQuery effect that makes the images display (e.g. "fadeIn") - Default: NULL', 'bj-lazy-load');
		echo '<p>';
		_e( 'NOTE: If you are loading a large number of images, it is best to NOT use this setting. Effects calls are very expensive. Even a simple show() can have a major impact on the browser&rsquo;s responsiveness.', 'bj-lazy-load' );
		echo '</p>';
	}
	function setting_field_speed () {
		$val = get_option( 'bjll_speed', 400 );
		echo '<input id="bjll_speed" name="bjll_speed" type="text" value="' . $val . '" /> ';
		_e( 'string or number determining how long the animation will run - Default: 400', 'bj-lazy-load' );
	}
	function setting_field_offset () {
		$val = get_option( 'bjll_offset', 200 );
		echo '<input id="bjll_offset" name="bjll_offset" type="text" value="' . $val . '" /> ';
		_e( 'An offset of "500" would cause any images that are less than 500px below the bottom of the window or 500px above the top of the window to load. - Default: 200', 'bj-lazy-load' );
	}

	function plugin_options_page () {
		if ( ! current_user_can( 'manage_options' ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.', 'bj-lazy-load' ) );
		}
		?>
		<div class="wrap">
			<h2>BJ Lazy Load <?php _e( 'Settings' ); ?></h2>
			<form method="post" action="options.php">
				<?php settings_fields( 'bjll_options' ); ?>
				<?php do_settings_sections( 'bjll' ); ?>
				
				<p class="submit">
				<input type="submit" class="button-primary" value="<?php _e( 'Save Changes' ) ?>" />
				</p>
			</form>
		</div>
		<?php
	}

}


/*
is_admin() will return true when trying to make an ajax request
if (!is_admin() && !is_feed()) {
*/
/* 'Conditional query tags do not work before the query is run. Before then, they always return false.' */
/* Anonymous functions aren't available before PHP 5.3, so we need a temp wrapper */
function BJLL_action_init () {
	if ( ! is_feed() ) {
		BJLL::singleton() ;
	}
}
add_action( 'wp', 'BJLL_action_init', 10, 0 );


if ( is_admin() ) {
	new BJLL_Admin;
}


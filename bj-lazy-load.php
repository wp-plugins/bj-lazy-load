<?php
/*
Plugin Name: BJ Lazy Load
Plugin URI: http://wordpress.org/extend/plugins/bj-lazy-load/
Description: Lazy image loading makes your site load faster and saves bandwidth.
Version: 0.2.3
Author: Bjørn Johansen
Author URI: http://twitter.com/bjornjohansen
License: GPL2

    Copyright 2011  Bjørn Johansen  (email : post@bjornjohansen.no)

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

	const version = '0.2.3';
	private $_placeholder_url;

	function __construct() {
		
		$this->_placeholder_url = plugins_url('/img/placeholder.gif', __FILE__);
	
		if (get_option('bjll_include_css', 1)) {
			add_action('wp_print_styles', array($this, 'enqueue_styles'));
		}
		
		if (get_option('bjll_include_js', 1)) {
			add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
		}
		
		add_action('wp_print_footer_scripts', array($this, 'output_js_options'));

		add_action( 'wp_ajax_BJLL_get_images', array($this, 'get_images_json') );
		add_action( 'wp_ajax_nopriv_BJLL_get_images', array($this, 'get_images_json') );

		add_filter('the_content', array($this, 'filter_post_images'), 200);
		
		if (intval(get_option('bjll_filter_post_thumbnails', 1))) {
			add_filter( 'post_thumbnail_html', array($this, 'filter_post_thumbnail_html'), 10 );
		}
	}
	
	public function enqueue_styles() {
		wp_enqueue_style( 'BJLL', plugins_url('/css/bjll.css', __FILE__), array(), self::version);
	}

	public function enqueue_scripts() {
		
		//wp_enqueue_script('JAIL', plugins_url('/js/jail.min.js', __FILE__), array('jquery'), '0.9.7', true);
		//wp_enqueue_script( 'BJLL', plugins_url('/js/bjll.js', __FILE__), array( 'jquery', 'JAIL' ), self::version, true );
		
		wp_enqueue_script( 'BJLL', plugins_url('/js/bjll.min.js', __FILE__), array( 'jquery' ), self::version, true );

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
		timeout: <?php echo intval(get_option('bjll_timeout', 10)); ?>,
		effect: <?php echo (strlen($val = get_option('bjll_effect', '')) ? '"'.$val.'"' : 'null'); ?>,
		speed: <?php echo intval(get_option('bjll_speed', 400)); ?>,
		event: "<?php echo get_option('bjll_event', 'load+scroll'); ?>",
		callback: "<?php echo get_option('bjll_callback', ''); ?>",
		placeholder: "<?php echo get_option('bjll_placeholder', ''); ?>",
		offset: <?php echo intval(get_option('bjll_offset', '')); ?>,
		ignoreHiddenImages: <?php echo intval(get_option('bjll_ignoreHiddenImages', 0)); ?>

	},
	ajaxurl: "<?php echo admin_url( 'admin-ajax.php' ); ?>"
};
/* ]]> */
</script>
		<?php
	}

	public function get_images_json() {
		echo json_encode($_POST['attachmentIDs']);
		exit;
	}
	
	public function filter_post_images($content) {
	
		$matches = array();
		preg_match_all('/<img\s+.*?>/', $content, $matches);
		
		$search = array();
		$replace = array();
		
		foreach ($matches[0] as $imgHTML) {
			
			$replaceHTML = $this->_get_placeholder_html($imgHTML);
			
			array_push($search, $imgHTML);
			array_push($replace, $replaceHTML);
		}
		
		$content = str_replace($search, $replace, $content);
	
		return $content;
	}
	
	public function filter_post_thumbnail_html( $html ) {

	  $html = $this->_get_placeholder_html($html);
	  
	  return $html;

	}
	
	protected function _get_placeholder_html ($html) {
	
		if (class_exists('DOMDocument')) {
			$html = $this->_get_placeholder_html_dom($html);
		} else {
			$html = $this->_get_placeholder_html_regexp($html);
		}
	
		return $html;
	}
	
	protected function _get_placeholder_html_dom ($html) {
	
		$doc = DOMDocument::loadHTML($html);
		if (!$doc) {
			return $this->_get_placeholder_html_regexp($html);
		}
		
		$images = $doc->getElementsByTagName('img');
		
		$img = $images->item(0);
		
		//foreach ($images as $img) {
		
			$noscriptImg = $img->cloneNode(true);
			$noscript = $doc->createElement('noscript');
			$noscript->appendChild($noscriptImg);
		
			$src = $img->getAttribute('src');
			$class = $img->getAttribute('class');
			
			$class .= ' lazy lazy-hidden';
			
			$img->setAttribute( 'data-href' , $src );
			$img->setAttribute( 'src' , $this->_placeholder_url );
			$img->setAttribute( 'class' , trim($class) );
			
			$img->parentNode->appendChild($noscript);
			
		//}
	
		$rethtml = $doc->saveHTML();
		
		$rethtml = substr($rethtml, strpos($rethtml, '<body>') + 6);
		$rethtml = substr($rethtml, 0, strpos($rethtml, '</body>'));
	
		return $rethtml;
	}
	protected function _get_placeholder_html_regexp ($html) {
		$orig_html = $html;
		
		/**/
		// replace the src and add the data-href attribute
		$html = preg_replace( '/<img(.*?)src=/i', '<img$1src="'.$this->_placeholder_url.'" data-href=', $html );
		
		// add the lazy class to the img element
		if (preg_match('/class="/i', $html)) {
			$html = preg_replace('/class="(.*?)"/i', ' class="lazy lazy-hidden $1"', $html);
		} else {
			$html = preg_replace('/<img/i', '<img class="lazy lazy-hidden"', $html);
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
		add_action('admin_menu', array($this, 'plugin_menu'));
		add_action('admin_init', array($this, 'register_settings'));
	}

	function plugin_menu() {
		add_options_page('BJ Lazy Load', 'BJ Lazy Load', 'manage_options', 'bjll', array($this, 'plugin_options_page'));
	}
	
	function register_settings() {
		register_setting( 'bjll_options', 'bjll_filter_post_thumbnails', 'intval' );
		register_setting( 'bjll_options', 'bjll_include_js', 'intval' );
		register_setting( 'bjll_options', 'bjll_include_css', 'intval' );

		add_settings_section('bjll_general', __('General'), array('BJLL_Admin','settings_section_general'), 'bjll');
		
		add_settings_field('bjll_filter_post_thumbnails', __('Lazy load post thumbnails'), array('BJLL_Admin', 'setting_field_filter_post_thumbnails'), 'bjll', 'bjll_general', array('label_for' => 'bjll_filter_post_thumbnails'));
		add_settings_field('bjll_include_js', __('Include JS'), array('BJLL_Admin', 'setting_field_include_js'), 'bjll', 'bjll_general', array('label_for' => 'bjll_include_js'));
		add_settings_field('bjll_include_css', __('Include CSS'), array('BJLL_Admin', 'setting_field_include_css'), 'bjll', 'bjll_general', array('label_for' => 'bjll_include_css'));
	
		register_setting( 'bjll_options', 'bjll_timeout', 'intval' );
		register_setting( 'bjll_options', 'bjll_effect' );
		register_setting( 'bjll_options', 'bjll_speed', 'intval' );
		register_setting( 'bjll_options', 'bjll_event', array('BJLL_Admin', 'sanitize_setting_event') );
		register_setting( 'bjll_options', 'bjll_callback' );
		register_setting( 'bjll_options', 'bjll_callbackAfterEachImage' );
		register_setting( 'bjll_options', 'bjll_placeholder' );
		register_setting( 'bjll_options', 'bjll_offset', 'intval' );
		register_setting( 'bjll_options', 'bjll_ignoreHiddenImages', 'intval' );
		
		add_settings_section('bjll_loader', __('Loader'), array('BJLL_Admin','settings_section_loader'), 'bjll');
		
		add_settings_field('bjll_timeout', __('Timeout'), array('BJLL_Admin', 'setting_field_timeout'), 'bjll', 'bjll_loader', array('label_for' => 'bjll_timeout'));
		add_settings_field('bjll_effect', __('jQuery Effect'), array('BJLL_Admin', 'setting_field_effect'), 'bjll', 'bjll_loader', array('label_for' => 'bjll_effect'));
		add_settings_field('bjll_speed', __('Effect Speed'), array('BJLL_Admin', 'setting_field_speed'), 'bjll', 'bjll_loader', array('label_for' => 'bjll_speed'));
		add_settings_field('bjll_event', __('Trigger Event'), array('BJLL_Admin', 'setting_field_event'), 'bjll', 'bjll_loader', array('label_for' => 'bjll_event'));
		add_settings_field('bjll_offset', __('Offset/Threshold'), array('BJLL_Admin', 'setting_field_offset'), 'bjll', 'bjll_loader', array('label_for' => 'bjll_offset'));
		add_settings_field('bjll_ignoreHiddenImages', __('Ignore Hidden Images'), array('BJLL_Admin', 'setting_field_ignoreHiddenImages'), 'bjll', 'bjll_loader', array('label_for' => 'bjll_ignoreHiddenImages'));
		
	}
	
	function sanitize_setting_event ($val) {
		$validoptions = self::_get_valid_setting_options_event();
		if (!in_array($val, $validoptions)) {
			// get previous saved value
			$val = get_option('bjll_event', 'load+scroll');
			if (!in_array($val, $validoptions)) {
				// if still not valid, set to our default
				$val = $validoptions[0];
			}
		}
		return $val;
	}
	
	function sanitize_setting_effect ($val) {
		if (!strlen($val)) {
			$val = null;
		}
		return $val;
	}
	
	private static function _get_valid_setting_options_event() {
		return array('load+scroll', 'load', 'click', 'mouseover', 'scroll');
	}
	
	
	function settings_section_general() {
	}
	
	function settings_section_loader() {
	}
	
	function setting_field_filter_post_thumbnails() {
		$checked = '';
		if (intval(get_option('bjll_filter_post_thumbnails', 1))) {
			$checked = ' checked="checked"';
		}
		
		echo '<input id="bjll_filter_post_thumbnails" name="bjll_filter_post_thumbnails" type="checkbox" value="1" ' . $checked . ' />';
	}
	function setting_field_include_js() {
		$checked = '';
		if (intval(get_option('bjll_include_js', 1))) {
			$checked = ' checked="checked"';
		}
		
		echo '<input id="bjll_include_js" name="bjll_include_js" type="checkbox" value="1" ' . $checked . ' /> Needed for the plugin to work, but <a href="http://developer.yahoo.com/performance/rules.html#num_http" target="_blank">for best performance you should include it in your combined JS</a>';
	}
	function setting_field_include_css() {
		$checked = '';
		if (intval(get_option('bjll_include_css', 1))) {
			$checked = ' checked="checked"';
		}
		
		echo '<input id="bjll_include_css" name="bjll_include_css" type="checkbox" value="1" ' . $checked . ' /> Needed for the plugin to work, but <a href="http://developer.yahoo.com/performance/rules.html#num_http" target="_blank">for best performance you should include it in your combined CSS</a>';
	}
	function setting_field_ignoreHiddenImages() {

		$checked = '';
		if (intval(get_option('bjll_ignoreHiddenImages', 0))) {
			$checked = ' checked="checked"';
		}
		
		echo '<input id="bjll_ignoreHiddenImages" name="bjll_ignoreHiddenImages" type="checkbox" value="1" ' . $checked . ' /> whether to ignore hidden images to be loaded - Default: false/unchecked (so hidden images are loaded)';

	}
	function setting_field_event() {
		
		$options = self::_get_valid_setting_options_event();
	
		$currentval = get_option('bjll_event');
		
		echo '<select id="bjll_event" name="bjll_event" type="checkbox">';
		foreach ($options as $option) {
			$selected = '';
			if ($option == $currentval) {
				$selected = ' selected="selected"';
			}
			echo sprintf('<option value="%1$s"%2$s>%1$s</option>', $option, $selected);
		}
		echo '</select> event that triggers the image to load. Default: load+scroll';
	}
	function setting_field_timeout() {
		$val = get_option('bjll_timeout', 10);
		echo '<input id="bjll_timeout" name="bjll_timeout" type="text" value="' . $val . '" /> number of msec after that the images will be loaded - Default: 10';
	}
	function setting_field_effect() {
		$val = get_option('bjll_effect', '');
		if (strtolower($val) == 'null') {
			$val = '';
		}
		echo '<input id="bjll_effect" name="bjll_effect" type="text" value="' . $val . '" /> any jQuery effect that makes the images display (e.g. "fadeIn") - Default: NULL<p>NOTE: If you are loading a large number of images, it is best to NOT use this setting. Effects calls are very expensive. Even a simple show() can have a major impact on the browser&rsquo;s responsiveness.</p>';
	}
	function setting_field_speed() {
		$val = get_option('bjll_speed', 400);
		echo '<input id="bjll_speed" name="bjll_speed" type="text" value="' . $val . '" /> string or number determining how long the animation will run - Default: 400';
	}
	function setting_field_offset() {
		$val = get_option('bjll_offset', 200);
		echo '<input id="bjll_offset" name="bjll_offset" type="text" value="' . $val . '" /> an offset of "500" would cause any images that are less than 500px below the bottom of the window or 500px above the top of the window to load. - Default: 200';
	}

	function plugin_options_page() {
		if (!current_user_can('manage_options'))  {
			wp_die( __('You do not have sufficient permissions to access this page.') );
		}
		?>
		<div class="wrap">
			<h2>BJ Lazy Load <?php _e('Settings'); ?></h2>
			<form method="post" action="options.php">
				<?php settings_fields('bjll_options'); ?>
				<?php do_settings_sections('bjll'); ?>
				
				<p class="submit">
				<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
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
if (!is_feed()) {
	new BJLL;
}

if (is_admin()) {
	new BJLL_Admin;
}


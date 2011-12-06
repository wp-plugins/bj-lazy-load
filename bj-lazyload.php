<?php
/*
Plugin Name: BJ Lazy Load
Plugin URI: http://wordpress.org/extend/plugins/bj-lazy-load/
Description: Lazy image loading makes your site load faster and saves bandwidth.
Version: 0.2
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

	private $_placeholder_url;

	function __construct() {
		
		$this->_placeholder_url = plugins_url('/img/placeholder.gif', __FILE__);
	
		add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));

		add_action( 'wp_ajax_BJLL_get_images', array($this, 'get_images_json') );
		add_action( 'wp_ajax_nopriv_BJLL_get_images', array($this, 'get_images_json') );

		add_filter('the_content', array($this, 'filter_post_images'), 200);
		
		if (intval(get_option('bjll_filter_post_thumbnails', 1))) {
			add_filter( 'post_thumbnail_html', array($this, 'filter_post_thumbnail_html'), 10 );
		}
	}

	public function enqueue_scripts() {
		wp_enqueue_script('JAIL', plugins_url('/js/jail.min.js', __FILE__), array('jquery'), '0.9.7', true);
		
		wp_enqueue_script( 'BJLL', plugins_url('/js/bjll.js', __FILE__), array( 'jquery', 'JAIL' ), '0.1', true );
		
		/* We don't need this (yet)
		wp_localize_script( 'BJLL', 'BJLL', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
		*/
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
	
		$orig_html = $html;
			
		// replace the src and add the data-href attribute
		$html = preg_replace( '/<img(.*?)src=/i', '<img$1src="'.$this->_placeholder_url.'" data-href=', $html );
		
		// add the lazy class to the img element
		if (preg_match('/class="/i', $html)) {
			$html = preg_replace('/class="(.*?)"/i', ' class="lazy $1"', $html);
		} else {
			$html = preg_replace('/<img/i', '<img class="lazy"', $html);
		}
		
		$html .= '<noscript>' . $orig_html . '</noscript>';
	
		return $html;
	}
	
}

class BJLL_Admin {

	function __construct () {
		add_action('admin_menu', array($this, 'plugin_menu'));
		add_action( 'admin_init', array($this, 'register_settings'));
	}

	function plugin_menu() {
		add_options_page('BJ Lazy Load', 'BJ Lazy Load', 'manage_options', 'bjll', array($this, 'plugin_options_page'));
	}
	
	function register_settings() {
		//register_setting( $option_group, $option_name, $sanitize_callback );
		register_setting( 'bjll_options', 'bjll_filter_post_thumbnails', 'intval' );

		//add_settings_section( $id, $title, $callback, $page );
		add_settings_section('bjll_general', __('General'), array('BJLL_Admin','settings_section_general'), 'bjll');
		
		//add_settings_field( $id, $title, $callback, $page, $section, $args );
		add_settings_field('bjll_filter_post_thumbnails', __('Lazy load post thumbnails'), array('BJLL_Admin', 'setting_field_filter_post_thumbnails'), 'bjll', 'bjll_general');

		
	}
	
	function settings_section_general() {
	}
	
	function setting_field_filter_post_thumbnails() {

		$checked = '';
		if (intval(get_option('bjll_filter_post_thumbnails', 1))) {
			$checked = ' checked="checked"';
		}
		
		echo '<input id="bjll_filter_post_thumbnails" name="bjll_filter_post_thumbnails" type="checkbox" value="1" ' . $checked . ' />';

	}

	function plugin_options_page() {
		if (!current_user_can('manage_options'))  {
			wp_die( __('You do not have sufficient permissions to access this page.') );
		}
		?>
		<div class="wrap">
			<h2>BJ Lazy Load <?php _e('Settings'); ?></h2>
			<p><?php _e('More settings will be available in the near future.'); ?></p>
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


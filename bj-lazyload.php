<?php
/*
Plugin Name: BJ Lazy Load
Plugin URI: http://wordpress.org/extend/plugins/bj-lazy-load/
Description: Lazy image loading makes your site load faster and saves bandwidth.
Version: 0.1
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

	function enqueue_scripts() {
		wp_enqueue_script('JAIL', plugins_url('/js/jail.min.js', __FILE__), array('jquery'), '0.9.7', true);
		
		wp_enqueue_script( 'BJLL', plugins_url('/js/bjll.js', __FILE__), array( 'jquery', 'JAIL' ), '0.1', true );
		
		/* We don't need this (yet)
		wp_localize_script( 'BJLL', 'BJLL', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
		*/
	} 

	function get_images_json() {
		echo json_encode($_POST['attachmentIDs']);
		exit;
	}
	
	function filter_post_images($content) {
	
		$placeholder_url = plugins_url('/img/placeholder.gif', __FILE__);
	
		$matches = array();
		preg_match_all('/<img\s+.*?>/', $content, $matches);
		
		$search = array();
		$replace = array();
		
		foreach ($matches[0] as $imgHTML) {
			$replaceHTML = $imgHTML;
			
			// replace the src and add the data-href attribute
			$replaceHTML = preg_replace( '/<img(.*?)src=/i', '<img$1src="'.$placeholder_url.'" data-href=', $replaceHTML );
			
			// add the lazy class to the img element
			if (preg_match('/class="/i', $replaceHTML)) {
				$replaceHTML = preg_replace('/class="(.*?)"/i', ' class="lazy $1"', $replaceHTML);
			} else {
				$replaceHTML = preg_replace('/<img/i', '<img class="lazy"', $replaceHTML);
			}
			
			$replaceHTML .= '<noscript>' . $imgHTML . '</noscript>';
			
			array_push($search, $imgHTML);
			array_push($replace, $replaceHTML);
		}
		
		$content = str_replace($search, $replace, $content);
	
		return $content;
	}
	
}

add_action('wp_enqueue_scripts', array('BJLL', 'enqueue_scripts'));

add_action( 'wp_ajax_BJLL_get_images', array('BJLL', 'get_images_json') );
add_action( 'wp_ajax_nopriv_BJLL_get_images', array('BJLL', 'get_images_json') );

add_filter('the_content', array('BJLL', 'filter_post_images'), 200);


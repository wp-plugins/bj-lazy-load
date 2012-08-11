<?php
/*
Plugin Name: BJ Lazy Load
Plugin URI: http://wordpress.org/extend/plugins/bj-lazy-load/
Description: Lazy image loading makes your site load faster and saves bandwidth.
Version: 0.5.3
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


require_once( dirname(__FILE__) . '/scb/load.php' );

if ( ! class_exists( 'BJLL' ) ) {
	class BJLL {

		const version = '0.5.3';
		protected $_placeholder_url;
		
		protected static $_instance;

		function __construct() {
		
			$placeholder_url = plugins_url( '/img/placeholder.gif', __FILE__ );
			$placeholder_url = apply_filters('bj_lazy_load_placeholder_url', $placeholder_url);
			$this->_placeholder_url = $placeholder_url;
		
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
			
			$options = self::_get_options();
			
			
			if ( $options->get('filter_content') == 'yes' ) {
				add_filter( 'the_content', array( $this, 'filter' ), 200 );
			}
			if ( $options->get('filter_post_thumbnails') == 'yes' ) {
				add_filter( 'post_thumbnail_html', array( $this, 'filter' ), 200 );
			}
			if ( $options->get('filter_gravatars') == 'yes' ) {
				add_filter( 'get_avatar', array( $this, 'filter' ), 200 );
			}
		}
		
		static function singleton() {
			if ( ! isset( self::$_instance ) ) {
				$className = __CLASS__;
				self::$_instance = new $className;
			}
			return self::$_instance;
		}
		
		static function enqueue_scripts() {
		
			$in_footer = true;
			
			$options = self::_get_options();
			$theme_loader_function = $options->get( 'theme_loader_function' );
			
			if ( $theme_loader_function == 'wp_head' ) {
				$in_footer = false;
			}
			
			wp_enqueue_script( 'jquery.sonar', plugins_url( '/js/jquery.sonar.min.js', __FILE__ ), array( 'jquery' ), self::version, $in_footer );
			wp_enqueue_script( 'BJLL', plugins_url( '/js/bj-lazy-load.min.js', __FILE__ ), array( 'jquery', 'jquery.sonar' ), self::version, $in_footer );
		}
		
		static function filter( $content ) {
		
			$BJLL = BJLL::singleton();
			
			$options = self::_get_options();
			
			if ( $options->get('lazy_load_images') == 'yes' ) {
				$content = $BJLL->_filter_images( $content );
			}
			
			if ( $options->get('lazy_load_iframes') == 'yes' ) {
				$content = $BJLL->_filter_iframes( $content );
			}
		
			return $content;
		}
		
		protected function _filter_images( $content ) {
		
			$matches = array();
			preg_match_all( '/<img\s+.*?>/', $content, $matches );
			
			$search = array();
			$replace = array();
			
			foreach ( $matches[0] as $imgHTML ) {
				
				// replace the src and add the data-src attribute
				$replaceHTML = preg_replace( '/<img(.*?)src=/i', '<img$1src="' . $this->_placeholder_url . '" data-lazy-type="image" data-lazy-src=', $imgHTML );
				
				// add the lazy class to the img element
				if ( preg_match( '/class="/i', $replaceHTML ) ) {
					$replaceHTML = preg_replace( '/class="(.*?)"/i', 'class="lazy lazy-hidden $1"', $replaceHTML );
				} else {
					$replaceHTML = preg_replace( '/<img/i', '<img class="lazy lazy-hidden"', $replaceHTML );
				}
				
				$replaceHTML .= '<noscript>' . $imgHTML . '</noscript>';
				
				array_push( $search, $imgHTML );
				array_push( $replace, $replaceHTML );
			}
			
			$content = str_replace( $search, $replace, $content );
		
		
			return $content;
		}
		
		protected function _filter_iframes( $content ) {
		
			$matches = array();
			preg_match_all( '/<iframe\s+.*?>/', $content, $matches );
			
			$search = array();
			$replace = array();
			
			foreach ( $matches[0] as $iframeHTML ) {
				$replaceHTML = '<img src="' . $this->_placeholder_url . '"  class="lazy lazy-hidden" data-lazy-type="iframe" data-lazy-src="' . base64_encode($iframeHTML) . '" alt="">';
				
				$replaceHTML .= '<noscript>' . $iframeHTML . '</noscript>';
				
				array_push( $search, $iframeHTML );
				array_push( $replace, $replaceHTML );
			}
		
			$content = str_replace( $search, $replace, $content );
			
			return $content;
		}
		
		protected function _get_options() {
			return new scbOptions( 'bj_lazy_load_options', __FILE__, array(
				'filter_content'          => 'yes',
				'filter_post_thumbnails'  => 'yes',
				'filter_gravatars'        => 'yes',
				'lazy_load_images'        => 'yes',
				'lazy_load_iframes'       => 'yes',
				'theme_loader_function'   => 'wp_footer'
			) );
		}
		
		function options_init() {
		
			$options = self::_get_options();

			// Creating settings page objects
			if ( is_admin() ) {
				require_once( dirname( __FILE__ ) . '/admin.php' );
				new BJLL_Admin_Page( __FILE__, $options );
			}
		}
		
	}
}


add_action( 'wp', create_function('', 'if ( ! is_feed() ) { BJLL::singleton(); }'), 10, 0 );

scb_init( array( 'BJLL', 'options_init' ) );


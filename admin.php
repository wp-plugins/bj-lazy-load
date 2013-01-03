<?php

class BJLL_Admin_Page extends scbAdminPage {

	function setup() {
		$this->args = array(
			'menu_title' => 'BJ Lazy Load',
			'page_title' => __( 'BJ Lazy Load Options', 'bj_lazy_load' ),
		);
	}
	
	function page_content() {
		
		
		echo $this->form_table( array(
			array(
				'title' => __( 'Apply to content', 'bj_lazy_load' ),
				'type' => 'radio',
				'name' => 'filter_content',
				'value' => array( 'yes' => __('Yes', 'bj_lazy_load'), 'no' => __('No', 'bj_lazy_load') ),
			),
			array(
				'title' => __( 'Apply to post thumbnails', 'bj_lazy_load' ),
				'type' => 'radio',
				'name' => 'filter_post_thumbnails',
				'value' => array( 'yes' => __('Yes', 'bj_lazy_load'), 'no' => __('No', 'bj_lazy_load') ),
			),
			array(
				'title' => __( 'Apply to gravatars', 'bj_lazy_load' ),
				'type' => 'radio',
				'name' => 'filter_gravatars',
				'value' => array( 'yes' => __('Yes', 'bj_lazy_load'), 'no' => __('No', 'bj_lazy_load') ),
			),
			array(
				'title' => __( 'Lazy load images', 'bj_lazy_load' ),
				'type' => 'radio',
				'name' => 'lazy_load_images',
				'value' => array( 'yes' => __('Yes', 'bj_lazy_load'), 'no' => __('No', 'bj_lazy_load') ),
			),
			array(
				'title' => __( 'Lazy load iframes', 'bj_lazy_load' ),
				'type' => 'radio',
				'name' => 'lazy_load_iframes',
				'value' => array( 'yes' => __('Yes', 'bj_lazy_load'), 'no' => __('No', 'bj_lazy_load') ),
			),
			array(
				'title' => __( 'Theme loader function', 'bj_lazy_load' ),
				'type' => 'select',
				'name' => 'theme_loader_function',
				'value' => array( 'wp_footer', 'wp_head' ),
			),
			array(
				'title' => __( 'Placeholder Image URL', 'bj_lazy_load' ),
				'type' => 'text',
				'name' => 'placeholder_url',
				'desc' => __( 'Leave blank for default', 'bj_lazy_load' ),
			),
			array(
				'title' => __( 'Skip images with classes', 'bj_lazy_load' ),
				'type' => 'text',
				'name' => 'skip_classes',
				'desc' => __( 'Comma separated. Example: "no-lazy, lazy-ignore, image-235"', 'bj_lazy_load' ),
			),
		) );
		
	}

}

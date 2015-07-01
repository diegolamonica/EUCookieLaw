<?php
class EUCookieLawTinyMCE{
	const VERSION = '2.2.0';

	function EUCookieLawTinyMCE(){

		add_action('admin_head', array($this,'adminHead'));
		add_action( 'admin_enqueue_scripts', array( $this, 'adminScripts' ) );

	}

	function adminScripts(){
		wp_register_style(__CLASS__, plugins_url('eucookielaw-tinymce.css', __FILE__ ), array(), self::VERSION, 'screen');
		wp_enqueue_style( __CLASS__ );
	}
	function adminHead(){
		if(current_user_can('edit_pages') || current_user_can('edit_posts')){
			if ( get_user_option('rich_editing') == 'true') {
				add_filter("mce_external_plugins", array($this,"addTinymcePlugin"));
				add_filter('mce_buttons', array($this,'registerButtons'));
				add_editor_style( plugins_url('eucookielaw-tinymce.css', __FILE__ ) );

			}
		}
	}
	function addTinymcePlugin($plugins){
		$plugins['eucookielaw'] = plugins_url( '/EUCookieLaw-tinymce.js', __FILE__ );
		return $plugins;
	}

	function registerButtons($buttons){

		$buttons[] = "eucookielaw";

		return $buttons;
	}
}
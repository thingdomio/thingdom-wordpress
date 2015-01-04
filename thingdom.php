<?php

/* 
Plugin Name: Thingdom
Plugin URI: https://www.github.com/thingdomio/thingdom-wordpress/ 
Description: Integrates your WordPress back-end with the Thingdom API.
Version: 1.0
Author: Nicholas Kreidberg 
Author URI: http://niczak.com
*/

defined( 'ABSPATH' ) or die( 'Plugin file cannot be accessed directly.' );

require_once('thingdomAPI.php');

if( !class_exists( 'ThingdomWP' )) {
	class ThingdomWP {

		/**
		 * Display (user friendly) name to identify plugin.
		 * @var string
		 */

		protected $name = 'Thingdom WordPress';

		/**
		 * List of options displayed on the settings page.
		 * @var array
		 */				

		protected $options = array(
			'thingName' => array(
				'label'			=> 'Thingdom thing name',
				'placeholder'	=> 'Thing Name',
				'type'			=> 'text',
				'default'		=> ''
			),
			'productType' => array(
				'label'	 		=> 'Thingdom product type',
				'placeholder'	=> 'Product Type',
				'type'			=> 'text',
				'default'		=> ''
			),						
			'pages' => array(
				'label'			=> 'New page alerts',
				'type'			=> 'checkbox',
				'default'		=> 1
			),
			'posts' => array(
				'label'			=> 'New post alerts',
				'type'			=> 'checkbox',
				'default'		=> 1
			)
		);		

		/**
		 * Slug used for admin menus.
		 * @var string
		 */

		protected $slug = 'thingdom-settings-admin';

		/**
		 * Tag identifier used by file includes and selector attributes.
		 * @var string
		 */

		protected $tag = 'thingdom-wp';

		/**
		 * Thingdom "Thing Name" used for all calls to Thingdom.
		 * @var string
		 */

		protected $thingName;

		/**
		 * Thingdom "Product Type" used for all calls to Thingdom.
		 * @var string
		 */

		protected $thingType;

		/**
		 * Display (user friendly) name to identify plugin menu title.
		 * @var string
		 */

		protected $title = 'Thingdom WordPress Settings';

		/**
		 * Current plugin version.
		 * @var string
		 */

		protected $version = '1.0';


		/**
		 * Initiate plugin by getting options and setting instance variables.
		 *
		 * @access public
		 */

		public function __construct()
		{
			$this->thingName = get_option($this->tag.'_'.'thingName');
			$this->thingType = get_option($this->tag.'_'.'productType');

			if ( is_admin() ) {
				add_action('admin_menu', array($this, 'registerMenu'));

				if(!empty($this->thingName) && !empty($this->thingType)) {
					// register all Thingdom-specific admin actions 
					add_action('publish_post', array($this, 'thingdomPost'), 10, 2);
				}
			}

			if(!empty($this->thingName) && !empty($this->thingType)) {
				// register all Thingdom-specific non-admin actions
				add_action('comment_post', array($this, 'thingdomComment'), 10, 2 );	
			}
			
		}

		/**
		 * Method triggered whenever a page/post is published.
		 *
		 * @access public
		 * @param int $post_id
		 * @param array $post
		 */		

		public function thingdomPost($post_id) 
		{
			try {
				$thingdom = new Thingdom();
			} catch (Exception $ex) {
				error_log( json_encode($ex->getMessage()) );
				return false;
			}

			$post_title = get_the_title($post_id);

			$thing = $thingdom->getThing($this->thingName, $this->thingType);

			if($post->post_type == 'post') {
				$thing->feed('new_post', "New Post: $post_title");

			} else if($post->post_type == 'page') {
				$thing->feed('new_page', "New Page: $post_title");
			}
		}

		/**
		 * Method triggered whenever a comment is posted to the site.
		 *
		 * @access public
		 * @param int $post_id
		 * @param array $post
		 */				

		public function thingdomComment($post_id) 
		{
			try {
				$thingdom = new Thingdom();
			} catch (Exception $ex) {
				error_log( json_encode($ex->getMessage()) );
				return false;
			}

			$comment = get_comments(array('ID' => $post_id));
			$post_title = get_the_title($post_id);

			$thing = $thingdom->getThing($this->thingName, $this->thingType);

			$thing->feed('new_comment', "New comment on: {$post_title}\n<br>From: {$comment[0]->comment_author}<br>\nComment: {$comment[0]->comment_content}");
		}

		/**
		 * Admin menu action method to add options page and call initialize settings.
		 *
		 * @access public
		 */		

		public function registerMenu()
		{
			add_options_page( 'Thingdom Settings', 'Thingdom Settings', 'manage_options', $this->slug, array($this, 'loadSettings')); 
			add_action('admin_init', array($this, 'registerSettings'));
		}

		/**
		 * Register all setting options using instance array to initialize.
		 *
		 * @access public
		 */		

		public function registerSettings()
		{
			foreach($this->options as $id => $options) {
				register_setting('thingdom-options', $this->tag.'_'.$id);
			}
		}

		/**
		 * Load settings view.
		 *
		 * @access public
		 */

		public function loadSettings()
		{
			include('settings.php');
		}

	}

    new ThingdomWP();
}

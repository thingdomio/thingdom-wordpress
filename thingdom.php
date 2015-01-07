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

		//
		// Instance variables
		//

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
			'comments' 	=> array(
				'label'			=> 'New comment alerts',
				'type'			=> 'checkbox',
				'default'		=> 1
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
			),
			'pages_update' => array(
				'label'			=> 'Updated page alerts',
				'type'			=> 'checkbox',
				'default'		=> 1
			),
			'posts_update' => array(
				'label'			=> 'Updated post alerts',
				'type'			=> 'checkbox',
				'default'		=> 1
			)
		);		

		/**
		 * Secret used to identify this WordPress instance.
		 * @var string
		*/
		protected $secret;

		/**
		 * List of settings currently stored by the plugin.
		 * @var array
		*/

		protected $settings = array(
			'comments'		=> '',
			'pages'			=> '',
			'pages_update'	=> '',
			'posts'			=> '',
			'posts_update'	=> '',
			'secret'		=> ''
		);

		/**
		 * Slug used for admin menus.
		 * @var string
		*/

		protected $slug = 'thingdom-settings';

		/**
		 * Tag identifier used by file includes and selector attributes.
		 * @var string
		*/

		protected $tag = 'thingdom-wp_';

		/**
		 * Thingdom "Thing Name" used for all calls to Thingdom.
		 * @var string
		*/

		protected $thingName;

		/**
		 * Thingdom "Product Type" used for all calls to Thingdom.
		 * @var string
		*/

		protected $thingType = 'wordpress';

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

		//
		// Public methods
		//

		/**
		 * Initiate plugin by getting options and setting instance variables.
		 *
		 * @access public
		*/

		public function __construct()
		{
			// get 'blogname' field from database, if empty default to 'My Blog'
			$this->thingName = !empty(get_option('blogname')) ? get_option('blogname') : 'My Blog';

			// look up settings and populate instance array
			$this->getSettings();

			// this will be used for Thingdom calls and will always contain a static value unless this is the first invocation of the plugin
			$this->secret = $this->settings['secret'];

			// fire off method to update Thingdom status variables
			$this->updateStatus();

			// get other plugin options and build array

			if ( is_admin() ) {
				add_action('admin_menu', array($this, 'registerMenu'));

				if(!empty($this->secret)) {
					// register all Thingdom-specific admin actions based on plugin configuration
					if($this->settings['posts'] == 1 || $this->settings['pages'] == 1) {
						add_action('publish_post', array($this, 'newPost'), 10, 2);
						add_action('publish_page', array($this, 'newPost'), 10, 2);
						//add_action('post_updated', array($this, 'updatePost'), 10, 3);
					}									
				}
			}

			if(!empty($this->secret)) {
				// register all Thingdom-specific non-admin actions based on plugin configuration
				if($this->settings['comments'] == 1)  {
					add_action('comment_post', array($this, 'newComment'), 10, 1);
				}				
			}
		}

		/**
		 * Method triggered whenever a comment is posted to the site.
		 *
		 * @access public
		 * @param int $post_id
		*/				

		public function newComment($comment_id) 
		{

			$thingdom = $this->getThingdom();
			
			if(!$thingdom) {
				return false;
			}

			$comment = get_comments(array('ID' => $comment_id));
			$post_title = get_the_title($comment[0]->comment_post_ID);

			$thing = $thingdom->getThing($this->thingName, $this->thingType);

			$thing->feed('new_comment', "New comment on: {$post_title}\n<br>From: {$comment[0]->comment_author}<br>\nComment: {$comment[0]->comment_content}");

		}		

		/**
		 * Method triggered whenever a page/post is published.
		 *
		 * @access public
		 * @param int $post_id
		 * @param object $post
		*/		

		public function newPost($post_id, $post) 
		{
			$thingdom = $this->getThingdom();

			if(!$thingdom) {
				return false;
			}

			$post_title = get_the_title($post_id);

			$thing = $thingdom->getThing($this->thingName, $this->thingType);

			if($post->post_type == 'post' && $this->settings['posts'] == 1) {
				$thing->feed('new_post', "New post published: {$post_title}");
			} else if($post->post_type == 'page' && $this->settings['pages'] == 1) {
				$thing->feed('new_page', "New page published: {$post_title}");
			}
		}

		/**
		 * Method triggered whenever a page/post is published.
		 *
		 * @access public
		 * @param int $post_id
		 * @param object $post_after 
		 * @param object $post_before
		*/				

		public function updatePost($post_id, $post_after, $post_before)
		{
			$thingdom = $this->getThingdom();

			if(!$thingdom) {
				return false;
			}

			$post_title = get_the_title($post_id);

			$thing = $thingdom->getThing($this->thingName, $this->thingType);

			if($post_after->post_type == 'post' && $this->settings['posts_update'] == 1)  {
				$thing->feed('update_post', "Post updated: {$post_title}");
			} else if($post_after->post_type == 'page' && $this->settings['pages_update'] == 1) {
				$thing->feed('update_page', "Page updated: {$post_title}");
			}
		}

		/**
		 * Method to handle updating status variables when plugin is invoked.
		 *
		 * @access public
		*/

		public function updateStatus()
		{
			$thingdom = $this->getThingdom();

			if(!$thingdom) {
				return false;
			}

			$page_count = wp_count_posts('page')->publish;
			$post_count = wp_count_posts()->publish;
			$comment_count = wp_count_comments()->approved;

			$thing = $thingdom->getThing($this->thingName, $this->thingType);

			$thing->status('page_count', $page_count);
			$thing->status('post_count', $post_count);
			$thing->status('comment_count', $comment_count);
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
				register_setting('thingdom-options', $this->tag.$id);
			}
		}

		//
		// Private methods
		//

		/**
		 * Retrieve plugin settings from database and return array to constructor.
		 * @access private
		 * @return array
		*/

		private function getSettings()
		{
			foreach($this->settings as $key => $val) {
				$this->settings[$key] = !empty(get_option($this->tag.$key)) ? get_option($this->tag.$key) : '';
			}
		}

		/**
		 * Initialize and return an authenticated Thingdom object.
		 * @access private
		 * @return (object) Thingdom
		*/

		private function getThingdom()
		{
			try {
				$thingdom = new thingdom();
				$secret = $thingdom->authenticate($this->secret);
			} catch (Exception $ex) {
				error_log( json_encode($ex->getMessage()) );
				return false;
			}

			if(empty($this->secret)) {
				// first time plugin has run on this instance, write secret to wp_options table
				update_option($this->tag.'secret', $secret);
			}

			return $thingdom;
		}
	}

    new ThingdomWP();
}

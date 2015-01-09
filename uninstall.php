<?php
/**
 * Fired when the plugin is uninstalled
 *
 * @package Add_to_Footer
 */

// If uninstall not called from WordPress, then exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

$tag = 'thingdom-wp_';
$fields = array("comments", "pages", "pages_update", "posts", "posts_update", "secret");

foreach($fields as $field) {
    delete_option($tag.$field);    
}

?>
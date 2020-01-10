<?php

/**
 * Register polls post type
 */

namespace ms\Poll;

defined('ABSPATH') || exit;

class Backend
{
	public function __construct()
	{
		add_action('acf/init', array($this, 'register_post_type'));
		add_action('template_redirect', array($this, 'disable_single_page'));
		add_action('edit_form_after_editor', array($this, 'generate_shortcode'));
	}

	// Register polls post type
	public function register_post_type()
	{

		$args = array(
			'label'                 => __('Polls', 'wppolls'),
			'supports'              => array('title'),
			'hierarchical'          => false,
			'public'                => true,
			'show_ui'               => true,
			'show_in_menu'          => true,
			'menu_position'         => 100,
			'show_in_admin_bar'     => true,
			'show_in_nav_menus'     => true,
			'can_export'            => true,
			'has_archive'           => false,
			'exclude_from_search'   => false,
			'publicly_queryable'    => true,
			'capability_type'       => 'page',
		);
		register_post_type('wppolls', $args);
	}

	// Disable single post type view
	public function disable_single_page()
	{
		$queried_post_type = get_query_var('post_type');
		if (is_single() && 'wppolls' ==  $queried_post_type) {
			wp_redirect(home_url(), 301);
			exit;
		}
	}

	// Display shortcode when creating a poll
	public function generate_shortcode()
	{
		global $typenow;
		if ($typenow == 'wppolls') :
?>
<div class="postbox-container">
	<h2>Shortcode</h2>
	<input class="regular-text" type="text" value="[wppolls id=<?php echo htmlentities('"' . get_the_ID() . '"'); ?>]">
</div>
<?php
		endif;
	}
}
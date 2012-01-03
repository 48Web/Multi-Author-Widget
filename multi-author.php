<?php
/*
Plugin Name: Multi-Authors Widget
Description: The Authors Widget lists each author, their Gravatar, and recent posts in a list. It's a fully configurable sidebar widget that is useful for multi-author blogs. Based on the WordPress.com Authors Widget and adapted from the "Authors Widget" by Gavriel Fleischer at http://wordpress.org/extend/plugins/authors/ and http://blog.fleischer.hu/wordpress/authors/
Version: 1.0
Author: 48Web
Author URI: http://48web.com
License: GPL2
*/

// Multi-language support
if (defined('WPLANG') && function_exists('load_plugin_textdomain')) {
	load_plugin_textdomain('authors', PLUGINDIR.'/'.dirname(plugin_basename(__FILE__)).'/lang');
}

// Widget stuff
function widget_authors_register() {
if ( function_exists('register_sidebar_widget') ) :
	if ( function_exists('seo_tag_cloud_generate') ) :
	function widget_authors_cloud($args = '') {
		global $wpdb;

		$defaults = array(
			'optioncount' => false, 'exclude_admin' => true,
			'show_fullname' => false, 'hide_empty' => true,
			'feed' => '', 'feed_image' => '', 'feed_type' => '', 'echo' => true,
			'limit' => 0, 'posts_limit' => 0, 'em_step' => 0.1
		);

		$r = wp_parse_args( $args, $defaults );
		extract($r, EXTR_SKIP);

		$return = '';

		$authors = $wpdb->get_results('SELECT ID, user_nicename, display_name FROM '.$wpdb->users.' ' . ($exclude_admin ? 'WHERE ID <> 1 ' : '') . 'ORDER BY display_name');

		$author_count = array();
		foreach ((array) $wpdb->get_results('SELECT DISTINCT post_author, COUNT(ID) AS count FROM '.$wpdb->posts.' WHERE post_type = "post" AND ' . get_private_posts_cap_sql( 'post' ) . ' GROUP BY post_author') as $row) {
			$author_count[$row->post_author] = $row->count;
		}

		foreach ( (array) $authors as $key => $author ) {
			$posts = (isset($author_count[$author->ID])) ? $author_count[$author->ID] : 0;
			if ( $posts != 0 || !$hide_empty ) {
				$author = get_userdata( $author->ID );
				$name = $author->display_name;
				if ( $show_fullname && ($author->first_name != '' && $author->last_name != '') )
					$name = "$author->first_name $author->last_name";

				if ( $posts == 0 ) {
					if ( !$hide_empty )
						$link = '';
				}
				else {
					$link = get_author_posts_url($author->ID, $author->user_nicename);
				}
				$authors[$key]->name = $name;
				$authors[$key]->count = $posts;
				$authors[$key]->link = $link;
				$authors[$key]->extra = $optioncount ? '('.$posts.')' : '';
			}
			else
				unset($authors[$key]);
		}

		$args['number'] = $limit;
		$return = seo_tag_cloud_generate( $authors, $args ); // Here's where those top tags get sorted according to $args
		echo $return;
	}
	endif;

	function widget_authors_order_by($args) {
		$count = $args['optioncount'];
		$args['optioncount'] = 1;
		$arr = array_slice(explode('<li>', widget_authors_list_authors($args)), 1);
		switch ($args['orderby']) {
			case 'posts': usort($arr, 'widget_authors_sort_by_posts');break;
			case 'name':
			default:
		}
		if ('0' == $count) {
			array_walk($arr, 'widget_authors_format', 'no-count');
		}
		return $arr;
	}

	function widget_authors_format(&$val, $i, $format) {
		switch ($format) {
		case 'no-count':
		    //$val = preg_replace('#\(\s*([0-9]*)\)</li>#', '</li>', $val);
			$val = preg_replace('/\((\d+)\)/i', '</li>', $val);
/*			$val = preg_replace('/(\d+)/i', '</li>', $val);
			echo "<pre>";
			print_r($val);
			echo "</pre>";*/
			
		    break;
		}
	}

	function widget_authors_dropdown($args = '') {
		$args['echo'] = false;
		unset($args['feed']);
		$arr = widget_authors_order_by($args);
		$options = '';
		foreach ($arr as $author) {
			preg_match('#<a href="([^"]*)"[^>]*>([^<]*)</a>( \(([0-9]*)\))?#', $author, $matches);
			$options .= '<option value="'.htmlspecialchars($matches[1]).'">'.$matches[2].($args['optioncount'] ? ' ('.$matches[4].')' : '').'</option>'."\n";
		}
		unset($arr);
		$dropdown = '<select onchange="window.location=this.options[this.selectedIndex].value">'."\n";
		$dropdown .= '<option value="#">'.__('Select Author...', 'authors').'</option>'."\n";
		$dropdown .= $options;
		$dropdown .= '</select>';
		echo $dropdown;
	}

function widget_authors_list_authors($args = '') {
	global $wpdb;

	$defaults = array(
		'optioncount' => false, 'exclude_admin' => true,
		'show_fullname' => false, 'hide_empty' => true,
		'feed' => '', 'feed_image' => '', 'feed_type' => '', 'echo' => true,
		'style' => 'list', 'html' => true,
		'show_avatar' => false, 'avatar_size' => 32
	);

	$r = wp_parse_args( $args, $defaults );
	extract($r, EXTR_SKIP);
	$return = '';

	/** @todo Move select to get_authors(). */
	$authors = $wpdb->get_results("SELECT ID, user_nicename FROM $wpdb->users WHERE ID = 3 OR ID = 7 OR ID = 2 OR ID = 5 ORDER BY display_name");

	$author_count = array();
	foreach ((array) $wpdb->get_results("SELECT DISTINCT post_author, COUNT(ID) AS count FROM $wpdb->posts WHERE post_type = 'post' AND " . get_private_posts_cap_sql( 'post' ) . " GROUP BY post_author") as $row) {
		$author_count[$row->post_author] = $row->count;
	}

	foreach ( (array) $authors as $author ) {

		$link = '';

		$author = get_userdata( $author->ID );
		if ($exclude_admin && 10 == $author->user_level)
			continue;
		$posts = (isset($author_count[$author->ID])) ? $author_count[$author->ID] : 0;
		$name = $author->display_name;
		$email = $author->user_email;
		$avatar = get_avatar($email, $avatar_size);

		if ( $show_fullname && ($author->first_name != '' && $author->last_name != '') )
			$name = "$author->first_name $author->last_name";

		if( !$html ) {
			if ( $posts == 0 ) {
				if ( ! $hide_empty )
					$return .= $name . ', ';
			} else
				$return .= $name . ', ';

			// No need to go further to process HTML.
			continue;
		}

		if ( !($posts == 0 && $hide_empty) && 'list' == $style )
			$return .= '<li><div class="maw-author-head">';
			
			
			
		if ( $posts == 0 ) {
			if ( ! $hide_empty )
				$link = $name;
		} else {
			$link = '';
			if ( $show_avatar && !empty($avatar) )
			   $return .= "<div class='maw-author-img'>" .$avatar .'</div>';

			   $return .= '<div class="maw-author-name"><a href="' . get_author_posts_url($author->ID, $author->user_nicename) . '" title="' . esc_attr( sprintf(__("Posts by %s"), $author->display_name) ) . '">' . $name . '</a></div>';

					if ( $optioncount )
				$link .= ' ('. $posts . ')';
			
      $return .= "</div><ul>";
			$author_posts = $wpdb->get_results("SELECT id, post_title, guid FROM $wpdb->posts WHERE post_type = 'post' AND " . get_private_posts_cap_sql( 'post' ) . " AND post_author = " . $author->ID . " ORDER BY ID desc LIMIT " . $posts_limit );
			$post_links = "";
			
			foreach ( $author_posts as $author_post )
			{
				$post_links .= "<li><a href = " . get_permalink($author_post->id) . " > ".$author_post->post_title."</a></li>";
			}
			$post_links .= "</ul>";
		}

		if ( !($posts == 0 && $hide_empty) && 'list' == $style )
			$return .= $link . $post_links . '</li>'."\n";
		else if ( ! $hide_empty )
			$return .= $link . ', ';
	}

	$return = trim($return, ', ');

	if ( ! $echo )
		return $return;
	echo $return;
}

	function widget_authors($args, $widget_args = 1) {
		extract($args, EXTR_SKIP);
		if ( is_numeric($widget_args) )
			$widget_args = array( 'number' => $widget_args );
		$widget_args = wp_parse_args( $widget_args, array( 'number' => -1 ) );
		extract($widget_args, EXTR_SKIP);

		$options = get_option('widget_authors');
		if (isset($options[$number]))
			$options = $options[$number];
		$options = wp_parse_args($args, $options);
#		if (!isset($options[$number]))
#			return;

		$title = empty($options['title']) ? __('Authors','authors') : apply_filters('widget_title', $options['title']);
		$format = $options['format'];
		$order = "name";
		$limit = $options['limit'];
		$posts_limit = $options['posts_limit'];
		$show_avatar = $options['show_avatar'] ? '1' : '0';
		$avatar_size = $options['avatar_size'];
		$feedlink = $options['feedlink'] ? '1' : '0';
		$count = $options['count'] ? '1' : '0';
		$exclude_admin = $options['exclude_admin'] ? '1' : '0';
		$hide_credit = $options['hide_credit'] ? '1' : '0';

		?>
		<?php echo $before_widget; ?>
			<?php echo $before_title . $title . $after_title; ?>
			<div class="multi-author-widget">
				<ul>
					<?php
						$list_args = array('orderby'=>$order, 'limit'=>$limit, 'posts_limit'=>$posts_limit, 'show_avatar'=>$show_avatar, 'avatar_size'=>$avatar_size, 'optioncount'=>$count, 'exclude_admin'=>$exclude_admin, 'show_fullname'=>0, 'hide_empty'=>1);
						if ($feedlink) {
							$list_args['feed'] = 'RSS';
							$list_args['feed_image'] = '';
						}
						if ('cloud' == $format && function_exists('seo_tag_cloud_generate') ) {
							widget_authors_cloud($list_args);
						}
						elseif ('dropdown' == $format) {
							widget_authors_dropdown($list_args);
						}
						else /*if ('list' == $format)*/ {
							$list_args['echo'] = false;
							$arr = widget_authors_order_by($list_args);
							echo '<li>'.implode('<li>', $arr);
						}
					?>
				</ul>
			</div>
			<?php //if ($options['hide_credit'] != 1) printf('<span class="credit">'.__('Powered by %s','authors').'</span>', '<a href="http://blog.fleischer.hu/wordpress/authors/" title="'.__('Authors Widget Plugin for Wordpress','authors').'">'.__('Authors Widget','authors').'</a>');?>
		<?php echo $after_widget; ?>
	<?php
	}

	function widget_authors_sort_by_posts($a, $b) {
		$matches = array();
		preg_match('#\(([0-9]*)\)</li>#', $a, $matches);
		$aC = is_array($matches) && count($matches) >= 2 ? intval($matches[1]) : 0;
		preg_match('#\(([0-9]*)\)</li>#', $b, $matches);
		$bC = is_array($matches) && count($matches) >= 2 ? intval($matches[1]) : 0;
		return $aC < $bC ? 1 : -1;
	}
	
	function widget_authors_style() {
		echo '<link type="text/css" rel="stylesheet" href="' . get_bloginfo('wpurl') . '/wp-content/plugins/multi-author-widget/css/maw.css" />' . "\n";
	}

	function widget_authors_control( $widget_args ) {
		global $wp_registered_widgets;
		static $updated = false;

		if ( is_numeric($widget_args) )
			$widget_args = array( 'number' => $widget_args );
		$widget_args = wp_parse_args( $widget_args, array( 'number' => -1 ) );
		extract($widget_args, EXTR_SKIP);

		$options = get_option('widget_authors');

		if ( !is_array( $options ) )
			$options = array();

		if ( !$updated && !empty($_POST['sidebar']) ) {
			$sidebar = (string) $_POST['sidebar'];

			$sidebars_widgets = wp_get_sidebars_widgets();
			if ( isset($sidebars_widgets[$sidebar]) )
				$this_sidebar =& $sidebars_widgets[$sidebar];
			else
				$this_sidebar = array();

			foreach ( (array) $this_sidebar as $_widget_id ) {
				if ( 'widget_authors' == $wp_registered_widgets[$_widget_id]['callback'] && isset($wp_registered_widgets[$_widget_id]['params'][0]['number']) ) {
					$widget_number = $wp_registered_widgets[$_widget_id]['params'][0]['number'];
					if ( !in_array( "authors-$widget_number", $_POST['widget-id'] ) ) // the widget has been removed.
						unset($options[$widget_number]);
				}
			}

			foreach ( (array) $_POST['widget-authors'] as $widget_number => $widget_authors ) {
				if ( !isset($widget_authors['title']) && isset($options[$widget_number]) ) // user clicked cancel
					continue;
				$title = trim(strip_tags(stripslashes($widget_authors['title'])));
				$format = 'list';
				$order = 'name';
				$limit = !empty($widget_authors['limit']) ? $widget_authors['limit'] : '';
				$posts_limit = !empty($widget_authors['posts_limit']) ? $widget_authors['posts_limit'] : '';
				$show_avatar = isset($widget_authors['show_avatar']);
				$avatar_size = !empty($widget_authors['avatar_size']) ? $widget_authors['avatar_size'] : 32;
				$feedlink = isset($widget_authors['feedlink']);
				$count = isset($widget_authors['count']);
				$exclude_admin = isset($widget_authors['exclude_admin']);
				$hide_credit = isset($widget_authors['hide_credit']);
				$options[$widget_number] = compact( 'title', 'format', 'order', 'limit', 'posts_limit', 'show_avatar', 'avatar_size', 'feedlink', 'count', 'exclude_admin', 'hide_credit' );
			}

			update_option('widget_authors', $options);
			$updated = true;
		}

		if ( -1 == $number ) {
			$title = '';
			$format = 'list';
			$order = 'name';
			$limit = '';
			$show_avatar = false;
			$avatar_size = 32;
			$feedlink = false;
			$count = false;
			$exclude_admin = 0;
			$hide_credit = 0;
			$number = '%i%';
		} else {
			$title = attribute_escape( $options[$number]['title'] );
			$format = 'list';
			$order = 'name';
			$limit = attribute_escape( $options[$number]['limit'] );
			$posts_limit = attribute_escape( $options[$number]['posts_limit'] );
			$show_avatar = (bool) $options[$number]['show_avatar'];
			$avatar_size = attribute_escape( $options[$number]['avatar_size'] );
			$feedlink = (bool) $options[$number]['feedlink'];
			$count = (bool) $options[$number]['count'];
			$exclude_admin = (bool) $options[$number]['exclude_admin'];
			$hide_credit = (bool) $options[$number]['hide_credit'];
		}
		?>
		<p><label for="authors-title-<?php echo $number; ?>"><?php _e('Title','authors'); ?>: <input id="authors-title-<?php echo $number; ?>" name="widget-authors[<?php echo $number; ?>][title]" type="text" value="<?php echo $title; ?>" class="widefat" /></label></p>


<p><label for="posts-limit-<?php echo $number; ?>"><?php _e('Number of posts to show for each author', 'authors') ?>: <input type="text" class="widefat" style="width: 25px; text-align: center;" id="posts-limit-<?php echo $number; ?>" name="widget-authors[<?php echo $number; ?>][posts_limit]" value="<?php echo $posts_limit ?>" /></label></p>

		<p><label for="authors-show-avatar-<?php echo $number; ?>"><input id="authors-show-avatar-<?php echo $number; ?>" name="widget-authors[<?php echo $number; ?>][show_avatar]" type="checkbox" <?php checked( $show_avatar, true ); ?> class="checkbox" /> <?php _e('Show Avatar','authors'); ?></label></p>
		<p><label for="authors-avatar-size-<?php echo $number; ?>"><?php _e('Avatar size', 'authors') ?>: <input type="text" class="widefat" style="width: 25px; text-align: center;" id="authors-avatar-size-<?php echo $number; ?>" name="widget-authors[<?php echo $number; ?>][avatar_size]" value="<?php echo $avatar_size ?>" /></label></p>
		<p><label for="authors-feedlink-<?php echo $number; ?>"><input id="authors-feedlink-<?php echo $number; ?>" name="widget-authors[<?php echo $number; ?>][feedlink]" type="checkbox" <?php checked( $feedlink, true ); ?> class="checkbox" /> <?php _e('Show RSS links','authors'); ?></label></p>
		<p><label for="authors-count-<?php echo $number; ?>"><input id="authors-count-<?php echo $number; ?>" name="widget-authors[<?php echo $number; ?>][count]" type="checkbox" <?php checked( $count, true ); ?> class="checkbox" /> <?php _e('Show post counts','authors'); ?></label></p>
		<p><label for="authors-exclude-admin-<?php echo $number; ?>"><input id="authors-exclude-admin-<?php echo $number; ?>" name="widget-authors[<?php echo $number; ?>][exclude_admin]" type="checkbox" <?php checked( $exclude_admin, true ); ?> class="checkbox" /> <?php _e('Exclude admin','authors'); ?></label></p>
		<p>Plugin created by <a href="http://48web.com" target="_blank">48Web</a>... If you need help with WordPress, <a href="http://48Web.com/contact" target="_blank">let us know!</a></p>
		<input type="hidden" name="widget-authors[<?php echo $number; ?>][submit]" value="1" />
	<?php
	}

	function widget_authors_upgrade() {
		$options = get_option( 'widget_authors' );

		if ( !isset( $options['title'] ) )
			return $options;

		$newoptions = array( 1 => $options );

		update_option( 'widget_authors', $newoptions );

		$sidebars_widgets = get_option( 'sidebars_widgets' );
		if ( is_array( $sidebars_widgets ) ) {
			foreach ( $sidebars_widgets as $sidebar => $widgets ) {
				if ( is_array( $widgets ) ) {
					foreach ( $widgets as $widget )
						$new_widgets[$sidebar][] = ( $widget == 'authors' ) ? 'authors-1' : $widget;
				} else {
					$new_widgets[$sidebar] = $widgets;
				}
			}
			if ( $new_widgets != $sidebars_widgets )
				update_option( 'sidebars_widgets', $new_widgets );
			}

		return $newoptions;
	}

	if ( !$options = get_option( 'widget_authors' ) )
		$options = array();

	if ( isset($options['title']) )
		$options = widget_authors_upgrade();

	$widget_ops = array( 'classname' => 'widget_authors', 'description' => __( 'A customizable sidebar widget to list authors and their recent posts','authors' ) );

	$name = __( 'Authors','authors' );

	$id = false;
	foreach ( (array) array_keys($options) as $o ) {
		// Old widgets can have null values for some reason
		if ( !isset($options[$o]['title']) )
			continue;
		$id = "authors-$o";
		wp_register_sidebar_widget( $id, $name, 'widget_authors', $widget_ops, array( 'number' => $o ) );
		wp_register_widget_control( $id, $name, 'widget_authors_control', array( 'id_base' => 'authors' ), array( 'number' => $o ) );
	}

	// If there are none, we register the widget's existance with a generic template
	if ( !$id ) {
		wp_register_sidebar_widget( 'authors-1', $name, 'widget_authors', $widget_ops, array( 'number' => -1 ) );
		wp_register_widget_control( 'authors-1', $name, 'widget_authors_control', array( 'id_base' => 'authors' ), array( 'number' => -1 ) );
	}
	if ( is_active_widget('widget_authors') )
		add_action('wp_head', 'widget_authors_style');
endif;
}

add_action('init', 'widget_authors_register');

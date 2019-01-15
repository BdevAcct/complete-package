<?php

/***** Deprecated functionality *****/

/** Custom Hooks - Scheduled for removal **/

function jmd_html_class() {
    do_action('jmd_html_class');
}

/** Page Title Output - Scheduled for removal **/

if (!function_exists('jmd_magandanow_new_page_title')) {
	function jmd_magandanow_new_page_title() {
		if (!is_front_page()) {
			echo '<header class="page-header">' . "\n";
				echo '<h1 class="page-title">';
					if (is_archive()) {
						if (is_category() || is_tax()) {
							single_cat_title();
						} elseif (is_tag()) {
							single_tag_title();
						} elseif (is_author()) {
							global $author;
							$user_info = get_userdata($author);
							printf(esc_html(_x('Articles by %s', 'post author', 'jmd-magandanow-new')), esc_attr($user_info->display_name));
						} elseif (is_day()) {
							echo get_the_date();
						} elseif (is_month()) {
							echo get_the_date('F Y');
						} elseif (is_year()) {
							echo get_the_date('Y');
						} elseif (is_post_type_archive()) {
							global $post;
							$post_type = get_post_type_object(get_post_type($post));
							echo esc_attr($post_type->labels->name);
						} else {
							esc_html_e('Archives', 'jmd-magandanow-new');
						}
					} else {
						if (is_home()) {
							echo esc_attr(get_the_title(get_option('page_for_posts', true)));
						} elseif (is_404()) {
							esc_html_e('Page not found (404)', 'jmd-magandanow-new');
						} elseif (is_search()) {
							printf(esc_html__('Search Results for %s', 'jmd-magandanow-new'), esc_attr(get_search_query()));
						} else {
							the_title();
						}
					}
				echo '</h1>' . "\n";
			echo '</header>' . "\n";
		}
	}
}

?>
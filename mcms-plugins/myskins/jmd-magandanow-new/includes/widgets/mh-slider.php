<?php

/***** MH Slider [lite] *****/

class jmd_slider_hp_widget extends MCMS_Widget {
    function __construct() {
		parent::__construct(
			'jmd_slider_hp', esc_html_x('MH Slider Widget [lite]', 'widget name', 'jmd-magandanow-new'),
			array(
				'classname' => 'jmd_slider_hp',
				'description' => esc_html__('Slider widget for use on homepage template.', 'jmd-magandanow-new')
			)
		);
	}
    function widget($args, $instance) {
		$defaults = array('category' => 0, 'tags' => '', 'postcount' => 5, 'offset' => 0, 'image_size' => 'large', 'sticky' => 1);
		$instance = mcms_parse_args($instance, $defaults);
	   	$query_args = array();
	   	if (0 !== $instance['category']) {
			$query_args['cat'] = $instance['category'];
		}
		if (!empty($instance['tags'])) {
			$tag_slugs = explode(',', $instance['tags']);
			$tag_slugs = array_map('trim', $tag_slugs);
			$query_args['tag_slug__in'] = $tag_slugs;
		}
		if (!empty($instance['postcount'])) {
			$query_args['posts_per_page'] = $instance['postcount'];
		}
		if (0 !== $instance['offset']) {
			$query_args['offset'] = $instance['offset'];
		}
		if (1 === $instance['sticky']) {
			$query_args['ignore_sticky_posts'] = true;
		}
		$slider_loop = new MCMS_Query($query_args);
        echo $args['before_widget']; ?>
        	<div id="mh-slider-<?php echo rand(1, 9999); ?>" class="flexslider mh-slider-widget <?php echo 'mh-slider-' . esc_attr($instance['image_size']); ?>">
				<ul class="slides"><?php
					while ($slider_loop->have_posts()) : $slider_loop->the_post(); ?>
						<li class="mh-slider-item">
							<article class="post-<?php the_ID(); ?>">
								<a href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>"><?php
									if (has_post_thumbnail()) {
										if ($instance['image_size'] == 'large') {
											the_post_thumbnail('jmd-magandanow-new-slider');
										} else {
											the_post_thumbnail('jmd-magandanow-new-content');
										}
									} else {
										if ($instance['image_size'] == 'large') {
											echo '<img class="mh-image-placeholder" src="' . esc_url(get_template_directory_uri() . '/images/placeholder-slider.png') . '" alt="' . esc_html__('No Image', 'jmd-magandanow-new') . '" />';
										} else {
											echo '<img class="mh-image-placeholder" src="' . esc_url(get_template_directory_uri() . '/images/placeholder-content.png') . '" alt="' . esc_html__('No Image', 'jmd-magandanow-new') . '" />';
										}
									} ?>
								</a>
								<div class="mh-slider-caption">
									<div class="mh-slider-content">
										<a href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>">
											<h2 class="mh-slider-title">
												<?php the_title(); ?>
											</h2>
										</a>
										<div class="mh-slider-excerpt">
											<?php the_excerpt(); ?>
										</div>
									</div>
								</div>
							</article>
						</li><?php
					endwhile;
					mcms_reset_postdata(); ?>
				</ul>
			</div><?php
        echo $args['after_widget'];
    }
    function update($new_instance, $old_instance) {
        $instance = array();
        if (0 !== absint($new_instance['category'])) {
			$instance['category'] = absint($new_instance['category']);
		}
		if (!empty($new_instance['tags'])) {
			$tag_slugs = explode(',', $new_instance['tags']);
			$tag_slugs = array_map('sanitize_title', $tag_slugs);
			$instance['tags'] = implode(', ', $tag_slugs);
		}
		if (0 !== absint($new_instance['postcount'])) {
			if (absint($new_instance['postcount']) > 50) {
				$instance['postcount'] = 50;
			} else {
				$instance['postcount'] = absint($new_instance['postcount']);
			}
		}
		if (0 !== absint($new_instance['offset'])) {
			if (absint($new_instance['offset']) > 50) {
				$instance['offset'] = 50;
			} else {
				$instance['offset'] = absint($new_instance['offset']);
			}
		}
		if ('large' !== $new_instance['image_size']) {
			if (in_array($new_instance['image_size'], array('normal'))) {
				$instance['image_size'] = $new_instance['image_size'];
			}
		}
		$instance['sticky'] = (!empty($new_instance['sticky'])) ? 1 : 0;
        return $instance;
    }
    function form($instance) {
	    $defaults = array('category' => 0, 'tags' => '', 'postcount' => 5, 'offset' => 0, 'image_size' => 'large', 'sticky' => 1);
        $instance = mcms_parse_args($instance, $defaults); ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('category')); ?>"><?php esc_html_e('Select a Category:', 'jmd-magandanow-new'); ?></label>
            <select id="<?php echo esc_attr($this->get_field_id('category')); ?>" class="widefat" name="<?php echo esc_attr($this->get_field_name('category')); ?>">
            	<option value="0" <?php selected(0, $instance['category']); ?>><?php esc_html_e('All', 'jmd-magandanow-new'); ?></option><?php
            		$categories = get_categories();
            		foreach ($categories as $cat) { ?>
            			<option value="<?php echo absint($cat->cat_ID); ?>" <?php selected($cat->cat_ID, $instance['category']); ?>><?php echo esc_html($cat->cat_name) . ' (' . absint($cat->category_count) . ')'; ?></option><?php
            		} ?>
            </select>
            <small><?php esc_html_e('Select a category to display posts from.', 'jmd-magandanow-new'); ?></small>
		</p>
		<p>
        	<label for="<?php echo esc_attr($this->get_field_id('tags')); ?>"><?php esc_html_e('Filter Posts by Tags (e.g. lifestyle):', 'jmd-magandanow-new'); ?></label>
			<input class="widefat" type="text" value="<?php echo esc_attr($instance['tags']); ?>" name="<?php echo esc_attr($this->get_field_name('tags')); ?>" id="<?php echo esc_attr($this->get_field_id('tags')); ?>" />
	    </p>
        <p>
        	<label for="<?php echo esc_attr($this->get_field_id('postcount')); ?>"><?php esc_html_e('Post Count (max. 50):', 'jmd-magandanow-new'); ?></label>
			<input class="widefat" type="text" value="<?php echo absint($instance['postcount']); ?>" name="<?php echo esc_attr($this->get_field_name('postcount')); ?>" id="<?php echo esc_attr($this->get_field_id('postcount')); ?>" />
	    </p>
	    <p>
        	<label for="<?php echo esc_attr($this->get_field_id('offset')); ?>"><?php esc_html_e('Skip Posts (max. 50):', 'jmd-magandanow-new'); ?></label>
			<input class="widefat" type="text" value="<?php echo absint($instance['offset']); ?>" name="<?php echo esc_attr($this->get_field_name('offset')); ?>" id="<?php echo esc_attr($this->get_field_id('offset')); ?>" />
	    </p>
	    <p>
	    	<label for="<?php echo esc_attr($this->get_field_id('image_size')); ?>"><?php esc_html_e('Image size:', 'jmd-magandanow-new'); ?></label>
			<select id="<?php echo esc_attr($this->get_field_id('image_size')); ?>" class="widefat" name="<?php echo esc_attr($this->get_field_name('image_size')); ?>">
				<option value="normal" <?php selected('normal', $instance['image_size']); ?>><?php esc_html_e('Normal', 'jmd-magandanow-new'); ?></option>
				<option value="large" <?php selected('large', $instance['image_size']); ?>><?php esc_html_e('Large', 'jmd-magandanow-new'); ?></option>
			</select>
        </p>
        <p>
			<input id="<?php echo esc_attr($this->get_field_id('sticky')); ?>" name="<?php echo esc_attr($this->get_field_name('sticky')); ?>" type="checkbox" value="1" <?php checked(1, $instance['sticky']); ?> />
			<label for="<?php echo esc_attr($this->get_field_id('sticky')); ?>"><?php esc_html_e('Ignore Sticky Posts', 'jmd-magandanow-new'); ?></label>
		</p>
    	<p>
    		<strong><?php esc_html_e('Info:', 'jmd-magandanow-new'); ?></strong> <?php esc_html_e('This is the lite version of this widget with basic features. More features and options are available in the premium version of JMD MagandaNow.', 'jmd-magandanow-new'); ?>
    	</p><?php
    }
}

?>
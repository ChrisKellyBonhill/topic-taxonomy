<?php

function wpb_widgets_init() {
 
    register_sidebar( array(
        'name'          => 'Topic Widget Area',
        'id'            => 'topic-header-widget',
        'before_widget' => '<div class="topic-widget">',
        'after_widget'  => '</div>',
        'before_title'  => '<h2 class="topic-title">',
        'after_title'   => '</h2>',
    ) );
 
}
add_action( 'widgets_init', 'wpb_widgets_init' );

// Creating the widget 
class topic_widget extends \WP_Widget {

	function __construct() {
		$widget_ops = array('classname' => 'same-category-posts', 'description' => __('List posts from same topic based on shown post\'s topic'));
		parent::__construct('same-category-posts', __('Related Topics'), $widget_ops);
	}
	
	/*
		wrapper to execute the the_post_thumbnail with filters
	*/
	function the_post_thumbnail() {
        $ret = $this->the_post_thumbnail();
        return $ret;
	}

	/**
	 * Calculate the HTML for showing the thumb of a post item.
     * Expected to be called from a loop with globals properly set
	 *
	 * @param  array $instance Array which contains the various settings
	 * @return string The HTML for the thumb related to the post
     *
     * @since 1.0.8
	 */
	function show_thumb() {
        $ret = '';

		if ( function_exists('the_post_thumbnail') && 
				current_theme_supports("post-thumbnails") &&
				has_post_thumbnail() ) {
			$ret .= '<a class="same-category-post-thumbnail"';
			$ret .= 'href="' . get_the_permalink() . '" title="' . get_the_title() . '">';
			$ret .= $this->the_post_thumbnail();
			$ret .= '</a>';
		}
		return $ret;
	}
	
	/**
	 * Calculate the HTML for a post item based on the widget settings and post.
     * Expected to be called in an active loop with all the globals set
	 *
	 * @param  array $instance Array which contains the various settings
     * $param  null|integer $current_post_id If on singular page specifies the id of
     *                      the post, otherwise null
	 * @return string The HTML for item related to the post
     *
     * @since 1.0.8
	 */
    function itemHTML($instance,$current_post_id) {
        global $post;
		
        $ret = '<li class="same-topic-post-item ' . ($post->ID == $current_post_id ? 'same-category-post-current' : '') . '">';
		$ret .= $this->show_thumb();
		$ret .= '<a class="post-title" href="' . get_the_permalink() . '" rel="bookmark" title="Permanent Link to ' . get_the_title() . '">' . get_the_title() . '</a>';
        $date_format = "j M Y"; 
        $ret .= '<p class="post-date">';
        $ret .= get_the_date($date_format);		
        $ret .= '</p>';
		$ret .= $this->show_thumb();

		$ret .= '</li>';
		return $ret;
	}
	/**

	 * Set for each object_taxonomy the hierarchical post_type as default, if no tax is selected
	 *
	 * @param  array &$instance
	 * @return by ref
	 */
	function initPostTypesAndTaxes(&$instance) {
		$post_types = get_post_types( array( 'publicly_queryable' => true ) );
		foreach ($post_types as $post_type) {
			$object_taxes = get_object_taxonomies( $post_type, 'objects' );

			$set_default = true;
			foreach ( $object_taxes as $tax ) {
				if (isset($instance['include_tax'][$post_type]) && $instance['include_tax'][$post_type]) {
					if ($tax->name == $instance['include_tax'][$post_type])
						$set_default = false;
				}
				// put all taxes and the associated post_type
				if ($tax->hierarchical) {
					$instance['post_types'][$tax->name] = array( 'post_type'=>$post_type, 'hierarchical'=>true );
				} else {
					$instance['post_types'][$tax->name] = array( 'post_type'=>$post_type, 'hierarchical'=>false );
				}				
			}
			// put and set only 'default' taxes
			if ($set_default) {
				foreach ( $object_taxes as $tax ) {
					if ($tax->hierarchical) {
						$instance['include_tax'][$post_type] = $tax->name; // set as 'default'
					}
				}
			}
		}
	}

	// Displays a list of posts from same category on single post pages.
	function widget($args, $instance) {
		// Only show widget if on a post page.
		if ( !is_single() ) return;

		global $wp_query;
		$post_old = $post; // Save the post object.
		$post = $wp_query->post;
		$current_post_id = $post->ID;

		extract( $args );
		$this->instance = $instance;

		$topics = get_the_terms( $current_post_id, 'topic' );
		$primaryTopic;
		foreach ($topics as $topic) {
			$primaryTopic = $topic;
			break;
		}

		// Query args
		$args = array(
			'post_type' => 'post',
			'orderby' => 'date',
            'order'   => 'DESC',
            'tax_query' => array(
        		array (
            		'taxonomy' => 'topic',
            		'field' => 'slug',
            		'terms' => $primaryTopic,
        		)
    		),
		);

		
		$args['post__not_in'] = array();
		if (isset( $instance['exclude_current_post'] ) && $instance['exclude_current_post']) {
			$args['post__not_in'] = array( $current_post_id );
		}

		if(isset( $instance['exclude_sticky_posts'] ) && $instance['exclude_sticky_posts']) {
			foreach (get_option( 'sticky_posts' ) as $value) {
				$args['post__not_in'][] = $value;
			}
		}

		$args['posts_per_page'] = (isset($instance['num']) && $instance['num']) ? $instance['num'] : -1;

		$my_query = new \WP_Query($args);

		if( $my_query->have_posts() ) {
			echo $before_widget;

			// Widget title
			if( !isset ( $instance["hide_title"] ) ) {
				if( isset( $instance["separate_categories"] ) && $instance["separate_categories"] ) { 
					// Separate categories: title to array
					foreach($categories as $cat) {
						$widgetHTML[$cat->name]['ID'] = $cat->term_id;
						if( isset ( $instance["title_link"] ) ) {
							$title = '<a href="' . get_category_link( $cat ) . '">'. $cat->name . '</a>';
							if(isset($instance['title']) && strpos($instance['title'], '%cat-all%') !== false)
								$title = str_replace( "%cat-all%", $title, $instance['title']);
							else if(isset($instance['title']) && strpos($instance['title'], '%cat%') !== false)
								$title = str_replace( "%cat%", $title, $instance['title']);
							$widgetHTML[$cat->name]['title'] = $title;
						} else {
							$title = $cat->name;
							if(isset($instance['title']) && strpos($instance['title'], '%cat-all%') !== false)
								$title = str_replace( "%cat-all%", $title, $instance['title']);
							else if(isset($instance['title']) && strpos($instance['title'], '%cat%') !== false)
								$title = str_replace( "%cat%", $title, $instance['title']);
							$widgetHTML[$cat->name]['title'] = $title;
						}
					}
				} else {
					// ! Separate categories: echo
					echo $before_title;
					if( isset ( $instance["title_link"] ) ) {
						$linkList = "";
						foreach($categories as $cat) {
							if(isset($instance['exclude_terms']) && $instance['exclude_terms'] && in_array($cat->term_id,$instance['exclude_terms']))
								continue;
							$linkList .= '<a href="' . get_category_link( $cat ) . '">'. $cat->name . '</a>, ';
						}
						$linkList = trim($linkList, ", ");
						if( isset($instance['title']) && $instance['title'] ) { 			// use placeholders if title is not empty
							if(strpos($instance['title'], '%cat-all%') !== false || 
								strpos($instance['title'], '%cat%') !== false) {			// all-category placeholder is used
								if(strpos($instance['title'], '%cat-all%') !== false)
									$linkList = str_replace( "%cat-all%", $linkList, $instance['title']);
								else if(strpos($instance['title'], '%cat%') !== false)
									$linkList = str_replace( "%cat%", '<a href="' . get_category_link( $categories[0] ) . '">'. $categories[0]->name . '</a>', $instance['title']);
							} else 															// no category placeholder is used
								$linkList = '<a href="' . get_category_link( $categories[0] ) . '">'. $instance['title'] . '</a>';
						}
						echo htmlspecialchars_decode(apply_filters('widget_title',$linkList));
					} else {
						$categoryNames = "";
						if ($categories) {
							foreach ($categories as $key => $val) {
								if(isset($instance['exclude_terms']) && $instance['exclude_terms'] && in_array($val->term_id,$instance['exclude_terms'][$val->taxonomy]))
									continue;
								$categoryNames .= $val->name . ", ";
							}
							$categoryNames = trim($categoryNames, ", ");
						}
					
						if( isset($instance['title']) && $instance['title'] ) {				// use placeholders if title is not empty
							if(strpos($instance['title'], '%cat-all%') !== false)			// all-category placeholder is used
								$categoryNames = str_replace( "%cat-all%", $categoryNames, $instance['title']);
							else if(strpos($instance['title'], '%cat%') !== false)			// one-category placeholder is used
								$categoryNames = str_replace( "%cat%", $categories[0]->name, $instance['title']);
							else
								$categoryNames = $instance['title'];
						}
						echo htmlspecialchars_decode(apply_filters('widget_title',$categoryNames));
					}
					echo $after_title;
				}
			}
			// Widget title
			
			// Topic Navigation
			$primaryTopicID = $primaryTopic->term_id;
			$primaryTopicUrl = get_term_link($primaryTopicID);
			echo '<div class="chapterNavigation">';
			echo '<h2><a href="' . $primaryTopicUrl . '">' . $primaryTopic->name . '</a></h2>';
			$primaryTopicImg = get_field('topic_image', 'topic_' . $primaryTopic->term_id);
			echo '<img src="' . $primaryTopicImg .'"/>';
			$primaryTopicExcerpt = get_field('excerpt', 'topic_' . $primaryTopic->term_id);
			echo '<div class="topicExcerpt">' . $primaryTopicExcerpt . '</div>';
			echo '<div class="viewAll">View all</div>';

			echo '</div>';
			// Post list
			echo "<ul>\n";
			while ($my_query->have_posts()) {
				$my_query->the_post();
				
				if( isset( $instance["separate_categories"] ) && $instance["separate_categories"] ) {
					// Separate categories: get itemHTML to array
					$object_taxes = get_object_taxonomies( $post, 'objects' );
					$post_categories = null;
					foreach ( $object_taxes as $tax ) {
						$post_type = $instance['post_types'][$tax->name]['post_type'];
						if ($tax->name == $instance['include_tax'][$post_type]) {
							$post_categories = get_the_terms($post->ID,$tax->name);
							break;
						}
					}
					foreach ($post_categories as $val) {
						$widgetHTML[$val->name][$post->ID]['itemHTML'] = $this->itemHTML($instance,$current_post_id);
						$widgetHTML[$val->name][$post->ID]['ID'] = $post->ID;
					}
				} else {					
					// ! Separate categories: get itemHTML and echo
					echo $this->itemHTML($instance,$current_post_id);
				}
			} // end while

			if (isset( $instance["separate_categories"] ) && $instance["separate_categories"]) {
				// Separate categories: echo
				$isOnPage = array();
				foreach($widgetHTML as $val) {
					// widget title
					$haveItemHTML = false;
					$ret = $before_title . htmlspecialchars_decode(apply_filters('widget_title',isset($val['title'])?$val['title']:"")) . $after_title;
					$count = 1;
					$num_per_cat = (isset($instance['num_per_cate'])&&$instance['num_per_cate']!=0?($instance['num_per_cate']):99999);
					foreach($val as $key) {
						if(is_array($key) && array_key_exists('itemHTML', $key)) {
							if( !in_array($key['ID'], $isOnPage) ) {
								if($count <= $num_per_cat) {
									$ret .= $key['itemHTML'];
									$haveItemHTML = true;
									$isOnPage[] = $key['ID'];
								} else
									break;
								$count++;
							}
						}
					}
				if ($haveItemHTML)
					echo $ret;
				}
			}

			echo "</ul>\n";
			// end Post list
			
			echo $after_widget;
		}

		$post = $post_old; // Restore the post object.
	}

	/**
	 * Update the options
	 *
	 * @param  array $new_instance
	 * @param  array $old_instance
	 * @return array
	 */
	function update($new_instance, $old_instance) {
		
		return $new_instance;
	}

	/**
	 * The widget configuration form back end.
	 *
	 * @param  array $instance
	 * @return void
	 */
	function form($instance) {
	
		// ToDo seperate_cat get seperate_tax (if update, settings)
		
		$this->initPostTypesAndTaxes($instance);

		$instance = wp_parse_args( ( array ) $instance, array(
			'title'                => '',
			'num'                  => '',
			'exclude_current_post' => '',
			'exclude_sticky_posts' => '',
			'author'               => '',
			'date_format'          => '',
			'use_wp_date_format'   => '',
			'excerpt'              => ''
		) );

		$title                = $instance['title'];
		$num                  = $instance['num'];
		$exclude_current_post = $instance['exclude_current_post'];
		$exclude_sticky_posts = $instance['exclude_sticky_posts'];
		$author               = $instance['author'];
		$date_format          = $instance['date_format'];
		$use_wp_date_format   = $instance['use_wp_date_format'];
		$excerpt              = $instance['excerpt'];
		$excerpt_length       = $instance['excerpt_length'];
		$excerpt_more_text    = $instance['excerpt_more_text'];
		$comment_num          = $instance['comment_num'];
		$date                 = $instance['date'];	
		
		?>
		<div class="same-category-widget-cont">
			<h4 data-panel="title"><?php _e('Title')?></h4>
			<div>
				<p>
					<label for="<?php echo $this->get_field_id("title"); ?>">
						<?php _e( 'Title' ); ?>:
						<input style="width:80%;" class="widefat" id="<?php echo $this->get_field_id("title"); ?>" name="<?php echo $this->get_field_name("title"); ?>" type="text" value="<?php echo esc_attr($instance['title']); ?>" />
					</label>
				</p>
			</div>
			<h4 data-panel="filter"><?php _e('Filter')?></h4>
			<div>
				<p>
					<label for="<?php echo $this->get_field_id("num"); ?>">
						<?php _e('Number of posts to show (overall)'); ?>:
						<input style="width:30%;" style="text-align: center;" id="<?php echo $this->get_field_id("num"); ?>" name="<?php echo $this->get_field_name("num"); ?>" type="number" min="0" value="<?php echo absint($instance["num"]); ?>" size='3' />
					</label>
				</p>
				
				<p>
					<label for="<?php echo $this->get_field_id("exclude_current_post"); ?>">
						<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id("exclude_current_post"); ?>" name="<?php echo $this->get_field_name("exclude_current_post"); ?>"<?php checked( (bool) $instance["exclude_current_post"], true ); ?> />
						<?php _e( 'Exclude current post' ); ?>
					</label>
				</p>

				<p>
					<label for="<?php echo $this->get_field_id("exclude_sticky_posts"); ?>">
						<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id("exclude_sticky_posts"); ?>" name="<?php echo $this->get_field_name("exclude_sticky_posts"); ?>"<?php checked( (bool) $instance["exclude_sticky_posts"], true ); ?> />
						<?php _e( 'Exclude sticky posts' ); ?>
					</label>
				</p>

				<p>
					<label for="<?php echo $this->get_field_id("exclude_children"); ?>">
						<input type="checkbox" class="checkbox" 
							id="<?php echo $this->get_field_id("exclude_children"); ?>" 
							name="<?php echo $this->get_field_name("exclude_children"); ?>"
							<?php checked( (bool) $instance["exclude_children"], true ); ?> />
								<?php _e( 'Exclude  children' ); ?>
					</label>
				</p>

			</div>
		</div>
		<?php
	}
}
 
 
// Register and load the widget
function wpb_load_widget() {
    register_widget( 'topic_widget' );
}
add_action( 'widgets_init', 'wpb_load_widget' );

?>
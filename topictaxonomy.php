<?php
/*
Plugin Name: Topic Taxonomy
Plugin URL: ###
Description: Add the 'Topic' taxonomy to your WordPress installation along with a custom Gutenberg block to add to your pages to display posts with this taxonomy.
Version: 1.0.0
Author: C Kelly, Bonhill Plc
Author URL: https://bonhillplc.com/

*/
// add_action( 'after_setup_theme', 'check_acf' );
// function check_acf() {
//     if ( !class_exists( 'acf' ) || !is_plugin_active( 'advanced-custom-fields/acf.php' ) || !is_plugin_active( 'advanced-custom-fields-pro/acf.php' ) ) {
//         // Define path and URL to the ACF plugin.
//         define( 'MY_ACF_PATH', plugin_dir_path( __FILE__ ) . 'includes/acf/' );
//         define( 'MY_ACF_URL', plugin_dir_path( __FILE__ ) . 'includes/acf/' );

//         // Include the ACF plugin.
//         include_once( MY_ACF_PATH . 'acf.php' );

//         // Customize the url setting to fix incorrect asset URLs.
//         add_filter('acf/settings/url', 'my_acf_settings_url');
//         function my_acf_settings_url( $url ) {
//             return MY_ACF_URL;
//         }

//         // (Optional) Hide the ACF admin menu item.
//         add_filter('acf/settings/show_admin', 'my_acf_settings_show_admin');
//         function my_acf_settings_show_admin( $show_admin ) {
//             return true;
//         }
//     }
// }

add_filter('acf/settings/load_json', 'my_acf_json_load_point', 1);

function my_acf_json_load_point( $paths ) {
    unset($paths[0]);
    $paths[] = plugin_dir_path( __FILE__ ) . '/acf-json';
    return $paths;
}

add_filter( 'acf/settings/save_json', 'my_acf_json_save_point' );
function my_acf_json_save_point( $paths ){
    // update path
    $paths = plugin_dir_path( __FILE__ ) . '/acf-json';
    // return
    return $paths;
}

add_action( 'init', 'create_topic_nonhierarchical_taxonomy', 0 );

function create_topic_nonhierarchical_taxonomy() {
  $labels = array(
    'name' => _x( 'Topic', 'taxonomy general name' ),
    'singular_name' => _x( 'Topic', 'taxonomy singular name' ),
    'search_items' =>  __( 'Search Topics' ),
    'popular_items' => __( 'Popular Topics' ),
    'all_items' => __( 'All Topic' ),
    'parent_item' => null,
    'parent_item_colon' => null,
    'edit_item' => __( 'Edit Topic' ), 
    'update_item' => __( 'Update Topic' ),
    'add_new_item' => __( 'Add New Topic' ),
    'new_item_name' => __( 'New Topic Name' ),
    'separate_items_with_commas' => __( 'Separate topics with commas' ),
    'add_or_remove_items' => __( 'Add or remove topics' ),
    'choose_from_most_used' => __( 'Choose from the most used topics' ),
    'menu_name' => __( 'Topic' ),
  );    

  register_taxonomy('topic',array('post'), array(
    'hierarchical' => true,
    'labels' => $labels,
    'show_ui' => true,
    'show_in_rest' => true,
    'show_admin_column' => true,
    'update_count_callback' => '_update_post_term_count',
    'query_var' => true,
    'rewrite' => array( 'slug' => 'topic' ),
  ));
}

add_action( 'init', 'create_content_nonhierarchical_taxonomy', 0 );

function create_content_nonhierarchical_taxonomy() {
  $labels = array(
    'name' => _x( 'Content Type', 'taxonomy general name' ),
    'singular_name' => _x( 'Content Type', 'taxonomy singular name' ),
    'search_items' =>  __( 'Search Content Types' ),
    'popular_items' => __( 'Popular Content Types' ),
    'all_items' => __( 'All Content Types' ),
    'parent_item' => null,
    'parent_item_colon' => null,
    'edit_item' => __( 'Edit Content Type' ), 
    'update_item' => __( 'Update Content Type' ),
    'add_new_item' => __( 'Add New Content Type' ),
    'new_item_name' => __( 'New Content Type Name' ),
    'separate_items_with_commas' => __( 'Separate Content Types with commas' ),
    'add_or_remove_items' => __( 'Add or remove Content Types' ),
    'choose_from_most_used' => __( 'Choose from the most used Content Types' ),
    'menu_name' => __( 'Content Type' ),
  );    

  register_taxonomy('content_type',array('post'), array(
    'hierarchical' => true,
    'labels' => $labels,
    'show_ui' => true,
    'show_in_rest' => true,
    'show_admin_column' => true,
    'update_count_callback' => '_update_post_term_count',
    'query_var' => true,
    'rewrite' => array( 'slug' => 'content_type' ),
  ));

  wp_insert_term( 'Article', 'content_type');
  wp_insert_term( 'Video', 'content_type');
  wp_insert_term( 'Podcast', 'content_type');
  wp_insert_term( 'Webcast', 'content_type');
  wp_insert_term( 'Webinar', 'content_type');
  wp_insert_term( 'Gallery', 'content_type');
}

require_once('createWidget.php');

add_filter( "taxonomy_template", 'load_our_custom_tax_template');
function load_our_custom_tax_template ($tax_template) {
  if (is_tax('topic')) {
    $tax_template = dirname(  __FILE__  ) . '/templates/taxonomy-topic.php';
  }
  if (is_tax()) {
    $tax_template = dirname(  __FILE__  ) . '/templates/taxonomy.php';
  }
  return $tax_template;
}

function get_page_by_slug($slug) {
    if ($pages = get_pages()) {
        foreach ($pages as $page) {
            if ($slug === $page->post_name) {
                return true;
            }
        }
    }
    return false;
}

add_filter("after_setup_theme","add_topic_page");

function add_topic_page() {
    $topicPagePostTitle = 'Topics';
    $topicPageSlug = "topic";
    $topicPageTemplate = dirname(  __FILE__  ) . '/templates/topicPage.php';
    global $wpdb;

    $topicPageQuery = $wpdb->prepare(
        'SELECT ID FROM ' . $wpdb->posts . '
            WHERE post_name = ' . $topicPageSlug . '
            AND post_type = \'page\'',
        $topicPagePostTitle
    );
    $wpdb->query( $topicPageQuery );

    // if ( $wpdb->num_rows ) {
    if(get_page_by_slug( $topicPageSlug ) == true ) {
        // Title already exists
        $topicPage = get_page_by_title( $topicPagePostTitle );
        if ( is_page($topicPage->ID) ) {
            update_post_meta($topicPage->ID, '_wp_page_template', $topicPageTemplate);
        }
    } else {
        $topicPage = array(
            'post_title'   => 'Topics',
            'post_name'   => 'topic',
            'post_content' => '',
            'post_status'  => 'publish',
            'post_author'  => 1,
            'post_type'    => 'page',
            'post_parent'  => 0 
        );

        // Add page
        $insertTopicPage = wp_insert_post( $topicPage );
    }    
}

require_once('createPageTemplate.php');

?>
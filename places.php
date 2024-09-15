<?php

/**
 * Plugin Name: Places - Map Locations
 * Description: A custom plugin to manage locations with location categories.
 * Version: 0.2
 * Author: Prolific Digital
 * Author URI: https://prolificdigital.com
 * License: GPL2
 */

// Prevent direct file access
if (!defined('WPINC')) {
  die;
}

// import acf-fields.php
require_once plugin_dir_path(__FILE__) . 'acf-fields.php';

/**
 * Display admin notice if ACF Pro is not installed or activated.
 *
 * This function checks if ACF Pro is installed and activated. If it's not, 
 * it displays an admin notice warning users that the plugin requires ACF Pro 
 * to function properly.
 *
 * @return void
 */
function wp_maps_check_acf_pro() {
  // Check if ACF class exists (for Pro) or if ACF is activated (for regular)
  if (!class_exists('ACF') && !is_plugin_active('advanced-custom-fields-pro/acf.php')) {
    add_action('admin_notices', function () {
      echo '<div class="notice notice-error"><p><strong>Warning:</strong> WP Maps requires ACF Pro to be installed and activated.</p></div>';
    });
  }
}
add_action('admin_init', 'wp_maps_check_acf_pro');

/**
 * Display admin notice if FacetWP is not installed or activated.
 *
 * This function checks if FacetWP is active. If it's not, it displays an admin notice 
 * warning users that the plugin requires FacetWP to be installed and activated to function properly.
 *
 * @return void
 */
function wp_maps_check_facetwp() {
  if (!class_exists('FacetWP')) {
    add_action('admin_notices', function () {
      echo '<div class="notice notice-error"><p><strong>Warning:</strong> WP Maps requires FacetWP to be installed and activated.</p></div>';
    });
  }
}
add_action('admin_init', 'wp_maps_check_facetwp');

/**
 * Display admin notice if FacetWP Maps is not installed or activated.
 *
 * This function checks if FacetWP Maps is installed and activated. If it's not, 
 * it displays an admin notice warning users that the plugin requires FacetWP Maps 
 * to function properly.
 *
 * @return void
 */
function wp_maps_check_facetwp_maps() {
  // Check if FacetWP Maps is active (facetwp-map-facet.php in the facetwp-map-facet folder)
  if (!is_plugin_active('facetwp-map-facet/facetwp-map-facet.php')) {
    add_action('admin_notices', function () {
      echo '<div class="notice notice-error"><p><strong>Warning:</strong> WP Maps requires FacetWP Maps to be installed and activated.</p></div>';
    });
  }
}
add_action('admin_init', 'wp_maps_check_facetwp_maps');

/**
 * Add FacetWP facets programmatically by decoding and importing a JSON configuration.
 *
 * This filter modifies the existing facets by adding new facets defined in the JSON string.
 * Facets such as Location Map, Location Proximity, and Location Categories are included.
 * The JSON structure can be updated to include additional or custom facets as needed.
 *
 * @param array $facets The existing facets array from FacetWP.
 *
 * @return array $facets The modified facets array with the new facets added.
 */
add_filter('facetwp_facets', function ($facets) {

  // Paste the exported JSON between the single quotes like this: json_decode('PASTE JSON HERE');
  $add_facets = json_decode(
    '{"facets":[{"name":"location_map","label":"Location Map","type":"map","source":"acf/field_66e1936c43ea9","map_design":"default","btn_label":"","reset_label":"","cluster":"no","ajax_markers":"no","limit":"all","map_width":"100%","map_height":"100%","min_zoom":"1","max_zoom":"20","default_lat":"","default_lng":"","default_zoom":"","marker_content":""},{"name":"location_proximity","label":"Location Proximity","type":"proximity","source":"acf/field_66e1936c43ea9","unit":"mi","radius_ui":"dropdown","radius_options":"10, 25, 50, 100, 250","radius_min":"1","radius_max":"50","radius_default":"25","placeholder":""},{"name":"location_categories","label":"Location Categories","type":"fselect","source":"tax/location_category","label_any":"Any","parent_term":"","modifier_type":"off","modifier_values":"","hierarchical":"no","multiple":"yes","ghosts":"yes","preserve_ghosts":"yes","operator":"and","orderby":"count","count":"10"}]}',
    true
  );

  foreach ($add_facets['facets'] as $new_facet) {
    $facets[] = $new_facet;
  }

  return $facets;
});

/**
 * Customize the content of map markers for FacetWP maps.
 *
 * This filter modifies the content displayed in map markers. It combines various fields 
 * such as post title, location categories, phone number, address, additional details, 
 * and a "View More" link. The content is dynamically generated based on the post's 
 * custom fields and taxonomies, and outputs HTML that is displayed in the marker's info window.
 *
 * @param array $args    The existing marker arguments.
 * @param int   $post_id The ID of the post for which the marker is being generated.
 *
 * @return array $args   The modified marker arguments, including the custom content.
 */
add_filter('facetwp_map_marker_args', function ($args, $post_id) {
  // Get post title
  $title = get_the_title($post_id);

  // Get location categories
  $location_categories = get_the_terms($post_id, 'location_category');
  $category_output = '';
  if ($location_categories && !is_wp_error($location_categories)) {
    $category_output = '<div class="marker-categories">';
    foreach ($location_categories as $category) {
      $category_output .= '<span class="marker-category">' . esc_html($category->name) . '</span> ';
    }
    $category_output .= '</div>';
  }

  // Get phone number
  $phone = get_field('phone_number', $post_id);
  $phone_output = $phone ? '<a class="marker-phone" href="' . esc_url($phone['url']) . '" target="' . esc_attr($phone['target']) . '">' . esc_html($phone['title']) . '</a>' : '';

  // Get address fields
  $address = get_field('address', $post_id);
  $address_output = '';
  if ($address) {
    $address_output = '<address class="marker-address">';
    if (isset($address['street_number']) && isset($address['street_name'])) {
      $address_output .= $address['street_number'] . ' ' . $address['street_name'] . '<br>';
    }
    if (isset($address['city']) && isset($address['state']) && isset($address['post_code'])) {
      $address_output .= $address['city'] . ', ' . $address['state'] . ' ' . $address['post_code'];
    }
    $address_output .= '</address>';
  }

  // Get additional details
  $details = get_field('details', $post_id);
  $details_output = $details ? '<div class="marker-details">' . $details . '</div>' : '';

  // Get "View More" link
  $view_more = get_field('view_more', $post_id);
  $view_more_output = $view_more ? '<a class="marker-view-more" href="' . esc_url($view_more['url']) . '" target="' . esc_attr($view_more['target']) . '">' . esc_html($view_more['title']) . '</a>' : '';

  // Get category title
  $category_title_visibility = get_field('show_category_title', $post_id);
  $category_title = get_field('category_title', $post_id);
  $category_title_output = ($category_title_visibility == 1 && $category_title) ? '<div class="marker-category-title">' . $category_title . '</div>' : '';

  // Combine all content
  $marker_content = $category_output .
    '<h2 class="marker-title">' . $title . '</h2>' .
    ($address_output ? $address_output . '<br>' : '') .
    ($phone_output ? $phone_output . '<br>' : '') .
    ($details_output ? $details_output . '<br>' : '') .
    ($view_more_output ? $view_more_output . '<br>' : '') .
    ($category_title_output ? $category_title_output . '<br>' : '');

  $args['content'] = $marker_content;
  return $args;
}, 10, 2);

/**
 * Registers the Google Maps API key for ACF.
 *
 * This function retrieves the Google Maps API key stored in the ACF options page and updates
 * the ACF settings to use this key for Google Map fields.
 *
 * @return void
 */
add_action('acf/init', 'register_acf_google_map_api');
function register_acf_google_map_api() {
  $api_key = get_field('google_maps_api_key', 'option');
  acf_update_setting('google_api_key', $api_key);
}

/**
 * Filters the Google Maps API key for FacetWP.
 *
 * This filter retrieves the Google Maps API key from the ACF options page and returns it
 * for use in FacetWP map functionality.
 *
 * @param string $api_key The default Google Maps API key.
 * 
 * @return string The Google Maps API key from the ACF options page.
 */

add_filter('facetwp_gmaps_api_key', function ($api_key) {
  return get_field('google_maps_api_key', 'option');
});

/**
 * Filters the initialization arguments for FacetWP maps.
 *
 * This filter retrieves map control settings (zoom, map type, street view, fullscreen)
 * from the ACF options page and applies them to the FacetWP map initialization.
 *
 * @param array $args The existing map initialization arguments.
 * 
 * @return array The modified map initialization arguments with custom controls.
 */

add_filter('facetwp_map_init_args', function ($args) {
  $args['init']['zoomControl']       = get_field('zoom_controls', 'option');
  $args['init']['mapTypeControl']    = get_field('map_type_controls', 'option');
  $args['init']['streetViewControl'] = get_field('street_view_control', 'option');
  $args['init']['fullscreenControl'] = get_field('full_screen_control', 'option');
  return $args;
});

/**
 * Filters the initialization arguments for FacetWP maps to include custom map styles.
 *
 * This filter checks if custom map styling is enabled in the ACF options page. 
 * If enabled, it retrieves the custom map style (in JSON format) and applies it 
 * to the FacetWP map initialization.
 *
 * @param array $args The existing map initialization arguments.
 * 
 * @return array The modified map initialization arguments with custom map styles.
 */

add_filter('facetwp_map_init_args', function ($args) {
  if (get_field('enable_custom_map_style', 'option')) {
    $map_style = get_field('custom_map_style', 'option');
    $args['init']['styles'] = json_decode($map_style);
  }
  return $args;
});

/**
 * Filters the arguments for FacetWP map markers to include a custom marker icon.
 *
 * This filter retrieves a custom marker pin URL from the ACF options page and applies it 
 * to the FacetWP map markers. If a custom marker pin is set, it replaces the default marker icon.
 *
 * @param array $args    The existing map marker arguments.
 * @param int   $post_id The ID of the post for which the marker is being generated.
 * 
 * @return array The modified map marker arguments with the custom marker icon.
 */
add_filter('facetwp_map_marker_args', function ($args, $post_id) {
  $custom_marker_pin = get_field('custom_marker_pin', 'option');
  if ($custom_marker_pin) {
    $args['icon'] = $custom_marker_pin['url'];
  }
  return $args;
}, 10, 2);

/**
 * Registers ACF blocks.
 *
 * This function registers custom ACF blocks by defining the block's type and pointing 
 * to the block's configuration file. It uses `register_block_type` to register a block 
 * located in the '/blocks/map' directory.
 *
 * @return void
 */
add_action('init', 'register_acf_blocks');
function register_acf_blocks() {
  register_block_type(__DIR__ . '/blocks/map');
}

/**
 * Registers the "Locations" custom post type.
 *
 * This function creates a custom post type called "Locations" with support for title, editor, 
 * thumbnail, and revisions. It includes various labels for the post type interface and 
 * assigns the 'location_category' taxonomy to it. The post type is public and queryable 
 * with a custom archive.
 *
 * @return void
 */

add_action('init', 'wp_maps_create_locations_post_type', 0);
function wp_maps_create_locations_post_type() {
  $labels = array(
    'name'                  => _x('Locations', 'Post Type General Name', 'wp-maps'),
    'singular_name'         => _x('Location', 'Post Type Singular Name', 'wp-maps'),
    'menu_name'             => __('Locations', 'wp-maps'),
    'all_items'             => __('All Locations', 'wp-maps'),
    'add_new_item'          => __('Add New Location', 'wp-maps'),
    'edit_item'             => __('Edit Location', 'wp-maps'),
    'new_item'              => __('New Location', 'wp-maps'),
    'view_item'             => __('View Location', 'wp-maps'),
    'search_items'          => __('Search Location', 'wp-maps'),
    'not_found'             => __('Not found', 'wp-maps'),
    'featured_image'        => __('Featured Image', 'wp-maps'),
    'set_featured_image'    => __('Set featured image', 'wp-maps'),
    'remove_featured_image' => __('Remove featured image', 'wp-maps'),
    'insert_into_item'      => __('Insert into location', 'wp-maps'),
    'items_list'            => __('Locations list', 'wp-maps'),
    'items_list_navigation' => __('Locations list navigation', 'wp-maps'),
  );

  $args = array(
    'label'                 => __('Location', 'wp-maps'),
    'description'           => __('Post type for locations', 'wp-maps'),
    'labels'                => $labels,
    'supports'              => array('title', 'editor', 'thumbnail', 'revisions'),
    'taxonomies'            => array('location_category'),
    'public'                => true,
    'show_ui'               => true,
    'menu_position'         => 5,
    'menu_icon'             => 'dashicons-location-alt',
    'has_archive'           => true,
    'publicly_queryable'    => true,
  );

  register_post_type('locations', $args);
}

/**
 * Registers the "Location Categories" custom taxonomy.
 *
 * This function creates a hierarchical custom taxonomy called "Location Categories" 
 * and associates it with the "Locations" custom post type. It includes various labels 
 * for the taxonomy interface and displays the taxonomy in the admin UI and on the admin 
 * columns.
 *
 * @return void
 */

add_action('init', 'wp_maps_create_location_categories_taxonomy', 0);
function wp_maps_create_location_categories_taxonomy() {
  $labels = array(
    'name'              => _x('Location Categories', 'taxonomy general name', 'wp-maps'),
    'singular_name'     => _x('Location Category', 'taxonomy singular name', 'wp-maps'),
    'search_items'      => __('Search Location Categories', 'wp-maps'),
    'all_items'         => __('All Location Categories', 'wp-maps'),
    'edit_item'         => __('Edit Location Category', 'wp-maps'),
    'update_item'       => __('Update Location Category', 'wp-maps'),
    'add_new_item'      => __('Add New Location Category', 'wp-maps'),
    'menu_name'         => __('Location Categories', 'wp-maps'),
  );

  $args = array(
    'labels'            => $labels,
    'hierarchical'      => true,
    'public'            => true,
    'show_ui'           => true,
    'show_admin_column' => true,
  );

  register_taxonomy('location_category', array('locations'), $args);
}

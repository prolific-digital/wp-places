<?php

/**
 * Block template file: block.php
 *
 * Map Block Template.
 *
 * @param   array $block The block settings and attributes.
 * @param   string $content The block inner HTML (empty).
 * @param   bool $is_preview True during AJAX preview.
 * @param   (int|string) $post_id The post ID this block is saved to.
 */

// Create id attribute allowing for custom "anchor" value.
$id = 'map-' . $block['id'];
if (! empty($block['anchor'])) {
  $id = $block['anchor'];
}

// Create class attribute allowing for custom "className" and "align" values.
$classes = 'block-map';
if (! empty($block['className'])) {
  $classes .= ' ' . $block['className'];
}
if (! empty($block['align'])) {
  $classes .= ' align' . $block['align'];
}
?>

<style type="text/css">
  <?php echo '#' . $id; ?> {
    /* Add styles that use ACF values here */
  }
</style>

<div id="<?php echo esc_attr($id); ?>" class="<?php echo esc_attr($classes); ?>">

  <?php if ($is_preview) : ?>
    <div id="facet-map-<?php echo esc_attr($id); ?>" class="editor-message">
      <div class="message">
        <p>Map will render here on the frontend. <br>Preview this page on the frontend to see your map.</p>
      </div>
    </div>
  <?php else : ?>

    <button class="toggle-panel" aria-expanded="false" aria-controls="facet-filter-group-<?php echo esc_attr($id); ?>">
      <?php echo get_field('toggle_button_text', 'option') ? get_field('toggle_button_text', 'option') : 'Filter Locations'; ?>
    </button>

    <div id="facet-map-<?php echo esc_attr($id); ?>" class="facet custom-map">
      <?php echo facetwp_display('facet', 'location_map'); ?>
    </div>

    <div id="facet-filter-group-<?php echo esc_attr($id); ?>" class="facet panel" role="region" aria-label="Filter locations panel">

      <div class="panel-content">
        <?php
        echo facetwp_display('facet', 'location_proximity');
        echo facetwp_display('facet', 'location_categories');
        ?>

        <div class="location-listing">
          <div class="facetwp-template">
            <?php
            // WP_Query to retrieve all Locations posts
            $args = array(
              'post_type'      => 'locations',
              'posts_per_page' => -1, // Retrieve all posts
              'facetwp' => true
            );

            $locations_query = new WP_Query($args);

            if ($locations_query->have_posts()) {
              while ($locations_query->have_posts()) {
                $locations_query->the_post();

                // Get the post id
                $post_id = get_the_ID();

                // Get the address fields
                $address = get_field('address', $post_id);
                $phone_number = get_field('phone_number', $post_id);
                $details = get_field('details', $post_id);
                $view_more = get_field('view_more', $post_id);

                // Get the location categories (assuming a custom taxonomy 'location_category')
                $location_categories = get_the_terms($post_id, 'location_category');

                echo '<div class="location-item">';

                // Output location categories if they exist
                if ($location_categories && !is_wp_error($location_categories)) {
                  echo '<div class="location-categories">';
                  foreach ($location_categories as $category) {
                    echo '<span class="location-category">' . esc_html($category->name) . '</span> ';
                  }
                  echo '</div>';
                }

                // Output location title with link if available
                if ($view_more) {
                  echo '<h2 class="location-title"><a href="' . esc_url($view_more['url']) . '">' . get_the_title() . '</a></h2>';
                } else {
                  echo '<h2 class="location-title">' . get_the_title() . '</h2>';
                }


                // Output address if it exists
                if ($address) {
                  echo '<address class="location-address">';
                  if (isset($address['street_number']) && isset($address['street_name'])) {
                    echo $address['street_number'] . ' ' . $address['street_name'] . '<br>';
                  }
                  if (isset($address['city']) && isset($address['state']) && isset($address['post_code'])) {
                    echo $address['city'] . ', ' . $address['state'] . ' ' . $address['post_code'];
                  }
                  echo '</address>';
                }

                // Output phone number if it exists
                if ($phone_number) {
                  echo '<div class="location-phone">';
                  echo '<a href="tel:' . esc_url($phone_number['url']) . '">' . esc_html($phone_number['title']) . '</a>';
                  echo '</div>';
                }

                // Output details if available
                if ($details) {
                  echo '<div class="location-details">' . $details . '</div>';
                }

                // Output "View More" link if available
                if ($view_more) {
                  echo '<div class="location-view-more">';
                  echo '<a href="' . esc_url($view_more['url']) . '" target="' . esc_attr($view_more['target']) . '">' . esc_html($view_more['title']) . '</a>';
                  echo '</div>';
                }

                echo '</div>'; // Close location item
              }
            } else {
              echo '<p>No locations found.</p>';
            }

            // Reset post data
            wp_reset_postdata();
            ?>
          </div>
        </div>
      </div>
    </div>

  <?php endif; ?>

</div>
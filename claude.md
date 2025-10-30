# WP Places Plugin Documentation

## Overview

The WP Places plugin creates an interactive map system for displaying and filtering parks and facilities. It uses a custom post type "Places" with taxonomies for categorization, Advanced Custom Fields (ACF) for content management, and FacetWP for filtering functionality.

## Project Structure

```
wp-places-initial-alpha/
├── places.php                 # Main plugin file
├── acf-fields.php            # ACF field definitions
├── single-places.php         # Single place template
├── blocks/
│   └── map/
│       └── block.php         # Interactive map block
├── screenshot.jpg            # Template design reference
└── pin-preview.jpg          # Map marker design reference
```

## Custom Post Type: Places

### Registration
- **Post Type:** `places`
- **Slug:** `places/%location_category%`
- **URL Structure:** `/places/park/post-name` or `/places/facility/post-name`
- **Supports:** title, editor, thumbnail, custom-fields, revisions
- **Menu Position:** 5
- **Menu Icon:** `dashicons-location`

### Permalink Structure
The plugin uses a dynamic permalink structure that includes the location category in the URL:
- Parks: `/places/park/{post-name}`
- Facilities: `/places/facility/{post-name}`

This is achieved through:
1. Rewrite rule with `%location_category%` placeholder
2. Custom `post_type_link` filter that replaces the placeholder with the actual category slug

## Taxonomies

### 1. Location Category (Primary)
- **Slug:** `location_category`
- **Hierarchical:** Yes
- **Purpose:** Main categorization (Park, Facility)
- **Used in:** URLs, badges, filtering
- **Show in REST:** Yes

### 2. Location Type
- **Slug:** `location_type`
- **Hierarchical:** Yes
- **Purpose:** Sub-categorization (e.g., Community Center, Sports Complex)
- **FacetWP:** Checkboxes facet

### 3. Amenities
- **Slug:** `amenities`
- **Hierarchical:** Yes
- **Purpose:** Available amenities (e.g., Parking, Restrooms, WiFi)
- **FacetWP:** Checkboxes facet

### 4. Activities
- **Slug:** `activities`
- **Hierarchical:** Yes
- **Purpose:** Available activities (e.g., Swimming, Basketball, Hiking)
- **FacetWP:** Checkboxes facet

**Note:** All taxonomies are hierarchical to enable checkbox display in WordPress Quick Edit.

## ACF Field Groups

### CPT - Places
**Location Rule:** Post Type is equal to Places

#### Field Structure

1. **Gallery** (`gallery`)
   - Type: Gallery
   - Purpose: Multiple images for carousel display
   - Return Format: Array

2. **About Content** (`about_content`)
   - Type: WYSIWYG Editor
   - Toolbar: Full
   - Purpose: Main descriptive content

3. **Hours Display** (`hours_display`)
   - Type: Repeater
   - Sub-field: `hours` (Text)
   - Purpose: Display operating hours
   - Example: "Mon - Fri: 9:00am - 5:00pm"

4. **Contact Info** (Group: `contact_info`)
   - **Phone Number** (`phone_number`)
     - Type: Link
     - Return Format: Array
   - **Contact Email** (`contact_email`)
     - Type: Link
     - Return Format: Array

5. **Social Media Links** (`social_media_links`)
   - Type: Repeater
   - Sub-fields:
     - `platform_name` (Text)
     - `icon` (Image) - Upload custom social icons
     - `url` (URL)

6. **Address** (`address`)
   - Type: Google Map
   - Purpose: Location display and map rendering
   - Returns: Array with lat, lng, address

7. **CTA Button** (`cta_button`)
   - Type: Link
   - Purpose: Primary call-to-action
   - Example: "Register Now", "Book a Tour"

### Map Settings Options Page
**Location:** Under Places menu (`edit.php?post_type=places`)

Fields for configuring the map display and default settings.

## FacetWP Integration

### Facets Configuration

1. **Location Proximity** (`location_proximity`)
   - Type: Proximity
   - Source: Address (ACF field)

2. **Location Categories** (`location_categories`)
   - Type: Checkboxes
   - Source: Location Category taxonomy

3. **Location Types** (`location_types`)
   - Type: Checkboxes
   - Source: Location Type taxonomy

4. **Amenities** (`amenities`)
   - Type: Checkboxes
   - Source: Amenities taxonomy

5. **Activities** (`activities`)
   - Type: Checkboxes
   - Source: Activities taxonomy

6. **Places Map** (`places_map`)
   - Type: Map
   - Source: Address (ACF field)

## Map Implementation

### Interactive Map Block (`blocks/map/block.php`)

The map block displays all places with filtering capabilities:

```php
// Query all places
$places = new WP_Query(array(
    'post_type' => 'places',
    'posts_per_page' => -1,
    'facetwp' => true
));

// Display facets
echo facetwp_display('facet', 'location_proximity');
echo facetwp_display('facet', 'location_categories');
echo facetwp_display('facet', 'location_types');
echo facetwp_display('facet', 'amenities');
echo facetwp_display('facet', 'activities');

// Display map
echo facetwp_display('facet', 'places_map');
```

### Map Marker Popup

Custom marker content filter creates detailed popups:

**Structure:**
- Featured image with category badge overlay
- Place title
- Hours display with clock icon (SVG)
- Address with location pin icon (SVG)
- "VIEW DETAILS" button linking to single page

**Implementation:**
```php
add_filter('facetwp_map_marker_args', function ($args, $post_id) {
    // Build marker HTML with image, icons, and button
    $args['content'] = $marker_html;
    return $args;
}, 10, 2);
```

## Single Place Template

### Template: `single-places.php`
**Template Name:** Single Place
**Template Post Type:** places

### Layout Structure

```
┌─────────────────────────────────────────────┐
│ Header                                      │
├─────────────────────────────────────────────┤
│ Category Badge + Title                      │
├─────────────────────────────────────────────┤
│ Gallery Carousel                            │
├─────────────────────────────────────────────┤
│ Tab Navigation                              │
│ [About] [Memberships] [Features] etc.       │
├─────────────────────────────────────────────┤
│                          │                  │
│   Main Content           │   Sidebar        │
│   (Tab Content)          │   - Hours        │
│                          │   - Contact      │
│                          │   - Socials      │
│                          │   - Map          │
│                          │   - CTA Button   │
└─────────────────────────────────────────────┘
```

### Features

#### 1. Gallery Carousel
- Displays all images from Gallery ACF field
- Placeholder for navigation dots
- Responsive image display

#### 2. Tab Navigation
Seven tabs for content organization:
- About (active by default)
- Memberships
- Features & Rates
- Amenities
- Programs
- Rentals
- Events

**Note:** Currently only "About" tab has content. Others show "coming soon" placeholders.

#### 3. Sidebar Sections

**Hours Section:**
- Clock SVG icon
- Displays repeater field items
- Conditional display (only if hours exist)

**Contact Info Section:**
- Phone number with phone icon (SVG)
- Email link with email icon (SVG)
- Both use ACF Link field format

**Socials Section:**
- Displays uploaded icon images
- External links with `rel="noopener"`
- Fallback to text if no icon uploaded

**Location Map Section:**
- Google Maps integration
- Address display with pin icon (SVG)
- Interactive map at 300px height
- Vanilla JavaScript implementation

**CTA Button:**
- Prominent call-to-action
- Configurable link and text
- Supports external/internal links

### Map Rendering (Vanilla JS)

The template includes inline JavaScript for Google Maps rendering:

```javascript
(function() {
  function waitForGoogleMaps() {
    if (typeof google !== 'undefined' && typeof google.maps !== 'undefined') {
      initAllMaps();
    } else {
      setTimeout(waitForGoogleMaps, 100); // Poll every 100ms
    }
  }

  // Start waiting for Google Maps API
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', waitForGoogleMaps);
  } else {
    waitForGoogleMaps();
  }
})();
```

**Key Features:**
- No jQuery dependency (pure vanilla JS)
- Polls for Google Maps API availability
- Handles asynchronous API loading
- Auto-centers map on marker
- Sets zoom level to 16 for single markers

## Data Migration

### Post Type Migration Function

When the post type was changed from "locations" to "places", a migration function was created:

```php
function wp_maps_migrate_locations_to_places() {
    global $wpdb;
    $updated = $wpdb->update(
        $wpdb->posts,
        array('post_type' => 'places'),
        array('post_type' => 'locations'),
        array('%s'),
        array('%s')
    );
    // Flush rewrite rules
    flush_rewrite_rules();
}
```

**Admin Notice:**
- Displays banner in admin with "Migrate Now" button
- Migration preserves all post data, meta fields, taxonomies
- Only updates `post_type` column in database
- Dismissible after migration completes

## Technical Decisions & Solutions

### 1. Hierarchical Taxonomies for Quick Edit
**Problem:** Non-hierarchical taxonomies show as text input in Quick Edit.
**Solution:** Made all taxonomies hierarchical to enable checkbox display in WordPress admin.

### 2. Category-Based URLs
**Problem:** Need URLs like `/places/park/post-name`.
**Solution:**
- Use `%location_category%` placeholder in rewrite slug
- Filter `post_type_link` to replace placeholder with actual category slug
- Falls back to post name if no category assigned

### 3. jQuery Dependency Issue
**Problem:** "jQuery is not defined" error in single template.
**Solution:** Converted all map JavaScript to vanilla JS using:
- `document.querySelectorAll()` instead of `$()`
- `getAttribute()` instead of `.data()`
- `forEach()` instead of `.each()`
- `addEventListener()` instead of jQuery event handlers

### 4. Google Maps Async Loading
**Problem:** "google is not defined" - API loads asynchronously.
**Solution:** Implemented polling function that checks every 100ms for `google.maps` availability before initializing maps.

### 5. Marker Category Badge Placement
**Problem:** Category badge needed to overlay the featured image.
**Solution:** Placed badge inside `.marker-image` div for proper CSS positioning.

## Icon SVG Assets

The plugin uses inline SVG icons for:
- Clock (hours display)
- Phone (contact)
- Email (contact)
- Location pin (address)

Benefits:
- No external dependencies
- Easily styled with CSS
- Scalable vector graphics
- Inline for performance

## Dependencies

### Required Plugins
1. **Advanced Custom Fields (ACF) Pro**
   - Gallery field
   - Google Map field
   - Repeater field
   - Group field
   - WYSIWYG field
   - Link field

2. **FacetWP**
   - Map facet
   - Checkboxes facets
   - Proximity facet
   - Filtering engine

### WordPress Requirements
- WordPress 5.0+
- PHP 7.4+
- Google Maps API key (configured in ACF settings)

## Best Practices Implemented

1. **Security:**
   - All output escaped with `esc_html()`, `esc_url()`, `esc_attr()`
   - Nonce verification for admin actions
   - Proper capability checks

2. **Performance:**
   - Inline critical JavaScript (no external file needed)
   - SVG icons inline (no HTTP requests)
   - Efficient database queries
   - Conditional loading of map scripts

3. **Accessibility:**
   - Proper ARIA labels
   - Semantic HTML structure
   - Alt text for images
   - Keyboard navigation support

4. **Code Organization:**
   - Separate ACF fields definition
   - Modular function structure
   - Clear comments and documentation
   - Consistent naming conventions

## Future Enhancements

### Potential Improvements

1. **Tab Content Management:**
   - Add ACF fields for remaining tabs (Memberships, Features, etc.)
   - Make tabs dynamic based on available content
   - Hide empty tabs automatically

2. **Gallery Carousel:**
   - Implement JavaScript for carousel navigation
   - Add auto-play option
   - Touch/swipe support for mobile
   - Thumbnail navigation

3. **Map Customization:**
   - Custom map styles
   - Clustered markers for better performance
   - Info window customization
   - Directions integration

4. **Filtering:**
   - Save filter preferences
   - Filter presets
   - Export filtered results
   - Print-friendly filtered list

5. **Performance:**
   - Lazy load images
   - Cache marker content
   - AJAX pagination for places list
   - Image optimization

## Troubleshooting

### Map Not Rendering
1. Check if Google Maps API key is set in ACF settings
2. Verify lat/lng coordinates are saved for the place
3. Check browser console for JavaScript errors
4. Ensure `.acf-map` container has explicit height set

### Permalink 404 Errors
1. Go to Settings > Permalinks
2. Click "Save Changes" to flush rewrite rules
3. Verify location_category terms exist
4. Check if post has category assigned

### Taxonomy Not Showing as Checkboxes
1. Verify taxonomy is registered as hierarchical
2. Clear any caching plugins
3. Check if terms exist in the taxonomy
4. Try deactivating/reactivating plugin

### Missing ACF Fields
1. Ensure ACF Pro is installed and activated
2. Check field group location rules
3. Verify field keys match in code
4. Re-sync ACF fields if using JSON sync

## Development Notes

### When Adding New Taxonomies
1. Register in `places.php` with `register_taxonomy()`
2. Set `hierarchical => true` for checkbox display
3. Add to FacetWP facets JSON
4. Display in map block with `facetwp_display()`
5. Consider adding to marker popup content

### When Adding New ACF Fields
1. Define in `acf-fields.php` with unique field key
2. Update single template to display the field
3. Consider adding to map marker popup
4. Document in this file

### When Modifying Map JavaScript
1. Always use vanilla JS (no jQuery)
2. Test async Google Maps API loading
3. Handle cases where API fails to load
4. Wrap in IIFE to avoid global scope pollution

## Version History

### Initial Alpha
- Custom post type "Places" created
- Four taxonomies implemented
- ACF field structure defined
- Interactive map with FacetWP filtering
- Single place template with gallery and tabs
- Vanilla JS Google Maps integration
- Map marker popups with rich content

---

**Last Updated:** 2025-10-30
**Plugin Version:** Initial Alpha
**Author:** Custom Development

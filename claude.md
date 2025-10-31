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

The plugin uses two separate field groups for better organization:

### 1. CPT - Places: Basic Info
**Location Rule:** Post Type is equal to Places
**Purpose:** Core place information and metadata

#### Field Structure (in order):

1. **Address** (`address`)
   - Type: Google Map
   - Purpose: Location display and map rendering
   - Returns: Array with lat, lng, address

2. **About Content** (`about_content`)
   - Type: WYSIWYG Editor
   - Toolbar: Full
   - Purpose: Main descriptive content for About tab
   - Media Upload: Yes

3. **Gallery** (`gallery`)
   - Type: Gallery
   - Purpose: Multiple images for carousel display
   - Return Format: Array

4. **Hours Display** (`hours_display`)
   - Type: Repeater
   - Sub-field: `hours` (Text)
   - Purpose: Display operating hours
   - Min Rows: 1 (starts with empty row)
   - Example: "Mon - Fri: 9:00am - 5:00pm"

5. **Contact Info** (Group: `contact_info`)
   - **Phone Number** (`phone_number`)
     - Type: Link
     - Return Format: Array
   - **Contact Email** (`contact_email`)
     - Type: Link
     - Return Format: Array

6. **Social Media Links** (`social_media_links`)
   - Type: Repeater
   - Min Rows: 1 (starts with empty row)
   - Sub-fields:
     - `platform_name` (Text)
     - `icon` (Image) - Upload custom social icons
     - `url` (URL)

7. **CTA Button** (`cta_button`)
   - Type: Link
   - Purpose: Primary call-to-action
   - Example: "Register Now", "Book a Tour"

### 2. CPT - Places: Tab Sections
**Location Rule:** Post Type is equal to Places
**Purpose:** Configure frontend tabs and their content

**Backend Structure:** Each tab section is wrapped in a collapsible accordion for better UX. Editors can collapse sections they're not working on to reduce scrolling.

#### Accordion Sections:

##### Membership Section (Collapsible)
- **Show Membership Tab** (`show_membership`)
  - Type: True/False
  - UI Toggle: On/Off
  - Default: Off

- **Membership** (`membership`) - *Conditional: Shows when toggle ON*
  - Type: Repeater
  - Min Rows: 1
  - Layout: Block
  - Sub-fields:
    - `title` (Text) - Required
    - `details` (WYSIWYG, Full toolbar)
    - `button` (Link) - Optional CTA

##### Features & Rates Section (Collapsible)
- **Show Features & Rates Tab** (`show_features_rates`)
  - Type: True/False
  - UI Toggle: On/Off
  - Default: Off

- **Features & Rates** (`features_rates`) - *Conditional: Shows when toggle ON*
  - Type: Repeater (Sections)
  - Min Rows: 1
  - Layout: Block
  - Sub-fields:
    - `section_title` (Text)
    - `show_section_title` (True/False) - Toggle title visibility
    - `show_section_description` (True/False) - Toggle description visibility
    - **Feature Blocks** (Nested Repeater)
      - Min Rows: 1
      - Sub-fields:
        - `block_title` (WYSIWYG, Basic toolbar)
        - **Items** (Nested Repeater)
          - Min Rows: 1
          - Sub-field: `item` (Text)
    - `section_description` (Text)

##### Amenities Section (Collapsible)
- **Show Amenities Tab** (`show_amenities`)
  - Type: True/False
  - UI Toggle: On/Off
  - Default: Off

- **Amenities** (`amenities_tab`) - *Conditional: Shows when toggle ON*
  - Type: Repeater
  - Min Rows: 1
  - Layout: Block
  - Note: Distinct from Amenities taxonomy (this is for narrative content)
  - Sub-fields:
    - `title` (Text) - Required
    - `description` (Textarea)

##### Programs Section (Collapsible)
- **Show Programs Tab** (`show_programs`)
  - Type: True/False
  - UI Toggle: On/Off
  - Default: Off

- **Programs** (`programs`) - *Conditional: Shows when toggle ON*
  - Type: Text
  - Purpose: Shortcode input from scheduling system
  - Placeholder: `[program_schedule id="123"]`

##### Rentals Section (Collapsible)
- **Show Rentals Tab** (`show_rentals`)
  - Type: True/False
  - UI Toggle: On/Off
  - Default: Off

- **Rentals** (`rentals`) - *Conditional: Shows when toggle ON*
  - Type: Relationship
  - Post Type: `rentals`
  - Return Format: Object
  - Purpose: Link to Rentals CPT entries
  - Note: Rentals CPT must be created separately

##### Events Section (Collapsible)
- **Show Events Tab** (`show_events`)
  - Type: True/False
  - UI Toggle: On/Off
  - Default: Off

- **Events** (`events_tab`) - *Conditional: Shows when toggle ON*
  - Type: Group
  - Sub-fields:
    - `event_selection_mode` (Radio)
      - Options: "By Venue", "By Proximity", "By Linked Location"
      - Default: "venue"
    - `tec_venue` (Post Object) - Conditional on mode = venue
      - Post Type: `tribe_venue`
    - `linked_location` (Post Object) - Conditional on mode = location
    - `max_events` (Number)
      - Default: 3
      - Min: 1, Max: 20

##### FAQs Section (Collapsible)
- **Show FAQs Tab** (`show_faqs`)
  - Type: True/False
  - UI Toggle: On/Off
  - Default: Off

- **FAQs** (`faqs`) - *Conditional: Shows when toggle ON*
  - Type: Repeater
  - Min Rows: 1
  - Layout: Block
  - Sub-fields:
    - `title` (Text) - Required (The question)
    - `content` (WYSIWYG, Full toolbar) - The answer

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

## Backend Editor Experience

### Field Organization
When editing a Place, editors see two metaboxes:

1. **CPT - Places: Basic Info**
   - Shows all basic metadata fields
   - Field order: Address → About Content → Gallery → Hours → Contact → Socials → CTA

2. **CPT - Places: Tab Sections**
   - Shows collapsible accordion sections
   - Each section contains a toggle + content fields
   - Sections start collapsed by default
   - Multi-expand enabled (can open multiple sections at once)

### Collapsible Accordion Workflow
```
▼ Membership Section
  └─ Show Membership Tab [Toggle]
  └─ Membership (repeater) [appears when toggle ON]

▼ Features & Rates Section
  └─ Show Features & Rates Tab [Toggle]
  └─ Features & Rates (repeater) [appears when toggle ON]

... (7 sections total)
```

**Benefits:**
- **Focus:** Collapse sections not being edited
- **Reduced Scrolling:** Even with all tabs active, editors can minimize visual clutter
- **Clear Structure:** Each tab's toggle and content are grouped together
- **Conditional Rendering:** Content fields only show when their toggle is ON

### Repeater Defaults
All repeater fields start with 1 empty row, so editors can immediately begin adding content without clicking "Add Row" first.

## JavaScript Implementation

### Single Place Interactions (`js/single-place.js`)

**File Location:** `/js/single-place.js`
**Enqueued:** Only on single place pages (`is_singular('places')`)
**Dependencies:** None (pure vanilla JavaScript)

#### Features:
1. **Tab Switching**
   - Click tab buttons to switch content
   - Only one tab active at a time
   - Smooth content transitions

2. **Keyboard Navigation**
   - Arrow Left/Right: Navigate between tabs
   - Arrow Up/Down: Navigate between tabs
   - Home: Jump to first tab
   - End: Jump to last tab

3. **Deep Linking**
   - URL hash support (e.g., `#membership`)
   - Opens specific tab on page load
   - Updates URL when switching tabs
   - Browser back/forward button support

4. **FAQ Accordions**
   - Click to expand/collapse answers
   - Proper ARIA disclosure pattern
   - Keyboard accessible (Enter/Space keys)

5. **ARIA Implementation**
   - Full WAI-ARIA tab pattern
   - `role="tablist"`, `role="tab"`, `role="tabpanel"`
   - `aria-selected`, `aria-controls`, `aria-labelledby`
   - `aria-expanded` for FAQ items
   - Focus management

6. **Print Support**
   - All tab content expands for printing
   - FAQs expand for printing
   - Restores state after print

#### Code Structure:
```javascript
// Tab management
- initTabs()
- activateTab()
- handleTabKeydown()
- updateURL()

// FAQ management
- initFAQs()
- toggleFAQ()

// Print support
- beforeprint event
- afterprint event
```

## Version History

### Phase 2: Tab System Implementation (Current)
- Reorganized ACF into two field groups (Basic Info + Tab Sections)
- Added 7 new tab sections with toggle controls
- Implemented collapsible accordion UI in backend
- Added conditional field rendering based on toggles
- Created comprehensive tab content fields:
  - Membership (repeater)
  - Features & Rates (nested repeaters)
  - Amenities (repeater)
  - Programs (shortcode)
  - Rentals (relationship)
  - Events (TEC integration)
  - FAQs (repeater with accordion)
- Built vanilla JS tab system with keyboard navigation and deep linking
- Moved About Content and Gallery to Basic Info
- Set all repeaters to min 1 row for better UX

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
**Plugin Version:** Alpha (Phase 2)
**Author:** Custom Development

# Places

**Contributors:** Prolific Digital  
**Tags:** maps, locations, custom post type, facets, ACF, FacetWP, Google Maps  
**Requires at least:** 5.0  
**Tested up to:** 6.0  
**Requires PHP:** 7.2  
**License:** GPLv2 or later  
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html

A custom plugin for managing locations with categories and displaying them using FacetWP, ACF Pro, and FacetWP Maps.

## Description

Places is a WordPress plugin designed to manage and display locations with interactive maps. It integrates **Advanced Custom Fields (ACF Pro)**, **FacetWP**, and **FacetWP Maps** to filter and visualize locations on maps. The plugin automatically creates the necessary ACF fields and facets, making setup easy and fast.

### Features:

- **Custom Post Type:** Manage locations with a custom post type.
- **Custom Taxonomy:** Categorize locations using a custom taxonomy.
- **ACF & FacetWP Integration:** Display locations on interactive maps using **ACF Pro** and **FacetWP Maps**.
- **Proximity Search:** Search for locations using proximity-based filtering.
- **Customizable Markers:** Upload and use custom map marker icons.
- **Custom Map Styles:** Add custom map styles using Google Maps JSON styling.
- **Frontend & Backend Maps:** Maps display in both the frontend and backend (location edit screen).

### Dependencies

The following plugins are **required** for Places to function:

1. **[ACF Pro](https://www.advancedcustomfields.com/pro/):** Advanced Custom Fields Pro is required for managing custom fields, including map data.
2. **[FacetWP](https://facetwp.com/):** FacetWP is used for filtering and displaying locations with proximity search.
3. **[FacetWP Maps](https://facetwp.com/add-ons/maps/):** This add-on is required to display interactive maps and map markers.

## Installation

1. Upload the Places plugin to the `/wp-content/plugins/` directory, or install it via the WordPress Plugins screen.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Ensure that **ACF Pro**, **FacetWP**, and **FacetWP Maps** are installed and activated.
4. Once all dependencies and Places are activated, navigate to the **Locations** settings screen (found under the "Locations" post type) and add your **Google Maps API key** for maps to appear on both the frontend and backend.

### Dependencies Setup

- **ACF Fields:** Custom fields for locations, such as addresses, phone numbers, map markers, and more, are automatically created by this plugin.
- **Facets:** Facets for proximity search, location categories, and map display are also defined and auto-created by the plugin.

## Usage

1. After activation, the **Locations** custom post type will appear in your dashboard.
2. Create new locations by adding titles, addresses, phone numbers, and other metadata.
3. The plugin will automatically display these locations on maps, with filters for proximity and categories via FacetWP.
4. Ensure your **Google Maps API key** is added in the Locations settings for maps to render.

## Frequently Asked Questions

### Does this plugin work without ACF Pro?

No, **ACF Pro** is a required dependency for Places to function correctly.

### Can I use this plugin without FacetWP or FacetWP Maps?

No, **FacetWP** and **FacetWP Maps** are required for filtering locations and displaying maps.

### How do I add the Google Maps API key?

Navigate to the **Locations** settings screen, where you can enter your API key for maps to display in both the frontend and backend.

### Are the ACF fields and facets automatically created?

Yes, all necessary **ACF fields** and **facets** are defined within the plugin and are created automatically upon activation.

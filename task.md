# Map Filter Enhancement Task

## Current Goal
- Recreate the filter shell from `screenshot.jpg` around the FacetWP-powered map so visitors can search, open filters, and apply taxonomy refinements.
- Preserve full keyboard accessibility and ARIA semantics while integrating with existing facets (`Location Type`, `Amenities`, `Activities`, proximity, etc.).

## Strategy
1. **Audit + Data Mapping**
   - Confirm which FacetWP facets already exist and how they render inside `blocks/map/block.php`.
   - Identify new UI elements required by the mock (overlay panel, search field, filter toggle, close button, collapsible facet sections, submit button).

2. **Markup Restructure**
   - Wrap the map, search bar, and filter panel in a container that supports the mock’s layout (map full width with floating search/filter controls).
   - Replace the current `toggle-panel` button + panel markup with semantic structure: search field with adjacent filter toggle, slide-out/overlay panel including close button and heading hierarchy.
   - Group facets into collapsible sections that align with mock labels (`Location Type`, `Amenities`, `Activities`, `Distance`, `Accessibility`, etc.), ensuring buttons for expand/collapse expose `aria-expanded` and `aria-controls`.

3. **Interaction Logic**
   - Update `blocks/map/view.js` (or add a new module) to handle:
     - Filter panel open/close via toggle button, close button, and Escape key.
     - Focus management (focus trap when open, restore focus on close).
     - Collapsible sections toggled via click/keyboard and ARIA state sync.
     - Search field input updates corresponding FacetWP search facet (introduce or repurpose a facet for keyword search).
   - Ensure interactions work with multiple map blocks on a page.

4. **Styling Plan**
   - Extend `blocks/map/map.css` (or dedicated stylesheet) to deliver:
     - Overlay panel styling, animation, and responsive behavior mirroring the screenshot.
     - Search bar + filter button styles.
     - Facet headings, checkbox lists, and CTA button visuals.
   - Consider CSS variables for colors/spacings to ease future tweaks.

5. **FacetWP Integration**
   - Configure/confirm a search facet for keyword filtering (may require PHP adjustment if not already output).
   - Verify FacetWP refresh triggers on search input change and filter interactions.
   - Ensure default state renders all places and that the filter button can show/hide without reloading facets unnecessarily.

6. **Accessibility + QA**
   - Validate keyboard path: tab order, Enter/Space activation, Escape to close panel, focus trap, and screen reader context (`role="dialog"` or `role="region"` as appropriate).
   - Test filtering scenarios (single/multiple facets, search + facets combined) and confirm the map/marker list updates correctly.
   - Cross-check mobile breakpoints and ensure panel/search remain functional on touch devices.

## Outstanding Questions
- Do we need additional facet groups beyond the current three, or should mock-only sections like `Distance`/`Accessibility` map onto existing FacetWP facets (or be deferred)?
- Should the “Filter” button inside the panel trigger a FacetWP refresh explicitly or simply close the panel?

_No coding performed yet; next step is to design the detailed component structure before implementation._

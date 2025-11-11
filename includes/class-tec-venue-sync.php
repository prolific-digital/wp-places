<?php
/**
 * The Events Calendar Venue Sync
 *
 * Handles automatic syncing between Places and TEC Venues:
 * - When a place is saved, it creates/updates a corresponding TEC venue
 * - Maps place data (address, phone, CTA) to venue fields
 * - Maintains bidirectional link between place and venue
 * - Enables "Show Map" and "Show Map Link" by default
 *
 * @package WP_Places
 */

namespace WP_Places;

/**
 * Class TEC_Venue_Sync
 */
class TEC_Venue_Sync {

	/**
	 * Flag to prevent infinite loops during recursive saves
	 *
	 * @var bool
	 */
	private static $syncing = false;

	/**
	 * Initialize hooks
	 */
	public static function init() {
		// Hook into place save process
		add_action( 'acf/save_post', array( __CLASS__, 'sync_place_to_venue' ), 20 );

		// Admin notices
		add_action( 'admin_notices', array( __CLASS__, 'show_sync_notice' ) );

		// AJAX handler for one-time migration
		add_action( 'wp_ajax_tec_venue_migration', array( __CLASS__, 'ajax_migrate_venues' ) );

		// AJAX handler for dismissing migration notice
		add_action( 'wp_ajax_tec_venue_migration_dismiss', array( __CLASS__, 'ajax_dismiss_notice' ) );

		// Show migration notice
		add_action( 'admin_notices', array( __CLASS__, 'show_migration_notice' ) );
	}

	/**
	 * Sync place to TEC venue when place is saved
	 *
	 * @param int $post_id The post ID being saved.
	 */
	public static function sync_place_to_venue( $post_id ) {
		// Prevent infinite loops
		if ( self::$syncing ) {
			return;
		}

		// Ignore autosaves and revisions
		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		// Only proceed for places
		if ( get_post_type( $post_id ) !== 'places' ) {
			return;
		}

		// Check if TEC is active
		if ( ! class_exists( 'Tribe__Events__Main' ) ) {
			return;
		}

		// Set syncing flag
		self::$syncing = true;

		// Get existing venue ID if one is linked
		$venue_id = get_post_meta( $post_id, '_linked_tec_venue', true );

		// Get place data
		$place = get_post( $post_id );
		$address = get_field( 'address', $post_id );
		$contact_info = get_field( 'contact_info', $post_id );
		$cta_button = get_field( 'cta_button', $post_id );

		// Prepare venue data
		$venue_data = array(
			'post_title'  => $place->post_title,
			'post_status' => $place->post_status,
			'post_type'   => 'tribe_venue',
		);

		// Create or update venue
		if ( $venue_id && get_post_status( $venue_id ) ) {
			// Update existing venue
			$venue_data['ID'] = $venue_id;
			wp_update_post( $venue_data );
		} else {
			// Create new venue
			$venue_id = wp_insert_post( $venue_data );

			// Link venue back to place
			if ( $venue_id && ! is_wp_error( $venue_id ) ) {
				update_post_meta( $post_id, '_linked_tec_venue', $venue_id );
				update_post_meta( $venue_id, '_linked_place', $post_id );
			}
		}

		// If venue creation/update was successful, update meta fields
		if ( $venue_id && ! is_wp_error( $venue_id ) ) {
			// Parse address from ACF Google Map field
			if ( ! empty( $address ) && is_array( $address ) ) {
				// Full address string
				$full_address = isset( $address['address'] ) ? $address['address'] : '';

				// Parse address components
				$address_parts = self::parse_address( $full_address );

				// Update venue address fields
				update_post_meta( $venue_id, '_VenueAddress', $address_parts['street'] );
				update_post_meta( $venue_id, '_VenueCity', $address_parts['city'] );
				update_post_meta( $venue_id, '_VenueState', $address_parts['state'] );
				update_post_meta( $venue_id, '_VenueStateProvince', $address_parts['state'] );
				update_post_meta( $venue_id, '_VenueZip', $address_parts['zip'] );

				// Update coordinates
				if ( isset( $address['lat'] ) ) {
					update_post_meta( $venue_id, '_VenueLat', $address['lat'] );
				}
				if ( isset( $address['lng'] ) ) {
					update_post_meta( $venue_id, '_VenueLng', $address['lng'] );
				}
			}

			// Update phone number
			if ( ! empty( $contact_info['phone_number'] ) && is_array( $contact_info['phone_number'] ) ) {
				$phone = $contact_info['phone_number']['title'];
				update_post_meta( $venue_id, '_VenuePhone', $phone );
			}

			// Update website URL from CTA button
			if ( ! empty( $cta_button ) && is_array( $cta_button ) && ! empty( $cta_button['url'] ) ) {
				update_post_meta( $venue_id, '_VenueURL', $cta_button['url'] );
			}

			// Enable "Show Map" and "Show Map Link"
			update_post_meta( $venue_id, '_VenueShowMap', 'true' );
			update_post_meta( $venue_id, '_VenueShowMapLink', 'true' );

			// Set flag for admin notice
			set_transient( 'tec_venue_sync_notice_' . get_current_user_id(), 'place_synced', 30 );
		}

		// Reset syncing flag
		self::$syncing = false;
	}

	/**
	 * Parse address string into components
	 *
	 * Attempts to extract street, city, state, and zip from Google Maps address
	 *
	 * @param string $address Full address string.
	 * @return array Array with street, city, state, zip keys.
	 */
	private static function parse_address( $address ) {
		$parts = array(
			'street' => '',
			'city'   => '',
			'state'  => '',
			'zip'    => '',
		);

		if ( empty( $address ) ) {
			return $parts;
		}

		// Split by comma
		$segments = array_map( 'trim', explode( ',', $address ) );

		// Most common format: "Street, City, State Zip, Country"
		if ( count( $segments ) >= 3 ) {
			$parts['street'] = $segments[0];
			$parts['city']   = $segments[1];

			// Extract state and zip from third segment
			if ( isset( $segments[2] ) ) {
				// Match pattern: "State Zip" or "State ZIP-CODE"
				if ( preg_match( '/^([A-Z]{2})\s+(\d{5}(?:-\d{4})?)/', $segments[2], $matches ) ) {
					$parts['state'] = $matches[1];
					$parts['zip']   = $matches[2];
				} else {
					// If no match, just use the whole segment as state
					$parts['state'] = $segments[2];
				}
			}
		} elseif ( count( $segments ) === 2 ) {
			// Simpler format: "Street, City"
			$parts['street'] = $segments[0];
			$parts['city']   = $segments[1];
		} else {
			// Single segment, use as street
			$parts['street'] = $address;
		}

		return $parts;
	}

	/**
	 * Show admin notice after sync occurs
	 */
	public static function show_sync_notice() {
		$user_id = get_current_user_id();
		$notice_type = get_transient( 'tec_venue_sync_notice_' . $user_id );

		if ( ! $notice_type ) {
			return;
		}

		// Delete transient so it only shows once
		delete_transient( 'tec_venue_sync_notice_' . $user_id );

		if ( 'place_synced' === $notice_type ) {
			$message = __( 'Place saved and automatically synced to TEC venue.', 'wp-places' );
		} else {
			return;
		}

		printf(
			'<div class="notice notice-success is-dismissible"><p><strong>%s</strong> %s</p></div>',
			esc_html__( 'Venue Synced:', 'wp-places' ),
			esc_html( $message )
		);
	}

	/**
	 * Show migration notice for one-time venue deletion and sync
	 */
	public static function show_migration_notice() {
		// Only show to administrators
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Check if migration has been completed
		if ( get_option( 'tec_venue_migration_complete' ) ) {
			return;
		}

		// Check if user dismissed the notice
		if ( get_user_meta( get_current_user_id(), 'tec_venue_migration_dismissed', true ) ) {
			return;
		}

		// Check if TEC is active
		if ( ! class_exists( 'Tribe__Events__Main' ) ) {
			return;
		}

		?>
		<div class="notice notice-warning is-dismissible" id="tec-venue-migration-notice">
			<p>
				<strong><?php esc_html_e( 'TEC Venue Sync Installed', 'wp-places' ); ?></strong>
			</p>
			<p>
				<?php esc_html_e( 'Places will now automatically sync to The Events Calendar venues. Would you like to delete existing venues and create new ones from your places?', 'wp-places' ); ?>
			</p>
			<p>
				<strong><?php esc_html_e( 'Warning:', 'wp-places' ); ?></strong>
				<?php esc_html_e( 'This will delete ALL existing TEC venues and create new ones from your places. This action cannot be undone.', 'wp-places' ); ?>
			</p>
			<p>
				<button type="button" class="button button-primary" id="tec-venue-migrate">
					<?php esc_html_e( 'Delete Old Venues & Sync Places', 'wp-places' ); ?>
				</button>
				<button type="button" class="button" id="tec-venue-migrate-dismiss">
					<?php esc_html_e( 'Dismiss', 'wp-places' ); ?>
				</button>
				<span class="spinner" style="float: none; margin: 0 10px;"></span>
			</p>
		</div>
		<script>
		jQuery(document).ready(function($) {
			$('#tec-venue-migrate').on('click', function() {
				var $button = $(this);
				var $spinner = $button.siblings('.spinner');

				if (!confirm('<?php echo esc_js( __( 'Are you sure? This will DELETE ALL existing venues and create new ones from places. This cannot be undone!', 'wp-places' ) ); ?>')) {
					return;
				}

				$button.prop('disabled', true);
				$spinner.addClass('is-active');

				$.post(ajaxurl, {
					action: 'tec_venue_migration',
					nonce: '<?php echo esc_js( wp_create_nonce( 'tec_venue_migration' ) ); ?>'
				}, function(response) {
					$spinner.removeClass('is-active');
					if (response.success) {
						$('#tec-venue-migration-notice').addClass('notice-success').removeClass('notice-warning');
						$('#tec-venue-migration-notice p:last').html('<strong>' + response.data.message + '</strong>');
						setTimeout(function() {
							$('#tec-venue-migration-notice').fadeOut();
						}, 3000);
					} else {
						alert(response.data.message);
						$button.prop('disabled', false);
					}
				});
			});

			$('#tec-venue-migrate-dismiss').on('click', function() {
				$.post(ajaxurl, {
					action: 'tec_venue_migration_dismiss',
					nonce: '<?php echo esc_js( wp_create_nonce( 'tec_venue_migration_dismiss' ) ); ?>'
				});
				$('#tec-venue-migration-notice').fadeOut();
			});
		});
		</script>
		<?php
	}

	/**
	 * AJAX handler for migrating venues
	 */
	public static function ajax_migrate_venues() {
		check_ajax_referer( 'tec_venue_migration', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'wp-places' ) ) );
		}

		$result = self::migrate_venues();

		if ( $result['success'] ) {
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( $result );
		}
	}

	/**
	 * AJAX handler for dismissing migration notice
	 */
	public static function ajax_dismiss_notice() {
		check_ajax_referer( 'tec_venue_migration_dismiss', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die();
		}

		// Set user meta to hide notice permanently
		update_user_meta( get_current_user_id(), 'tec_venue_migration_dismissed', true );

		wp_send_json_success();
	}

	/**
	 * One-time migration: Delete all venues and create new ones from places
	 *
	 * @return array Result with success status and message.
	 */
	public static function migrate_venues() {
		global $wpdb;

		// Check if TEC is active
		if ( ! class_exists( 'Tribe__Events__Main' ) ) {
			return array(
				'success' => false,
				'message' => __( 'The Events Calendar is not active.', 'wp-places' ),
			);
		}

		$deleted_count = 0;
		$created_count = 0;

		// Set syncing flag to prevent hooks from firing during migration
		self::$syncing = true;

		// Step 1: Delete all existing venues
		$existing_venues = get_posts( array(
			'post_type'      => 'tribe_venue',
			'posts_per_page' => -1,
			'post_status'    => 'any',
		) );

		foreach ( $existing_venues as $venue ) {
			wp_delete_post( $venue->ID, true ); // Force delete
			$deleted_count++;
		}

		// Step 2: Create venues from all places
		$places = get_posts( array(
			'post_type'      => 'places',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
		) );

		foreach ( $places as $place ) {
			$place_id = $place->ID;

			// Get place data
			$address = get_field( 'address', $place_id );
			$contact_info = get_field( 'contact_info', $place_id );
			$cta_button = get_field( 'cta_button', $place_id );

			// Create venue
			$venue_data = array(
				'post_title'  => $place->post_title,
				'post_status' => 'publish',
				'post_type'   => 'tribe_venue',
			);

			$venue_id = wp_insert_post( $venue_data );

			if ( $venue_id && ! is_wp_error( $venue_id ) ) {
				// Link venue to place
				update_post_meta( $place_id, '_linked_tec_venue', $venue_id );
				update_post_meta( $venue_id, '_linked_place', $place_id );

				// Parse and update address
				if ( ! empty( $address ) && is_array( $address ) ) {
					$full_address = isset( $address['address'] ) ? $address['address'] : '';
					$address_parts = self::parse_address( $full_address );

					update_post_meta( $venue_id, '_VenueAddress', $address_parts['street'] );
					update_post_meta( $venue_id, '_VenueCity', $address_parts['city'] );
					update_post_meta( $venue_id, '_VenueState', $address_parts['state'] );
					update_post_meta( $venue_id, '_VenueStateProvince', $address_parts['state'] );
					update_post_meta( $venue_id, '_VenueZip', $address_parts['zip'] );

					if ( isset( $address['lat'] ) ) {
						update_post_meta( $venue_id, '_VenueLat', $address['lat'] );
					}
					if ( isset( $address['lng'] ) ) {
						update_post_meta( $venue_id, '_VenueLng', $address['lng'] );
					}
				}

				// Update phone
				if ( ! empty( $contact_info['phone_number'] ) && is_array( $contact_info['phone_number'] ) ) {
					update_post_meta( $venue_id, '_VenuePhone', $contact_info['phone_number']['title'] );
				}

				// Update URL
				if ( ! empty( $cta_button ) && is_array( $cta_button ) && ! empty( $cta_button['url'] ) ) {
					update_post_meta( $venue_id, '_VenueURL', $cta_button['url'] );
				}

				// Enable show map
				update_post_meta( $venue_id, '_VenueShowMap', 'true' );
				update_post_meta( $venue_id, '_VenueShowMapLink', 'true' );

				$created_count++;
			}
		}

		// Reset syncing flag
		self::$syncing = false;

		// Mark migration as complete
		update_option( 'tec_venue_migration_complete', true );

		return array(
			'success' => true,
			'message' => sprintf(
				/* translators: 1: deleted count, 2: created count */
				__( 'Migration complete! Deleted %1$d old venues and created %2$d new venues from places.', 'wp-places' ),
				$deleted_count,
				$created_count
			),
			'deleted' => $deleted_count,
			'created' => $created_count,
		);
	}
}

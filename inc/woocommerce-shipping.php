<?php
/**
 * Noyona Shipping — Zone × Weight matrix.
 *
 * Origin: Makati HQ (single online fulfillment branch, docs/order-tracking-approach.md §7).
 * Active carrier: J&T Express only. LBC Express is kept in code but disabled at
 * checkout per client direction — flip its `enabled` flag in CARRIERS to re-expose it.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Noyona_Shipping' ) ) {
	final class Noyona_Shipping {

		const OPTION_LAST_RUN = 'noyona_shipping_setup_last_run';
		const OPTION_RUN_LOG  = 'noyona_shipping_setup_log';

		/** Zones → Philippines province state codes (WC i18n/states.php). */
		const ZONES = array(
			'ncr' => array(
				'name'   => 'Philippines — NCR',
				'states' => array( '00' ),
			),
			'luzon' => array(
				'name'   => 'Philippines — Luzon (ex-NCR)',
				'states' => array(
					'ABR', 'APA', 'AUR', 'BAN', 'BTN', 'BTG', 'BEN', 'BUL', 'CAG',
					'CAN', 'CAS', 'CAT', 'CAV', 'IFU', 'ILN', 'ILS', 'ISA', 'KAL',
					'LUN', 'LAG', 'MAD', 'MAS', 'MOU', 'NUE', 'NUV', 'MDC', 'MDR',
					'PLW', 'PAM', 'PAN', 'QUE', 'QUI', 'RIZ', 'ROM', 'SOR', 'TAR',
					'ZMB', 'ALB',
				),
			),
			'visayas' => array(
				'name'   => 'Philippines — Visayas',
				'states' => array(
					'AKL', 'ANT', 'BIL', 'BOH', 'CAP', 'CEB', 'EAS', 'GUI',
					'ILI', 'LEY', 'NEC', 'NER', 'NSA', 'WSA', 'SIQ', 'SLE',
				),
			),
			'mindanao' => array(
				'name'   => 'Philippines — Mindanao',
				'states' => array(
					'AGN', 'AGS', 'BAS', 'BUK', 'CAM', 'COM', 'NCO', 'DAV',
					'DAS', 'DAC', 'DAO', 'DIN', 'LAN', 'LAS', 'MAG', 'MSC',
					'MSR', 'SAR', 'SCO', 'SUK', 'SLU', 'SUN', 'SUR', 'TAW',
					'ZAN', 'ZAS', 'ZSI',
				),
			),
		);

		/** Shipping class slug → display name. */
		const WEIGHT_CLASSES = array(
			'weight-0-1kg'  => 'Weight 0-1 kg',
			'weight-1-3kg'  => 'Weight 1-3 kg',
			'weight-3-5kg'  => 'Weight 3-5 kg',
			'weight-5-10kg' => 'Weight 5-10 kg',
		);

		/**
		 * Carriers shown at checkout. Display order = array order.
		 * `enabled => false` keeps the carrier's setup code in place but hides it from
		 * checkout (existing zone methods get `is_enabled = 0`; new ones aren't created).
		 */
		const CARRIERS = array(
			'jt'  => array( 'label' => 'J&T Express', 'enabled' => true ),
			'lbc' => array( 'label' => 'LBC Express', 'enabled' => false ),
		);

		/**
		 * Rate matrix — carrier → zone → class → PHP cost.
		 *
		 * J&T values: 2023 public walk-in rate card, "From Metro Manila" origin.
		 *   - 0–1kg row uses the 501g–1kg tier.
		 *   - 3–5kg row averages the 3.01–4kg and 4.01–5kg tiers.
		 *   - 5–10kg row averages the 5.01–10kg tiers.
		 * LBC remains at 0.00 — carrier is disabled at checkout (see CARRIERS), so
		 * the setup runner will not create / will disable its zone methods.
		 * The runner refuses to apply while all *enabled* carriers' cells are 0.00.
		 */
		const RATE_MATRIX = array(
			'jt' => array(
				'ncr' => array(
					'weight-0-1kg'  => 115.00,
					'weight-1-3kg'  => 155.00,
					'weight-3-5kg'  => 210.00,
					'weight-5-10kg' => 335.00,
				),
				'luzon' => array(
					'weight-0-1kg'  => 165.00,
					'weight-1-3kg'  => 190.00,
					'weight-3-5kg'  => 300.00,
					'weight-5-10kg' => 495.00,
				),
				'visayas' => array(
					'weight-0-1kg'  => 180.00,
					'weight-1-3kg'  => 200.00,
					'weight-3-5kg'  => 335.00,
					'weight-5-10kg' => 575.00,
				),
				'mindanao' => array(
					'weight-0-1kg'  => 195.00,
					'weight-1-3kg'  => 220.00,
					'weight-3-5kg'  => 350.00,
					'weight-5-10kg' => 575.00,
				),
			),
			'lbc' => array(
				'ncr' => array(
					'weight-0-1kg'  => 0.00,
					'weight-1-3kg'  => 0.00,
					'weight-3-5kg'  => 0.00,
					'weight-5-10kg' => 0.00,
				),
				'luzon' => array(
					'weight-0-1kg'  => 0.00,
					'weight-1-3kg'  => 0.00,
					'weight-3-5kg'  => 0.00,
					'weight-5-10kg' => 0.00,
				),
				'visayas' => array(
					'weight-0-1kg'  => 0.00,
					'weight-1-3kg'  => 0.00,
					'weight-3-5kg'  => 0.00,
					'weight-5-10kg' => 0.00,
				),
				'mindanao' => array(
					'weight-0-1kg'  => 0.00,
					'weight-1-3kg'  => 0.00,
					'weight-3-5kg'  => 0.00,
					'weight-5-10kg' => 0.00,
				),
			),
		);

		public static function init() {
			add_filter( 'woocommerce_product_get_shipping_class_id', array( __CLASS__, 'auto_assign_class' ), 10, 2 );
			add_filter( 'woocommerce_product_variation_get_shipping_class_id', array( __CLASS__, 'auto_assign_class' ), 10, 2 );

			add_action( 'admin_menu', array( __CLASS__, 'register_admin_page' ) );
			add_action( 'admin_post_noyona_shipping_setup', array( __CLASS__, 'handle_setup' ) );
		}

		/**
		 * Dynamically assign the right shipping class based on product weight.
		 * Admin only needs to set weight — no manual class dropdown per product.
		 */
		public static function auto_assign_class( $class_id, $product ) {
			if ( ! is_object( $product ) || ! method_exists( $product, 'get_weight' ) ) {
				return $class_id;
			}
			$weight = (float) $product->get_weight();
			if ( $weight <= 0 ) {
				return $class_id;
			}
			$slug = self::weight_to_class_slug( $weight );
			$term = get_term_by( 'slug', $slug, 'product_shipping_class' );
			if ( $term && ! is_wp_error( $term ) ) {
				return (int) $term->term_id;
			}
			return $class_id;
		}

		private static function weight_to_class_slug( $weight_kg ) {
			if ( $weight_kg <= 1.0 ) {
				return 'weight-0-1kg';
			}
			if ( $weight_kg <= 3.0 ) {
				return 'weight-1-3kg';
			}
			if ( $weight_kg <= 5.0 ) {
				return 'weight-3-5kg';
			}
			return 'weight-5-10kg';
		}

		public static function register_admin_page() {
			add_submenu_page(
				'tools.php',
				'Noyona Shipping Setup',
				'Noyona Shipping',
				'manage_woocommerce',
				'noyona-shipping-setup',
				array( __CLASS__, 'render_admin_page' )
			);
		}

		public static function render_admin_page() {
			$last_run       = get_option( self::OPTION_LAST_RUN );
			$log            = (array) get_option( self::OPTION_RUN_LOG, array() );
			$is_placeholder = self::rates_are_placeholder();
			$done_flag      = isset( $_GET['done'] );
			$notice         = get_transient( 'noyona_shipping_notice' );
			if ( $notice ) {
				delete_transient( 'noyona_shipping_notice' );
			}
			?>
			<div class="wrap">
				<h1>Noyona Shipping Setup</h1>
				<p>Creates 4 shipping zones (NCR, Luzon ex-NCR, Visayas, Mindanao) and 4 weight-based shipping classes, then registers a flat-rate method per enabled carrier in each zone. Origin: Makati HQ.</p>
				<p><em>Active carrier: <strong>J&amp;T Express</strong>. LBC Express is currently <strong>disabled at checkout</strong> per client direction — flip <code>'enabled' =&gt; true</code> in <code>CARRIERS</code> and re-run setup to expose it.</em></p>
				<p><em>Safe to re-run. Edit <code>RATE_MATRIX</code> in <code>inc/woocommerce-shipping.php</code> then click Run Setup; WooCommerce flat-rate costs update in place.</em></p>

				<?php if ( $notice ) : ?>
					<div class="notice notice-error"><p><?php echo esc_html( $notice ); ?></p></div>
				<?php endif; ?>
				<?php if ( $done_flag && ! $notice ) : ?>
					<div class="notice notice-success"><p>Setup completed.</p></div>
				<?php endif; ?>
				<?php if ( $is_placeholder ) : ?>
					<div class="notice notice-warning">
						<p><strong>Rate matrix is still all zeroes.</strong> Edit <code>inc/woocommerce-shipping.php</code> → <code>RATE_MATRIX</code> with real J&amp;T and LBC rates before clicking Run Setup. The runner refuses to apply placeholder values.</p>
					</div>
				<?php endif; ?>

				<?php foreach ( self::CARRIERS as $carrier_slug => $carrier_def ) : ?>
					<h2>
						<?php echo esc_html( $carrier_def['label'] ); ?> — rate matrix (PHP)
						<?php if ( empty( $carrier_def['enabled'] ) ) : ?>
							<span style="color:#a00;font-size:13px;font-weight:normal">— DISABLED at checkout</span>
						<?php endif; ?>
					</h2>
					<table class="widefat striped" style="max-width:820px;margin-bottom:20px">
						<thead>
							<tr>
								<th>Zone</th>
								<?php foreach ( self::WEIGHT_CLASSES as $slug => $name ) : ?>
									<th><?php echo esc_html( $name ); ?></th>
								<?php endforeach; ?>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( self::RATE_MATRIX[ $carrier_slug ] as $zone_slug => $cells ) : ?>
								<tr>
									<th><?php echo esc_html( self::ZONES[ $zone_slug ]['name'] ); ?></th>
									<?php foreach ( self::WEIGHT_CLASSES as $class_slug => $_ ) : ?>
										<td>₱<?php echo esc_html( number_format( (float) $cells[ $class_slug ], 2 ) ); ?></td>
									<?php endforeach; ?>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endforeach; ?>

				<?php if ( $last_run ) : ?>
					<h2>Last run</h2>
					<p><code><?php echo esc_html( $last_run ); ?></code></p>
					<?php if ( ! empty( $log ) ) : ?>
						<details>
							<summary>Run log</summary>
							<pre style="background:#f6f7f7;padding:12px;max-width:820px;overflow:auto"><?php echo esc_html( implode( "\n", $log ) ); ?></pre>
						</details>
					<?php endif; ?>
				<?php endif; ?>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:24px">
					<input type="hidden" name="action" value="noyona_shipping_setup">
					<?php wp_nonce_field( 'noyona_shipping_setup' ); ?>
					<p>
						<button type="submit" class="button button-primary" <?php disabled( $is_placeholder ); ?>>
							Run Setup
						</button>
					</p>
				</form>
			</div>
			<?php
		}

		public static function handle_setup() {
			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				wp_die( 'Forbidden' );
			}
			check_admin_referer( 'noyona_shipping_setup' );

			if ( self::rates_are_placeholder() ) {
				set_transient( 'noyona_shipping_notice', 'Rates are still placeholder (0.00). Fill RATE_MATRIX in inc/woocommerce-shipping.php before running setup.', 60 );
				wp_safe_redirect( admin_url( 'tools.php?page=noyona-shipping-setup' ) );
				exit;
			}

			$log = self::run_setup();
			update_option( self::OPTION_LAST_RUN, current_time( 'mysql' ) );
			update_option( self::OPTION_RUN_LOG, $log );

			wp_safe_redirect( admin_url( 'tools.php?page=noyona-shipping-setup&done=1' ) );
			exit;
		}

		private static function rates_are_placeholder() {
			foreach ( self::CARRIERS as $carrier_slug => $carrier_def ) {
				if ( empty( $carrier_def['enabled'] ) ) {
					continue;
				}
				if ( ! isset( self::RATE_MATRIX[ $carrier_slug ] ) ) {
					continue;
				}
				foreach ( self::RATE_MATRIX[ $carrier_slug ] as $zone_cells ) {
					foreach ( $zone_cells as $cost ) {
						if ( (float) $cost > 0.0 ) {
							return false;
						}
					}
				}
			}
			return true;
		}

		private static function run_setup() {
			$log = array();

			if ( ! class_exists( 'WC_Shipping_Zones' ) || ! class_exists( 'WC_Shipping_Zone' ) ) {
				$log[] = 'WooCommerce shipping classes unavailable — is WC active?';
				return $log;
			}

			$class_ids = array();
			foreach ( self::WEIGHT_CLASSES as $slug => $name ) {
				$term = get_term_by( 'slug', $slug, 'product_shipping_class' );
				if ( ! $term ) {
					$result = wp_insert_term( $name, 'product_shipping_class', array( 'slug' => $slug ) );
					if ( ! is_wp_error( $result ) ) {
						$class_ids[ $slug ] = (int) $result['term_id'];
						$log[]              = "Created shipping class: {$name}";
					} else {
						$log[] = "Failed to create class '{$name}': " . $result->get_error_message();
					}
				} else {
					$class_ids[ $slug ] = (int) $term->term_id;
					$log[]              = "Existing shipping class: {$name} (term_id {$term->term_id})";
				}
			}

			foreach ( self::ZONES as $zone_slug => $zone_def ) {
				$zone  = self::find_or_create_zone( $zone_def['name'] );
				$log[] = "Zone: {$zone_def['name']} (zone_id {$zone->get_id()})";

				$locations = array();
				foreach ( $zone_def['states'] as $state_code ) {
					$locations[] = array(
						'code' => 'PH:' . $state_code,
						'type' => 'state',
					);
				}
				$zone->set_locations( $locations );
				$zone->save();
				$log[] = '  → ' . count( $locations ) . ' state(s) linked';

				foreach ( self::CARRIERS as $carrier_slug => $carrier_def ) {
					$is_enabled  = ! empty( $carrier_def['enabled'] );
					$existing_id = self::find_flat_rate_method( $zone, $carrier_def['label'] );

					if ( ! $is_enabled && ! $existing_id ) {
						$log[] = "  → {$carrier_def['label']} skipped (disabled, no existing method)";
						continue;
					}

					$instance_id = $existing_id ?: (int) $zone->add_shipping_method( 'flat_rate' );
					$status_tag  = $is_enabled ? '' : ' [DISABLED]';
					$log[]       = "  → {$carrier_def['label']} flat_rate instance_id {$instance_id}{$status_tag}";

					self::set_flat_rate_costs(
						$instance_id,
						$carrier_def['label'],
						self::RATE_MATRIX[ $carrier_slug ][ $zone_slug ],
						$class_ids
					);
					self::set_method_enabled( $instance_id, $is_enabled );
					$log[] = '    → per-class rates applied' . ( $is_enabled ? '' : ' (method disabled at checkout)' );
				}
			}

			return $log;
		}

		private static function find_or_create_zone( $name ) {
			$zones = WC_Shipping_Zones::get_zones();
			foreach ( $zones as $zone_data ) {
				if ( isset( $zone_data['zone_name'] ) && $zone_data['zone_name'] === $name ) {
					return new WC_Shipping_Zone( $zone_data['zone_id'] );
				}
			}
			$zone = new WC_Shipping_Zone();
			$zone->set_zone_name( $name );
			$zone->save();
			return $zone;
		}

		/**
		 * Find a flat_rate instance in $zone whose saved title matches $target_title.
		 * Returns 0 when no matching instance exists. `get_shipping_methods()` returns
		 * both enabled and disabled methods so a previously-disabled carrier is found.
		 */
		private static function find_flat_rate_method( WC_Shipping_Zone $zone, $target_title ) {
			foreach ( $zone->get_shipping_methods() as $method ) {
				if ( 'flat_rate' !== $method->id ) {
					continue;
				}
				$settings = get_option( "woocommerce_flat_rate_{$method->instance_id}_settings", array() );
				$title    = is_array( $settings ) && isset( $settings['title'] ) ? (string) $settings['title'] : '';
				if ( $title === $target_title ) {
					return (int) $method->instance_id;
				}
			}
			return 0;
		}

		/**
		 * Toggle the zone-method `is_enabled` column. Required because flat_rate's
		 * checkout availability is gated on this DB flag, not the settings option.
		 */
		private static function set_method_enabled( $instance_id, $enabled ) {
			global $wpdb;
			$wpdb->update(
				$wpdb->prefix . 'woocommerce_shipping_zone_methods',
				array( 'is_enabled' => $enabled ? 1 : 0 ),
				array( 'instance_id' => (int) $instance_id ),
				array( '%d' ),
				array( '%d' )
			);
		}

		private static function set_flat_rate_costs( $instance_id, $title, $rates, $class_ids ) {
			$option_key = "woocommerce_flat_rate_{$instance_id}_settings";
			$settings   = get_option( $option_key, array() );
			if ( ! is_array( $settings ) ) {
				$settings = array();
			}

			$settings['title']      = $title;
			$settings['tax_status'] = 'none';
			$settings['cost']       = '0';
			$settings['type']       = 'class';

			$max_in_zone = 0.0;
			foreach ( $rates as $class_slug => $cost ) {
				$max_in_zone = max( $max_in_zone, (float) $cost );
				if ( ! isset( $class_ids[ $class_slug ] ) ) {
					continue;
				}
				$settings[ 'class_cost_' . $class_ids[ $class_slug ] ] = number_format( (float) $cost, 2, '.', '' );
			}
			$settings['no_class_cost'] = number_format( $max_in_zone, 2, '.', '' );

			update_option( $option_key, $settings );
		}
	}

	Noyona_Shipping::init();
}

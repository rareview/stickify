<?php
/**
 * Settings class.
 *
 * @author Rareview <hello@rareview.com>
 *
 * @package Stickify
 */

namespace Stickify\Inc;

use Stickify\Inc\Helpers;

/**
 * Class Settings
 */
class Settings {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->register_editor_assets();
	}

	/**
	 * Register editor assets.
	 *
	 * @return void
	 */
	public function register_editor_assets() {
		add_action( 'init', [ $this, 'stickify_register_settings' ] );
		add_action( 'admin_menu', [ $this, 'stickify_admin_menu' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_settings_assets' ] );
		add_action(
			'update_option_stickify_post_types',
			[ $this, 'clear_stickify_caches_on_setting_update' ],
			10,
			2
		);
		add_action(
			'update_option_stickify_cache_length',
			[ $this, 'clear_all_stickify_caches' ],
			10,
			2
		);
	}

	/**
	 * Enqueue assets for the settings screen.
	 *
	 * @param string $hook_suffix Current admin page hook suffix.
	 *
	 * @return void
	 */
	public function enqueue_settings_assets( $hook_suffix ) {
		if ( 'settings_page_stickify' !== $hook_suffix ) {
			return;
		}

		$asset = Helpers::asset_data( 'admin-settings' );
		wp_enqueue_script(
			Register::PREFIX . '-admin-settings-script',
			Helpers::asset_url( 'admin-settings.js' ),
			$asset['dependencies'],
			$asset['version'],
			true
		);

		wp_enqueue_style( 'wp-components' );

		wp_localize_script(
			Register::PREFIX . '-admin-settings-script',
			'stickifyAdmin',
			[
				'availablePostTypes' => $this->get_public_custom_post_types(),
			]
		);
	}

	/**
	 * Register the sticky post types settings.
	 */
	public function stickify_register_settings() {
		register_setting(
			'stickify_options_group',
			'stickify_post_types',
			[
				'type'              => 'array',
				'description'       => __( 'Array of post types to enable sticky functionality for.', 'stickify' ),
				'show_in_rest'      => [
					'schema' => [
						'type'  => 'array',
						'items' => [
							'type' => 'string',
						],
					],
				],
				'default'           => [],
				'sanitize_callback' => [ $this, 'sanitize_stickify_post_types' ],
			],
		);

		register_setting(
			'stickify_options_group',
			'stickify_cache_length',
			[
				'type'              => 'integer',
				'description'       => __( 'Sticky posts cache length in minutes.', 'stickify' ),
				'show_in_rest'      => [
					'schema' => [
						'type'    => 'integer',
						'default' => 15,
						'minimum' => 1,
					],
				],
				'default'           => 15,
				'sanitize_callback' => [ $this, 'sanitize_stickify_cache_length' ],
			]
		);
	}

	/**
	 * Sanitizes the list of post types selected for sticky functionality.
	 *
	 * @param mixed $input The raw option value submitted for the setting.
	 * @return array The sanitized array of allowed post type slugs.
	 */
	public function sanitize_stickify_post_types( $input ) {
		if ( ! is_array( $input ) ) {
			return [];
		}

		$public_post_types = array_keys( $this->get_public_custom_post_types() );

		return array_values(
			array_filter(
				$input,
				function ( $post_type ) use ( $public_post_types ) {
					return in_array( $post_type, $public_post_types, true );
				}
			)
		);
	}

	/**
	 * Sanitize cache length setting.
	 *
	 * @param mixed $input Raw cache length value.
	 *
	 * @return int
	 */
	public function sanitize_stickify_cache_length( $input ) {
		return max( 1, absint( $input ) ?: 15 );
	}

	/**
	 * Add admin menu item.
	 */
	public function stickify_admin_menu() {
		add_options_page(
			__( 'Stickify Settings', 'stickify' ),
			__( 'Stickify', 'stickify' ),
			'manage_options',
			'stickify',
			[ $this, 'stickify_options_page' ]
		);
	}

	/**
	 * Setup the options page.
	 */
	public function stickify_options_page() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Stickify Settings', 'stickify' ); ?></h1>
			<div id="stickify-settings-app"></div>
			<noscript>
				<?php $this->render_settings_form(); ?>
			</noscript>
		</div>
		<?php
	}

	/**
	 * Render the legacy no-JS settings form.
	 *
	 * @return void
	 */
	protected function render_settings_form() {
		$public_post_types   = $this->get_public_custom_post_types();
		$stickify_post_types = Helpers::get_stickify_post_types();
		$cache_length        = Helpers::get_stickify_cache_length();
		?>
		<form method="post" action="options.php">
			<?php
			settings_fields( 'stickify_options_group' );
			do_settings_sections( 'stickify_options_group' );
			?>
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><?php esc_html_e( 'Sticky Post Types', 'stickify' ); ?></th>
					<td>
						<?php foreach ( $public_post_types as $post_type => $label ) : ?>
							<label>
								<input type="checkbox" name="stickify_post_types[]" value="<?php echo esc_attr( $post_type ); ?>" <?php checked( in_array( $post_type, $stickify_post_types, true ) ); ?> />
								<?php echo esc_html( $label ); ?>
							</label><br>
						<?php endforeach; ?>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label for="stickify-cache-length"><?php esc_html_e( 'Cache Length', 'stickify' ); ?></label>
					</th>
					<td>
						<input
							id="stickify-cache-length"
							type="number"
							min="1"
							name="stickify_cache_length"
							value="<?php echo esc_attr( $cache_length ); ?>"
						/>
						<p class="description"><?php esc_html_e( 'How long, in minutes, sticky query results should be cached.', 'stickify' ); ?></p>
					</td>
				</tr>
			</table>
			<?php submit_button( __( 'Save Changes', 'stickify' ) ); ?>
		</form>
		<?php
	}

	/**
	 * Get public custom post types.
	 *
	 * @return array<string, string>
	 */
	protected function get_public_custom_post_types() {
		$post_types = get_post_types(
			[
				'public' => true,
			],
			'objects'
		);

		// Exclude select built-in post types that aren't relevant for sticky functionality.
		unset( $post_types['page'], $post_types['attachment'] );

		$labels = [];

		foreach ( $post_types as $post_type ) {
			$labels[ $post_type->name ] = $post_type->labels->singular_name;
		}

		return $labels;
	}

	/**
	 * Clear sticky caches when enabled post types change.
	 *
	 * @param array $old_value Previous value.
	 * @param array $new_value New value.
	 *
	 * @return void
	 */
	public function clear_stickify_caches_on_setting_update( $old_value, $new_value ): void {
		$old_value = (array) $old_value;
		$new_value = (array) $new_value;

		$post_types = array_unique( array_merge( $old_value, $new_value ) );

		foreach ( $post_types as $post_type ) {
			Helpers::delete_stickify_cache_by_type( $post_type );
		}
	}

	/**
	 * Clear all sticky post type caches when cache length changes.
	 *
	 * @param mixed $old_value Previous value.
	 * @param mixed $new_value New value.
	 *
	 * @return void
	 */
	public function clear_all_stickify_caches( $old_value, $new_value ): void {
		unset( $old_value, $new_value );

		foreach ( Helpers::get_stickify_post_types() as $post_type ) {
			Helpers::delete_stickify_cache_by_type( $post_type );
		}
	}
}

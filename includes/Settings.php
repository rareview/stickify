<?php
/**
 * Settings class.
 *
 * @author Rareview <hello@rareview.com>
 *
 * @package StickyPostTypes
 */

namespace StickyPostTypes\Inc;

use StickyPostTypes\Inc\Helpers;

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
		add_action( 'init', [ $this, 'sticky_post_types_register_settings' ] );
		add_action( 'admin_menu', [ $this, 'sticky_post_types_admin_menu' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_settings_assets' ] );
		add_action(
			'update_option_sticky_post_types_post_types',
			[ $this, 'clear_sticky_post_type_caches_on_setting_update' ],
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
		if ( 'settings_page_sticky-post-types' !== $hook_suffix ) {
			return;
		}

		wp_enqueue_script(
			Register::PREFIX . '-admin-settings-script',
			Helpers::asset_url( 'admin-settings.js' ),
			[ 'wp-api-fetch', 'wp-components', 'wp-element', 'wp-i18n' ],
			Helpers::version(),
			true
		);

		wp_enqueue_style( 'wp-components' );

		wp_localize_script(
			Register::PREFIX . '-admin-settings-script',
			'stickyPostTypesAdmin',
			[
				'availablePostTypes' => $this->get_public_custom_post_types(),
			]
		);
	}

	/**
	 * Register the sticky post types settings.
	 */
	public function sticky_post_types_register_settings() {
		register_setting(
			'sticky_post_types_options_group',
			'sticky_post_types_post_types',
			[
				'type'              => 'array',
				'description'       => __( 'Array of post types to enable sticky functionality for.', 'sticky-post-types' ),
				'show_in_rest'      => [
					'schema' => [
						'type'  => 'array',
						'items' => [
							'type' => 'string',
						],
					],
				],
				'default'           => [],
				'sanitize_callback' => [ $this, 'sanitize_sticky_post_types_post_types' ],
			],
		);
	}

	/**
	 * Sanitizes the list of post types selected for sticky functionality.
	 *
	 * @param mixed $input The raw option value submitted for the setting.
	 * @return array The sanitized array of allowed post type slugs.
	 */
	public function sanitize_sticky_post_types_post_types( $input ) {
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
	 * Add admin menu item.
	 */
	public function sticky_post_types_admin_menu() {
		add_options_page(
			__( 'Sticky Post Types Settings', 'sticky-post-types' ),
			__( 'Sticky Post Types', 'sticky-post-types' ),
			'manage_options',
			'sticky-post-types',
			[ $this, 'sticky_post_types_options_page' ]
		);
	}

	/**
	 * Setup the options page.
	 */
	public function sticky_post_types_options_page() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Sticky Custom Post Types Settings', 'sticky-post-types' ); ?></h1>
			<div id="sticky-post-types-settings-app"></div>
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
		$public_post_types = $this->get_public_custom_post_types();
		$sticky_post_types = Helpers::get_sticky_post_types();
		?>
		<form method="post" action="options.php">
			<?php
			settings_fields( 'sticky_post_types_options_group' );
			do_settings_sections( 'sticky_post_types_options_group' );
			?>
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><?php esc_html_e( 'Sticky Post Types', 'sticky-post-types' ); ?></th>
					<td>
						<?php foreach ( $public_post_types as $post_type => $label ) : ?>
							<label>
								<input type="checkbox" name="sticky_post_types_post_types[]" value="<?php echo esc_attr( $post_type ); ?>" <?php checked( in_array( $post_type, $sticky_post_types, true ) ); ?> />
								<?php echo esc_html( $label ); ?>
							</label><br>
						<?php endforeach; ?>
					</td>
				</tr>
			</table>
			<?php submit_button( __( 'Save Changes', 'sticky-post-types' ) ); ?>
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
				'public'   => true,
				'_builtin' => false,
			],
			'objects'
		);

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
	public function clear_sticky_post_type_caches_on_setting_update( $old_value, $new_value ): void {
		$old_value = (array) $old_value;
		$new_value = (array) $new_value;

		$post_types = array_unique( array_merge( $old_value, $new_value ) );

		foreach ( $post_types as $post_type ) {
			Helpers::delete_sticky_posts_cache_by_type( $post_type );
		}
	}
}

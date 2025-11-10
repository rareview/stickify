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
	 * Publically available post types.
	 *
	 * @var array $public_post_types
	 */
	protected $public_post_types = [];

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
		add_action( 'admin_init', [ $this, 'sticky_post_types_register_settings' ] );
		add_action( 'admin_menu', [ $this, 'sticky_post_types_admin_menu' ] );
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
				'show_in_rest'      => true,
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

		$public_post_types = get_post_types(
			[
				'public'   => true,
				'_builtin' => false,
			],
		);

		return array_values(
			array_filter(
				$input,
				function ( $post_type ) use ( $public_post_types ) {
					return in_array( $post_type, $public_post_types, true );
				}
			),
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
		$public_post_types = get_post_types(
			[
				'public'   => true,
				'_builtin' => false,
			],
		);
		$sticky_post_types = Helpers::get_sticky_post_types_types();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Sticky Custom Post Types Settings', 'sticky-post-types' ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'sticky_post_types_options_group' );
				do_settings_sections( 'sticky_post_types_options_group' );
				?>
				<table class="form-table">
					<tr valign="top">
						<th scope="row"><?php esc_html_e( 'Sticky Post Types', 'sticky-post-types' ); ?></th>
						<td>
							<?php foreach ( $public_post_types as $post_type ) : ?>
								<label>
									<input type="checkbox" name="sticky_post_types_post_types[]" value="<?php echo esc_attr( $post_type ); ?>" <?php checked( in_array( $post_type, $sticky_post_types, true ) ); ?> />
									<?php echo esc_html( ucfirst( $post_type ) ); ?>
								</label><br>
							<?php endforeach; ?>
						</td>
					</tr>
				</table>
				<?php submit_button( __( 'Save Changes', 'sticky-post-types' ) ); ?>
			</form>
		</div>
		<?php
	}
}

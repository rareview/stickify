<?php
/**
 * Settings class.
 *
 * @author Rareview <hello@rareview.com>
 *
 * @package Sticky CPTs
 */

namespace StickyCPTs\Inc;

use StickyCPTs\Inc\Helpers;

/**
 * Class Settings
 */
class Settings {

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
		add_action( 'admin_init', [ $this, 'sticky_cpts_register_settings' ] );
		add_action( 'admin_menu', [ $this, 'sticky_cpts_admin_menu' ] );
    }

	public function sticky_cpts_register_settings() {
		register_setting(
			'sticky_cpts_options_group',
			'sticky_cpts_post_types',
			[
				'type'              => 'array',
				'description'       => __( 'Array of post types to enable sticky functionality for.', 'sticky-cpts' ),
				'show_in_rest'      => true,
				'default'           => [],
				'sanitize_callback' => [ $this, 'sanitize_sticky_cpts_post_types' ],
			],
		);
	}

	public function sanitize_sticky_cpts_post_types( $input ) {
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

	public function sticky_cpts_admin_menu() {
		add_options_page(
			__( 'Sticky CPTs Settings', 'sticky-cpts' ),
			__( 'Sticky CPTs', 'sticky-cpts' ),
			'manage_options',
			'sticky-cpts',
			[ $this, 'sticky_cpts_options_page' ]
		);
	}

	public function sticky_cpts_options_page() {
		$public_post_types = get_post_types(
			[
				'public'   => true,
				'_builtin' => false,
			],
		);
		$sticky_post_types = Helpers::get_sticky_cpts_types();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Sticky Custom Post Types Settings', 'sticky-cpts' ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'sticky_cpts_options_group' );
				do_settings_sections( 'sticky_cpts_options_group' );
				?>
				<table class="form-table">
					<tr valign="top">
						<th scope="row"><?php esc_html_e( 'Sticky Post Types', 'sticky-cpts' ); ?></th>
						<td>
							<?php foreach ( $public_post_types as $post_type ) : ?>
								<label>
									<input type="checkbox" name="sticky_cpts_post_types[]" value="<?php echo esc_attr( $post_type ); ?>" <?php checked( in_array( $post_type, $sticky_post_types, true ) ); ?> />
									<?php echo esc_html( ucfirst( $post_type ) ); ?>
								</label><br>
							<?php endforeach; ?>
						</td>
					</tr>
				</table>
				<?php submit_button( __( 'Save Changes', 'sticky-cpts' ) ); ?>
			</form>
		</div>
		<?php
	}
}

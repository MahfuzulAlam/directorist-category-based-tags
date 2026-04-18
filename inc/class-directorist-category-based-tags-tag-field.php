<?php
/**
 * Tag taxonomy admin field.
 *
 * @package Directorist_Category_Based_Tags
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Directorist_Category_Based_Tags_Tag_Field {
	const META_KEY     = '_directorist_category_based_tags_categories';
	const NONCE_ACTION = 'directorist_category_based_tags_tag_field_action';
	const NONCE_NAME   = 'directorist_category_based_tags_tag_field_nonce';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( ATBDP_TAGS . '_edit_form_fields', array( __CLASS__, 'render_field' ) );
		add_action( 'edited_' . ATBDP_TAGS, array( __CLASS__, 'save_field' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ), 20 );
	}

	/**
	 * Check whether the current admin screen is the Directorist tag edit screen.
	 *
	 * @return bool
	 */
	public static function is_tag_edit_screen() {
		if ( ! is_admin() || ! function_exists( 'get_current_screen' ) ) {
			return false;
		}

		$screen = get_current_screen();

		return $screen && 'term' === $screen->base && ATBDP_TAGS === $screen->taxonomy;
	}

	/**
	 * Format category label with hierarchy depth.
	 *
	 * @param WP_Term $term Category term.
	 * @return string
	 */
	public static function get_category_option_label( WP_Term $term ) {
		$depth = count( get_ancestors( $term->term_id, ATBDP_CATEGORY, 'taxonomy' ) );

		return str_repeat( '- ', $depth ) . $term->name;
	}

	/**
	 * Render the category multiselect on the tag edit screen.
	 *
	 * @param WP_Term $term Current tag term.
	 * @return void
	 */
	public static function render_field( WP_Term $term ) {
		$selected_categories = get_term_meta( $term->term_id, self::META_KEY, true );
		$selected_categories = is_array( $selected_categories ) ? wp_parse_id_list( $selected_categories ) : array();

		$categories = get_terms(
			array(
				'taxonomy'   => ATBDP_CATEGORY,
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );
		?>
		<tr class="form-field term-group-wrap directorist-category-based-tags-wrap">
			<th scope="row">
				<label for="directorist-category-based-tags-categories"><?php esc_html_e( 'Categories', 'directorist-category-based-tags' ); ?></label>
			</th>
			<td>
				<select
					name="directorist_category_based_tags_categories[]"
					id="directorist-category-based-tags-categories"
					class="directorist-category-based-tags-categories"
					multiple="multiple"
					style="width: 100%;"
				>
					<?php if ( ! is_wp_error( $categories ) ) : ?>
						<?php foreach ( $categories as $category ) : ?>
							<option
								value="<?php echo esc_attr( $category->term_id ); ?>"
								<?php selected( in_array( (int) $category->term_id, $selected_categories, true ), true ); ?>
							>
								<?php echo esc_html( self::get_category_option_label( $category ) ); ?>
							</option>
						<?php endforeach; ?>
					<?php endif; ?>
				</select>
				<p class="description">
					<?php esc_html_e( 'Select one or more categories for this tag.', 'directorist-category-based-tags' ); ?>
				</p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Save selected categories for the tag.
	 *
	 * @param int $term_id Tag term ID.
	 * @return void
	 */
	public static function save_field( $term_id ) {
		$nonce = isset( $_POST[ self::NONCE_NAME ] ) ? sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_term', $term_id ) ) {
			return;
		}

		$category_ids = ! empty( $_POST['directorist_category_based_tags_categories'] )
			? wp_parse_id_list( wp_unslash( $_POST['directorist_category_based_tags_categories'] ) )
			: array();

		if ( ! empty( $category_ids ) ) {
			update_term_meta( $term_id, self::META_KEY, $category_ids );
			return;
		}

		delete_term_meta( $term_id, self::META_KEY );
	}

	/**
	 * Enqueue Select2 assets and field initializer on the tag edit screen.
	 *
	 * @return void
	 */
	public static function enqueue_assets() {
		if ( ! self::is_tag_edit_screen() ) {
			return;
		}

		$script_dependencies = array( 'jquery' );

		if ( wp_style_is( 'directorist-select2-style', 'registered' ) ) {
			wp_enqueue_style( 'directorist-select2-style' );
		}

		if ( wp_script_is( 'directorist-select2-script', 'registered' ) ) {
			wp_enqueue_script( 'directorist-select2-script' );
			$script_dependencies[] = 'directorist-select2-script';
		}

		wp_register_script(
			'directorist-category-based-tags-admin',
			DIRECTORIST_CATEGORY_BASED_TAGS_URL . 'assets/js/directorist-category-based-tags-admin.js',
			$script_dependencies,
			DIRECTORIST_CATEGORY_BASED_TAGS_VERSION,
			true
		);

		wp_enqueue_script( 'directorist-category-based-tags-admin' );
	}
}

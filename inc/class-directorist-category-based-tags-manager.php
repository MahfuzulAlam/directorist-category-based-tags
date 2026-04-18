<?php
/**
 * Runtime category based tags feature.
 *
 * @package Directorist_Category_Based_Tags
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Directorist_Category_Based_Tags_Manager {
	const AJAX_ACTION = 'directorist_category_based_tags_get_related_tags';
	const NONCE_ACTION = 'directorist_category_based_tags_nonce';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'wp_ajax_' . self::AJAX_ACTION, array( __CLASS__, 'ajax_get_related_tags' ) );
		add_action( 'wp_ajax_nopriv_' . self::AJAX_ACTION, array( __CLASS__, 'ajax_get_related_tags' ) );
		add_action( 'admin_footer', array( __CLASS__, 'enqueue_runtime_assets' ), 5 );
		add_action( 'wp_footer', array( __CLASS__, 'enqueue_runtime_assets' ), 5 );
		add_filter( 'atbdp_listing_type_settings_field_list', array( __CLASS__, 'register_option_fields' ) );
		add_filter( 'atbdp_categories_settings_sections', array( __CLASS__, 'register_option_fields_in_section' ) );
	}

	/**
	 * Add fields to the categories settings section.
	 *
	 * @param array $sections Settings sections.
	 * @return array
	 */
	public static function register_option_fields_in_section( $sections ) {
		if ( empty( $sections['categories_settings'] ) || ! is_array( $sections['categories_settings'] ) ) {
			return $sections;
		}

		if ( empty( $sections['categories_settings']['fields'] ) || ! is_array( $sections['categories_settings']['fields'] ) ) {
			$sections['categories_settings']['fields'] = array();
		}

		$sections['categories_settings']['fields'][] = 'category_based_tags';
		$sections['categories_settings']['fields'][] = 'category_based_tags_show_if_empty';
		$sections['categories_settings']['fields']   = array_values( array_unique( $sections['categories_settings']['fields'] ) );

		return $sections;
	}

	/**
	 * Register plugin option fields.
	 *
	 * @param array $fields Settings fields.
	 * @return array
	 */
	public static function register_option_fields( $fields ) {
		$fields['category_based_tags'] = array(
			'label' => __( 'Category Based Tags', 'directorist-category-based-tags' ),
			'type'  => 'toggle',
			'value' => false,
		);

		$fields['category_based_tags_show_if_empty'] = array(
			'label'   => __( 'Show All Tags If Empty', 'directorist-category-based-tags' ),
			'type'    => 'toggle',
			'value'   => false,
			'show-if' => array(
				'where'      => 'category_based_tags',
				'conditions' => array(
					array(
						'key'     => 'value',
						'compare' => '=',
						'value'   => true,
					),
				),
			),
		);

		return $fields;
	}

	/**
	 * Check whether the feature is enabled.
	 *
	 * @return bool
	 */
	public static function is_feature_enabled() {
		return (bool) get_directorist_option( 'category_based_tags', false );
	}

	/**
	 * Check whether all tags should be shown when no related tags are found.
	 *
	 * @return bool
	 */
	public static function should_show_all_tags_if_empty() {
		return (bool) get_directorist_option( 'category_based_tags_show_if_empty', false );
	}

	/**
	 * Determine whether the current screen is the Directorist listing editor.
	 *
	 * @return bool
	 */
	public static function is_admin_listing_editor_screen() {
		if ( ! is_admin() || ! function_exists( 'get_current_screen' ) ) {
			return false;
		}

		$screen = get_current_screen();

		return $screen && in_array( $screen->base, array( 'post', 'post-new' ), true ) && ATBDP_POST_TYPE === $screen->post_type;
	}

	/**
	 * Determine whether the Directorist add listing script is present.
	 *
	 * @return bool
	 */
	public static function is_frontend_listing_form_screen() {
		return ! is_admin() && ( wp_script_is( 'directorist-add-listing', 'enqueued' ) || wp_script_is( 'directorist-add-listing', 'done' ) );
	}

	/**
	 * Determine whether the Directorist search form script is present.
	 *
	 * @return bool
	 */
	public static function is_frontend_search_form_screen() {
		return ! is_admin() && ( wp_script_is( 'directorist-search-form', 'enqueued' ) || wp_script_is( 'directorist-search-form', 'done' ) );
	}

	/**
	 * Determine whether the runtime script should be printed.
	 *
	 * @return bool
	 */
	public static function should_render_runtime_script() {
		if ( ! self::is_feature_enabled() ) {
			return false;
		}

		return self::is_admin_listing_editor_screen() || self::is_frontend_listing_form_screen() || self::is_frontend_search_form_screen();
	}

	/**
	 * Return all Directorist tags as normalized items.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_all_tags() {
		$tags = get_terms(
			array(
				'taxonomy'   => ATBDP_TAGS,
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		if ( is_wp_error( $tags ) || empty( $tags ) ) {
			return array();
		}

		return array_map(
			static function( $tag ) {
				return array(
					'id'   => (int) $tag->term_id,
					'name' => $tag->name,
				);
			},
			$tags
		);
	}

	/**
	 * Get tags related to the provided category IDs.
	 *
	 * @param array<int|string> $category_ids Selected category IDs.
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_related_tags( $category_ids ) {
		$category_ids = wp_parse_id_list( $category_ids );

		if ( empty( $category_ids ) ) {
			return self::get_all_tags();
		}

		$tags = self::get_all_tags();

		if ( empty( $tags ) ) {
			return array();
		}

		$related_tags = array();

		foreach ( $tags as $tag ) {
			$related_categories = get_term_meta( $tag['id'], Directorist_Category_Based_Tags_Tag_Field::META_KEY, true );
			$related_categories = is_array( $related_categories ) ? wp_parse_id_list( $related_categories ) : array();

			if ( empty( $related_categories ) || ! array_intersect( $category_ids, $related_categories ) ) {
				continue;
			}

			$related_tags[] = $tag;
		}

		if ( ! empty( $related_tags ) ) {
			return $related_tags;
		}

		return self::should_show_all_tags_if_empty() ? self::get_all_tags() : array();
	}

	/**
	 * AJAX callback for fetching related tags.
	 *
	 * @return void
	 */
	public static function ajax_get_related_tags() {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		if ( ! self::is_feature_enabled() ) {
				wp_send_json_success(
					array(
						'tags' => self::get_all_tags(),
					)
				);
			}

		$category_ids = ! empty( $_POST['category_ids'] ) ? wp_unslash( $_POST['category_ids'] ) : array();
		$category_ids = is_array( $category_ids ) ? $category_ids : array( $category_ids );

		wp_send_json_success(
			array(
				'tags' => self::get_related_tags( $category_ids ),
			)
		);
	}

	/**
	 * Enqueue the runtime script for listing and search forms.
	 *
	 * @return void
	 */
	public static function enqueue_runtime_assets() {
		if ( ! self::should_render_runtime_script() ) {
			return;
		}

		wp_register_script(
			'directorist-category-based-tags-runtime',
			DIRECTORIST_CATEGORY_BASED_TAGS_URL . 'assets/js/directorist-category-based-tags.js',
			array( 'jquery' ),
			DIRECTORIST_CATEGORY_BASED_TAGS_VERSION,
			true
		);

		wp_localize_script(
			'directorist-category-based-tags-runtime',
			'directoristCategoryBasedTags',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'action'  => self::AJAX_ACTION,
				'nonce'   => wp_create_nonce( self::NONCE_ACTION ),
			)
		);

		wp_enqueue_script( 'directorist-category-based-tags-runtime' );
	}
}

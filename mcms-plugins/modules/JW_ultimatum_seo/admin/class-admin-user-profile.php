<?php
/**
 * @package MCMSSEO\Admin
 * @since      1.8.0
 */

/**
 * Customizes user profile.
 */
class MCMSSEO_Admin_User_Profile {
	/**
	 * Class constructor
	 */
	public function __construct() {
		add_action( 'show_user_profile', array( $this, 'user_profile' ) );
		add_action( 'edit_user_profile', array( $this, 'user_profile' ) );
		add_action( 'personal_options_update', array( $this, 'process_user_option_update' ) );
		add_action( 'edit_user_profile_update', array( $this, 'process_user_option_update' ) );

		add_action( 'update_user_meta', array( $this, 'clear_author_sitemap_cache' ), 10, 3 );
	}

	/**
	 * Clear author sitemap cache when settings are changed
	 *
	 * @since 3.1
	 *
	 * @param int    $meta_id The ID of the meta option changed.
	 * @param int    $object_id The ID of the user.
	 * @param string $meta_key The key of the meta field changed.
	 */
	public function clear_author_sitemap_cache( $meta_id, $object_id, $meta_key ) {
		if ( '_ultimatum_mcmsseo_profile_updated' === $meta_key ) {
			MCMSSEO_Utils::clear_sitemap_cache( array( 'author' ) );
		}
	}

	/**
	 * Filter POST variables.
	 *
	 * @param string $var_name Name of the variable to filter.
	 *
	 * @return mixed
	 */
	private function filter_input_post( $var_name ) {
		$val = filter_input( INPUT_POST, $var_name );
		if ( $val ) {
			return MCMSSEO_Utils::sanitize_text_field( $val );
		}
		return '';
	}

	/**
	 * Updates the user metas that (might) have been set on the user profile page.
	 *
	 * @param    int $user_id of the updated user.
	 */
	public function process_user_option_update( $user_id ) {
		update_user_meta( $user_id, '_ultimatum_mcmsseo_profile_updated', time() );

		$nonce_value = $this->filter_input_post( 'mcmsseo_nonce' );

		if ( empty( $nonce_value ) ) { // Submit from alternate forms.
			return;
		}

		check_admin_referer( 'mcmsseo_user_profile_update', 'mcmsseo_nonce' );

		update_user_meta( $user_id, 'mcmsseo_title', $this->filter_input_post( 'mcmsseo_author_title' ) );
		update_user_meta( $user_id, 'mcmsseo_metadesc', $this->filter_input_post( 'mcmsseo_author_metadesc' ) );
		update_user_meta( $user_id, 'mcmsseo_metakey', $this->filter_input_post( 'mcmsseo_author_metakey' ) );
		update_user_meta( $user_id, 'mcmsseo_excludeauthorsitemap', $this->filter_input_post( 'mcmsseo_author_exclude' ) );
		update_user_meta( $user_id, 'mcmsseo_content_analysis_disable', $this->filter_input_post( 'mcmsseo_content_analysis_disable' ) );
		update_user_meta( $user_id, 'mcmsseo_keyword_analysis_disable', $this->filter_input_post( 'mcmsseo_keyword_analysis_disable' ) );
	}

	/**
	 * Add the inputs needed for SEO values to the User Profile page
	 *
	 * @param MCMS_User $user User instance to output for.
	 */
	public function user_profile( $user ) {
		$options = MCMSSEO_Options::get_option( 'mcmsseo_titles' );

		mcms_nonce_field( 'mcmsseo_user_profile_update', 'mcmsseo_nonce' );

		require_once( 'views/user-profile.php' );
	}
}

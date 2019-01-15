<?php
/**
 * @package MCMSSEO\Premium\Classes
 */

/**
 * Class MCMSSEO_Redirect_Manager
 */
class MCMSSEO_Redirect_Upgrade {

	/**
	 * @var array
	 */
	private static $redirect_option_names = array(
		MCMSSEO_Redirect_Option::OLD_OPTION_PLAIN => MCMSSEO_Redirect::FORMAT_PLAIN,
		MCMSSEO_Redirect_Option::OLD_OPTION_REGEX => MCMSSEO_Redirect::FORMAT_REGEX,
	);

	/**
	 * Upgrade routine from Ultimatum SEO premium 1.2.0
	 */
	public static function upgrade_1_2_0() {
		$redirect_option  = self::get_redirect_option();
		$redirects = array();

		foreach ( self::$redirect_option_names as $redirect_option_name => $redirect_format ) {
			$old_redirects = $redirect_option->get_from_option( $redirect_option_name );

			foreach ( $old_redirects as $origin => $redirect ) {
				// Check if the redirect is not an array yet.
				if ( ! is_array( $redirect ) ) {
					$redirects[] = new MCMSSEO_Redirect( $origin, $redirect['url'], $redirect['type'], $redirect_format );
				}
			}
		}

		self::import_redirects( $redirects );
	}

	/**
	 * Check if redirects should be imported from the free version
	 *
	 * @since 2.3
	 */
	public static function import_redirects_2_3() {
		$mcms_query  = new MCMS_Query( 'post_type=any&meta_key=_ultimatum_mcmsseo_redirect&order=ASC' );

		if ( ! empty( $mcms_query->posts ) ) {
			$redirects = array();

			foreach ( $mcms_query->posts as $post ) {

				$old_url = '/' . $post->post_name . '/';
				$new_url = get_post_meta( $post->ID, '_ultimatum_mcmsseo_redirect', true );

				// Create redirect.
				$redirects[] = new MCMSSEO_Redirect( $old_url, $new_url, 301, MCMSSEO_Redirect::FORMAT_PLAIN );

				// Remove post meta value.
				delete_post_meta( $post->ID, '_ultimatum_mcmsseo_redirect' );
			}

			self::import_redirects( $redirects );
		}
	}


	/**
	 * Upgrade routine to merge plain and regex redirects in a single option.
	 */
	public static function upgrade_3_1() {
		$redirects = array();

		foreach ( self::$redirect_option_names as $redirect_option_name => $redirect_format ) {
			$old_redirects = get_option( $redirect_option_name, array() );

			foreach ( $old_redirects as $origin => $redirect ) {
				// Only when URL and type is set.
				if ( array_key_exists( 'url', $redirect ) && array_key_exists( 'type', $redirect ) ) {
					$redirects[] = new MCMSSEO_Redirect( $origin, $redirect['url'], $redirect['type'], $redirect_format );
				}
			}
		}

		// Saving the redirects to the option.
		self::import_redirects( $redirects, array( new MCMSSEO_Redirect_Option_Exporter() ) );
	}

	/**
	 * Imports an array of redirect objects.
	 *
	 * @param MCMSSEO_Redirect[]               $redirects The redirects.
	 * @param null|MCMSSEO_Redirect_Exporter[] $exporters The exporters.
	 */
	private static function import_redirects( $redirects, $exporters = null ) {
		if ( empty( $redirects ) ) {
			return;
		}

		$redirect_option  = self::get_redirect_option();
		$redirect_manager = new MCMSSEO_Redirect_Manager( null, $exporters, $redirect_option );

		foreach ( $redirects as $redirect ) {
			$redirect_option->add( $redirect );
		}

		$redirect_option->save( false );
		$redirect_manager->export_redirects();
	}

	/**
	 * Gets and caches the redirect option.
	 *
	 * @return MCMSSEO_Redirect_Option
	 */
	private static function get_redirect_option() {
		static $redirect_option;

		if ( empty( $redirect_option ) ) {
			$redirect_option  = new MCMSSEO_Redirect_Option( false );
		}

		return $redirect_option;
	}
}
